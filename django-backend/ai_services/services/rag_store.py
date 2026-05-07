import os
import glob
import json
import logging
from dataclasses import dataclass
from typing import List, Optional
from collections import Counter

import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

logger = logging.getLogger(__name__)


RAG_INDEX_FILE = os.path.abspath(
    os.path.join(os.path.dirname(__file__), "..", "knowledge", "rag_index.json")
)
RAG_EMBEDDINGS_FILE = os.path.abspath(
    os.path.join(os.path.dirname(__file__), "..", "knowledge", "rag_embeddings.npy")
)


def _repo_root() -> str:
    return os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))


def _default_sources() -> List[str]:
    base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
    knowledge_dir = os.path.join(base_dir, "knowledge")
    return [
        os.path.join(knowledge_dir, "*.md"),
        os.path.join(knowledge_dir, "*.txt"),
    ]


def _gather_sources() -> List[str]:
    env_sources = os.getenv("RAG_SOURCES")
    patterns = (
        [s.strip() for s in env_sources.split(",")]
        if env_sources
        else _default_sources()
    )
    files: List[str] = []
    for pattern in patterns:
        if os.path.isfile(pattern):
            files.append(pattern)
        else:
            files.extend(glob.glob(pattern))
    unique = sorted({os.path.abspath(p) for p in files})
    return unique


def _read_text_file(path: str, max_bytes: int) -> str:
    if os.path.getsize(path) > max_bytes:
        logger.warning("Skipping large file for RAG: %s", path)
        return ""
    try:
        with open(path, "r", encoding="utf-8", errors="ignore") as handle:
            return handle.read()
    except Exception:
        return ""


def _read_pdf_file(path: str, max_bytes: int) -> str:
    if os.path.getsize(path) > max_bytes:
        logger.warning("Skipping large PDF for RAG: %s", path)
        return ""
    try:
        from PyPDF2 import PdfReader
    except Exception:
        logger.warning("PyPDF2 not available; skipping PDF: %s", path)
        return ""
    try:
        reader = PdfReader(path)
        pages = []
        for page in reader.pages:
            pages.append(page.extract_text() or "")
        return "\n".join(pages)
    except Exception:
        return ""


def _clean_text(text: str) -> str:
    return " ".join(text.replace("\r", " ").split())


def _chunk_text(text: str, chunk_size: int, overlap: int) -> List[str]:
    if not text:
        return []
    text = _clean_text(text)
    chunks = []
    start = 0
    length = len(text)
    while start < length:
        end = min(length, start + chunk_size)
        window = text[start:end]

        if end < length:
            pivot = window.rfind(". ")
            if pivot > chunk_size * 0.6:
                end = start + pivot + 1
                window = text[start:end].strip()

        chunks.append(window.strip())
        if end >= length:
            break
        start = max(0, end - overlap)
    return [c for c in chunks if c]


def _friendly_source_name(path: str) -> str:
    basename = os.path.basename(path)
    name, _ = os.path.splitext(basename)
    return name.replace("_", " ").replace("-", " ").strip()


@dataclass
class RagChunk:
    text: str
    source: str


class EnhancedRAGStore:
    """
    Enhanced RAG Store with:
    - Multi-method retrieval (semantic + keyword/BM25)
    - Query expansion for better recall
    - Result re-ranking
    """

    def __init__(self):
        self.chunks: List[RagChunk] = []
        self.embeddings: Optional[np.ndarray] = None
        self.vectorizer: Optional[TfidfVectorizer] = None
        self.method: str = "tfidf"
        self.model_name: Optional[str] = None
        self._embedder = None

    def load_or_build(self) -> None:
        if self._load_index():
            return
        self._build_index()
        self._save_index()

    def build_and_save(self) -> None:
        self._build_index()
        self._save_index()

    def _load_index(self) -> bool:
        if not os.path.exists(RAG_INDEX_FILE):
            return False
        try:
            with open(RAG_INDEX_FILE, "r", encoding="utf-8") as handle:
                meta = json.load(handle)
            self.method = meta.get("method", "tfidf")
            self.model_name = meta.get("model_name")
            self.chunks = [RagChunk(**c) for c in meta.get("chunks", [])]
            if self.method == "sbert" and os.path.exists(RAG_EMBEDDINGS_FILE):
                self.embeddings = np.load(RAG_EMBEDDINGS_FILE)
            elif self.method == "tfidf":
                self._build_tfidf()
            return bool(self.chunks)
        except Exception:
            return False

    def _save_index(self) -> None:
        try:
            meta = {
                "method": self.method,
                "model_name": self.model_name,
                "chunks": [c.__dict__ for c in self.chunks],
            }
            with open(RAG_INDEX_FILE, "w", encoding="utf-8") as handle:
                json.dump(meta, handle)
            if self.method == "sbert" and self.embeddings is not None:
                np.save(RAG_EMBEDDINGS_FILE, self.embeddings)
        except Exception as exc:
            logger.warning("Failed to save RAG index: %s", exc)

    def _build_index(self) -> None:
        sources = _gather_sources()
        max_bytes = int(os.getenv("RAG_MAX_FILE_BYTES", "2000000"))
        include_pdfs = os.getenv("RAG_INCLUDE_PDFS", "false").lower() in {
            "1",
            "true",
            "yes",
        }
        chunk_size = int(os.getenv("RAG_CHUNK_SIZE", "1200"))
        overlap = int(os.getenv("RAG_CHUNK_OVERLAP", "200"))

        chunks: List[RagChunk] = []
        for path in sources:
            ext = os.path.splitext(path)[1].lower()
            text = ""
            if ext == ".pdf" and include_pdfs:
                text = _read_pdf_file(path, max_bytes)
            elif ext in {".md", ".txt", ".php"}:
                text = _read_text_file(path, max_bytes)
            if not text:
                continue

            friendly_name = _friendly_source_name(path)
            for chunk in _chunk_text(text, chunk_size, overlap):
                chunks.append(RagChunk(text=chunk, source=friendly_name))

        self.chunks = chunks
        if not self.chunks:
            logger.warning("No RAG chunks created; check RAG_SOURCES.")
            return

        use_embeddings = os.getenv("RAG_USE_EMBEDDINGS", "true").lower() in {
            "1",
            "true",
            "yes",
        }
        if use_embeddings:
            self._build_embeddings()
        if self.embeddings is None:
            self._build_tfidf()

    def _build_embeddings(self) -> None:
        try:
            from sentence_transformers import SentenceTransformer
        except Exception as exc:
            logger.warning(
                "SentenceTransformers unavailable, falling back to TF-IDF: %s", exc
            )
            self.embeddings = None
            self.method = "tfidf"
            return

        model_name = os.getenv("RAG_EMBEDDING_MODEL", "all-MiniLM-L6-v2")
        try:
            self._embedder = SentenceTransformer(model_name)
            texts = [c.text for c in self.chunks]
            self.embeddings = self._embedder.encode(
                texts,
                batch_size=32,
                normalize_embeddings=True,
                show_progress_bar=False,
            )
            self.method = "sbert"
            self.model_name = model_name
        except Exception as exc:
            logger.warning("Embedding build failed; falling back to TF-IDF: %s", exc)
            self.embeddings = None
            self.method = "tfidf"

    def _build_tfidf(self) -> None:
        texts = [c.text for c in self.chunks]
        self.vectorizer = TfidfVectorizer(
            max_features=5000,
            ngram_range=(1, 2),
        )
        try:
            tfidf_matrix = self.vectorizer.fit_transform(texts)
            self.embeddings = tfidf_matrix.toarray()
            self.method = "tfidf"
        except ValueError:
            self.embeddings = None
            self.method = "tfidf"

    def _expand_query(self, query: str) -> List[str]:
        """Expand query with synonyms and related terms for better recall."""
        expansions = {
            "leave": ["vacation", "time off", "holiday", "absence", "PTO"],
            "salary": ["pay", "payroll", "compensation", "wage", "income"],
            "employee": ["staff", "worker", "team member", "colleague"],
            "insurance": ["coverage", "policy", "medical", "health", "claim"],
            "policy": ["rules", "guidelines", "procedure", "regulation"],
            "onboarding": ["joining", "new hire", "orientation", "induction"],
            "performance": ["appraisal", "evaluation", "rating", "score"],
        }

        expanded = [query]
        query_lower = query.lower()
        for key, synonyms in expansions.items():
            if key in query_lower:
                expanded.extend(synonyms)

        return list(set(expanded))

    def _bm25_score(self, query: str, text: str, avg_doc_len: float) -> float:
        """Calculate BM25 score for keyword matching."""
        k1 = 1.5
        b = 0.75

        query_terms = query.lower().split()
        text_lower = text.lower()
        text_len = len(text_lower.split())

        doc_freq = Counter()
        for term in query_terms:
            doc_freq[term] = text_lower.count(term)

        score = 0.0
        for term in query_terms:
            tf = doc_freq.get(term, 0)
            if tf > 0:
                idf = 1.0
                numerator = tf * (k1 + 1)
                denominator = tf + k1 * (1 - b + b * (text_len / avg_doc_len))
                score += idf * (numerator / denominator)

        return score

    def query(
        self, query_text: str, top_k: int = 5, min_score: float = 0.2
    ) -> List[dict]:
        """Enhanced query with multi-method retrieval and re-ranking."""
        if not query_text:
            return []

        if not self.chunks or self.embeddings is None:
            self.load_or_build()
        if not self.chunks or self.embeddings is None:
            return []

        expanded_queries = self._expand_query(query_text)

        avg_doc_len = np.mean([len(c.text.split()) for c in self.chunks]) or 100

        semantic_scores = self._get_semantic_scores(expanded_queries[0])

        bm25_scores = np.array(
            [
                self._bm25_score(query_text, chunk.text, avg_doc_len)
                for chunk in self.chunks
            ]
        )

        if bm25_scores.max() > 0:
            bm25_scores = bm25_scores / bm25_scores.max()

        combined_scores = 0.7 * semantic_scores + 0.3 * bm25_scores

        top_indices = np.argsort(combined_scores)[::-1][: top_k * 2]

        results = []
        for idx in top_indices:
            score = float(combined_scores[idx])
            if score < min_score * 0.5:
                continue
            chunk = self.chunks[int(idx)]
            results.append(
                {
                    "text": chunk.text,
                    "source": chunk.source,
                    "score": round(score, 3),
                }
            )
            if len(results) >= top_k:
                break

        return results

    def _get_semantic_scores(self, query_text: str) -> np.ndarray:
        """Get semantic similarity scores."""
        if self.method == "sbert":
            try:
                if self._embedder is None:
                    from sentence_transformers import SentenceTransformer

                    self._embedder = SentenceTransformer(
                        self.model_name or "all-MiniLM-L6-v2"
                    )
                q_emb = self._embedder.encode(
                    [query_text],
                    normalize_embeddings=True,
                    show_progress_bar=False,
                )
                scores = np.dot(self.embeddings, np.array(q_emb).T).squeeze()
            except Exception:
                return np.zeros(len(self.chunks))
        else:
            if not self.vectorizer:
                return np.zeros(len(self.chunks))
            q_vec = self.vectorizer.transform([query_text])
            scores = cosine_similarity(self.embeddings, q_vec).squeeze()

        if scores.ndim == 0:
            scores = np.array([scores])

        if scores.max() > 0:
            scores = scores / scores.max()

        return scores


_RAG_STORE: Optional[EnhancedRAGStore] = None


def get_rag_store() -> EnhancedRAGStore:
    global _RAG_STORE
    if _RAG_STORE is None:
        _RAG_STORE = EnhancedRAGStore()
        _RAG_STORE.load_or_build()
    return _RAG_STORE
