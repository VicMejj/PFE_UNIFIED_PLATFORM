import os
import re
import logging
import requests
from django.conf import settings
from ai_services.models import ChatbotConversation, ChatbotMessage
from ai_services.services.rag_store import get_rag_store

logger = logging.getLogger(__name__)


class ChatbotEngine:
    """
    Mejj — AI Assistant for the Unified HR & Insurance Platform.

    Model fallback chain (best → most available):
        1. llama-3.3-70b-versatile   — best quality, generous free limit
        2. llama-3.1-8b-instant      — ultra fast fallback
        3. mixtral-8x7b-32768        — strong alternative
        4. gemma2-9b-it              — last resort
    """

    API_URL = "https://api.groq.com/openai/v1/chat/completions"
    MODEL_CHAIN = [
        "llama-3.3-70b-versatile",
        "llama-3.1-8b-instant",
        "mixtral-8x7b-32768",
        "gemma2-9b-it",
    ]

    BOT_NAME = "Mejj"

    # ─────────────────────────────────────────────
    # INIT
    # ─────────────────────────────────────────────
    def __init__(self):
        self.hf_api_key = (
            os.getenv("GROQ_API_KEY")
            or getattr(settings, "GROQ_API_KEY", None)
            or os.getenv("HUGGINGFACE_API_KEY")
            or getattr(settings, "HF_API_KEY", None)
        )
        self.headers = {
            "Authorization": f"Bearer {self.hf_api_key}",
            "Content-Type": "application/json",
        }

    # ─────────────────────────────────────────────
    # GREETING DETECTION
    # ─────────────────────────────────────────────
    def _is_greeting(self, text: str) -> bool:
        """Detect simple greetings that should not trigger RAG context."""
        text = text.strip().lower()
        greeting_patterns = [
            r"^(hi|hey|hello|hiya|howdy|greetings|salut|bonjour|salam|مرحبا|أهلا)[\s!.,]*$",
            r"^(good\s?(morning|afternoon|evening|day))[\s!.,]*$",
            r"^(what'?s?\s?up|sup|yo)[\s!.,]*$",
        ]
        for pattern in greeting_patterns:
            if re.match(pattern, text, re.IGNORECASE):
                return True
        # Short messages (≤4 words) that start with a greeting word
        words = text.split()
        if len(words) <= 4 and words and words[0] in {"hi", "hey", "hello", "hiya", "yo", "sup"}:
            return True
        return False

    # ─────────────────────────────────────────────
    # MAIN ENTRY POINT
    # ─────────────────────────────────────────────
    def process_message(
        self,
        message: str,
        session_id: str,
        auth_header: str | None = None,
        user_roles: list[str] | None = None,
    ) -> dict:
        """
        Full pipeline: receive a user message, persist it, run AI, return response.
        Returns dict: { response, intent, entities, model_used }
        """
        original_text = message.strip()
        if not original_text:
            return {"response": "Please send a message.", "intent": "empty", "entities": {}, "model_used": None}

        # 1. Get or create conversation
        conversation, _ = ChatbotConversation.objects.get_or_create(
            session_uuid=session_id,
            defaults={"user_id": 0},
        )

        # 2. Save user message
        ChatbotMessage.objects.create(
            conversation=conversation,
            sender="USER",
            text=original_text,
        )

        # 3. Extract entities & update memory
        entities = self.extract_entities(original_text)
        if entities:
            self.update_memory(conversation, entities)

        # 4. Build context
        memory = self.get_memory(conversation)
        history = self.get_history(conversation)

        # 5. Handle greeting — no RAG/platform context needed
        if self._is_greeting(original_text):
            name = memory.get("name", "")
            greeting = f"Hello{', ' + name if name else ''}! I'm {self.BOT_NAME}, your HR & platform assistant. How can I help you today?"
            ai_response = greeting
            model_used = "rule-based"

        # 6. Handle data-aware queries via Laravel API
        elif self._is_role_stats_query(original_text):
            counts, error = self._get_role_stats_data(auth_header, user_roles)
            if error:
                ai_response = error
                model_used = "laravel-api"
            else:
                if self.hf_api_key:
                    platform_context = self._get_platform_context(auth_header, user_roles)
                    rag_context = self._get_rag_context(original_text)
                    facts_context = self._format_role_stats_facts(counts)
                    messages = self.build_messages(
                        original_text,
                        memory,
                        history,
                        platform_context=platform_context,
                        rag_context=rag_context,
                        facts_context=facts_context,
                        user_roles=user_roles,
                    )
                    ai_response, model_used = self.call_llm(messages)
                else:
                    ai_response = self._format_role_stats_sentence(counts)
                    model_used = "laravel-api"

        elif self._is_employee_count_query(original_text):
            ai_response = self._get_employee_count_response(auth_header)
            model_used = "laravel-api"

        else:
            platform_context = self._get_platform_context(auth_header, user_roles)
            rag_context = self._get_rag_context(original_text)
            messages = self.build_messages(
                original_text,
                memory,
                history,
                platform_context=platform_context,
                rag_context=rag_context,
                user_roles=user_roles,
            )
            ai_response, model_used = self.call_llm(messages)

        ai_response = self._normalize_response(ai_response)

        # 7. Persist bot response
        ChatbotMessage.objects.create(
            conversation=conversation,
            sender="BOT",
            text=ai_response,
        )

        logger.info("Session %s | model=%s | user=%s", session_id, model_used, original_text[:60])

        return {
            "response": ai_response,
            "intent": "ai_generated",
            "entities": entities,
            "model_used": model_used,
        }

    def _normalize_response(self, text: str) -> str:
        """Normalize whitespace in responses to avoid escaped \\n in API clients."""
        single_line = os.getenv("CHATBOT_SINGLE_LINE", "true").lower() in {"1", "true", "yes"}
        if not single_line:
            return text.strip()
        return " ".join(text.split())

    def _is_employee_count_query(self, text: str) -> bool:
        text = text.lower()
        triggers = [
            "how many employees",
            "number of employees",
            "employee count",
            "total employees",
            "how much employees",
        ]
        return any(t in text for t in triggers)

    def _is_role_stats_query(self, text: str) -> bool:
        text = text.lower()
        triggers = [
            "users stats",
            "user stats",
            "users by role",
            "role stats",
            "how many admins",
            "how many managers",
            "how many hr",
            "how many rh",
            "how many employees by role",
            "admins and managers",
            "normal employees",
            "users and roles",
        ]
        return any(t in text for t in triggers)

    def _get_role_stats_data(
        self,
        auth_header: str | None,
        user_roles: list[str] | None,
    ) -> tuple[dict | None, str | None]:
        if not auth_header:
            return None, "Please sign in so I can check user role statistics."

        roles_normalized = {r.lower() for r in (user_roles or []) if r}
        if "admin" not in roles_normalized:
            return None, "Only administrators can access user role statistics."

        base_url = self._get_laravel_base_url()
        role_map = [
            ("admin", "Admins"),
            ("manager", "Managers"),
            ("rh", "RH"),
            ("user", "Employees"),
        ]

        counts = {}
        for role_key, label in role_map:
            try:
                response = requests.get(
                    f"{base_url}/api/core/users-by-role/{role_key}",
                    headers={"Authorization": auth_header, "Accept": "application/json"},
                    timeout=10,
                )
            except Exception:
                return None, "I couldn't reach the user roles service right now. Please try again shortly."

            if response.status_code == 401:
                return None, "Please log in again so I can access user role statistics."
            if response.status_code == 403:
                return None, "Only administrators can access user role statistics."
            if response.status_code != 200:
                return None, "I ran into a problem fetching user role statistics. Please try again later."

            try:
                payload = response.json()
            except Exception:
                return None, "I ran into a problem reading user role statistics. Please try again later."

            data = payload.get("data", {})
            total = data.get("total_users")
            counts[label] = total

        return counts, None

    def _format_role_stats_facts(self, counts: dict) -> str:
        return "\n".join([
            "User role counts:",
            f"- Admins: {counts.get('Admins', 'N/A')}",
            f"- Managers: {counts.get('Managers', 'N/A')}",
            f"- RH: {counts.get('RH', 'N/A')}",
            f"- Employees: {counts.get('Employees', 'N/A')}",
        ])

    def _format_role_stats_sentence(self, counts: dict) -> str:
        return (
            "Here are the current user counts by role: "
            f"Admins: {counts.get('Admins', 'N/A')}, "
            f"Managers: {counts.get('Managers', 'N/A')}, "
            f"RH: {counts.get('RH', 'N/A')}, "
            f"Employees: {counts.get('Employees', 'N/A')}."
        )

    def _get_employee_count_response(self, auth_header: str | None) -> str:
        if not auth_header:
            return "Please sign in so I can check the employee count."

        base_url = self._get_laravel_base_url()

        try:
            response = requests.get(
                f"{base_url}/api/employees",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
        except Exception:
            return "I couldn't reach the employee service right now. Please try again shortly."

        if response.status_code == 401:
            return "Please log in again so I can access employee data."
        if response.status_code == 403:
            return "You don't have permission to view employee counts. Please contact an administrator."
        if response.status_code != 200:
            return "I ran into a problem fetching employee data. Please try again later."

        try:
            payload = response.json()
        except Exception:
            return "I ran into a problem reading employee data. Please try again later."

        total = self._extract_total_from_employees_payload(payload)
        if total is None:
            total = self._fetch_total_from_homepage(auth_header, base_url)

        if total is None:
            return "I couldn't determine the employee count from the response."

        return f"You currently have {total} employees in the platform."

    def _extract_total_from_employees_payload(self, payload: dict | None) -> int | None:
        if not isinstance(payload, dict):
            return None

        data = payload.get("data")
        if isinstance(data, dict):
            meta = data.get("meta")
            if isinstance(meta, dict) and meta.get("total") is not None:
                return meta.get("total")
            if data.get("total") is not None:
                return data.get("total")
            items = data.get("data")
            if isinstance(items, list):
                return len(items)
        if isinstance(data, list):
            return len(data)

        return None

    def _fetch_total_from_homepage(self, auth_header: str, base_url: str) -> int | None:
        try:
            response = requests.get(
                f"{base_url}/api/homepage",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
        except Exception:
            return None

        if response.status_code != 200:
            return None

        try:
            payload = response.json()
        except Exception:
            return None

        data = payload.get("data", {})
        if not isinstance(data, dict):
            return None

        stats = data.get("statistics", {})
        if isinstance(stats, dict) and stats.get("total_employees") is not None:
            return stats.get("total_employees")

        return None

    def _get_laravel_base_url(self) -> str:
        return (
            getattr(settings, "LARAVEL_API_URL", None)
            or os.getenv("LARAVEL_API_URL")
            or "http://localhost:8000"
        )

    def _get_platform_context(self, auth_header: str | None, user_roles: list[str] | None) -> str:
        parts = []

        if user_roles:
            roles_str = ", ".join(sorted({r.lower() for r in user_roles if r}))
            if roles_str:
                parts.append(f"User roles: {roles_str}.")

        static_context = self._load_static_platform_context()
        if static_context:
            parts.append(static_context)

        live_context = self._fetch_live_platform_context(auth_header)
        if live_context:
            parts.append(live_context)

        context = "\n".join(parts).strip()
        if len(context) > 1500:
            context = context[:1500].rstrip() + "..."
        return context

    def _get_rag_context(self, query: str) -> str:
        """
        Retrieve relevant knowledge base chunks for the query.
        Returns plain text only — NO file paths, NO source labels.
        """
        enabled = os.getenv("RAG_ENABLED", "true").lower() in {"1", "true", "yes"}
        if not enabled:
            return ""

        top_k = int(os.getenv("RAG_TOP_K", "3"))
        min_score = float(os.getenv("RAG_MIN_SCORE", "0.45"))
        max_chars = int(os.getenv("RAG_MAX_CONTEXT_CHARS", "1500"))

        try:
            store = get_rag_store()
            results = store.query(query, top_k=top_k, min_score=min_score)
        except Exception:
            return ""

        if not results:
            return ""

        lines = []
        for item in results:
            text = item.get("text", "").strip()
            if text:
                lines.append(text)

        context = "\n\n".join(lines).strip()
        if len(context) > max_chars:
            context = context[:max_chars].rstrip() + "..."
        return context

    def _load_static_platform_context(self) -> str:
        base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
        path = os.path.join(base_dir, "knowledge", "platform_facts.md")
        if not os.path.exists(path):
            return ""
        try:
            with open(path, "r", encoding="utf-8") as handle:
                return handle.read().strip()
        except Exception:
            return ""

    def _fetch_live_platform_context(self, auth_header: str | None) -> str:
        if not auth_header:
            return ""

        base_url = self._get_laravel_base_url()
        parts = []

        settings_payload = self._safe_get_json(
            f"{base_url}/api/core/settings",
            auth_header,
        )
        if settings_payload:
            data = settings_payload.get("data", {})
            if isinstance(data, dict):
                company = data.get("company", {})
                system = data.get("system", {})
                modules = data.get("modules", {})

                if isinstance(company, dict):
                    company_bits = [
                        company.get("name"),
                        company.get("email"),
                        company.get("phone"),
                        company.get("address"),
                    ]
                    company_bits = [c for c in company_bits if c]
                    if company_bits:
                        parts.append(f"Company: {', '.join(company_bits)}.")

                if isinstance(system, dict):
                    sys_bits = []
                    if system.get("timezone"):
                        sys_bits.append(f"timezone={system.get('timezone')}")
                    if system.get("locale"):
                        sys_bits.append(f"locale={system.get('locale')}")
                    if system.get("currency"):
                        sys_bits.append(f"currency={system.get('currency')}")
                    if sys_bits:
                        parts.append(f"System: {', '.join(sys_bits)}.")

                if isinstance(modules, dict):
                    enabled = [name for name, enabled in modules.items() if enabled]
                    if enabled:
                        parts.append(f"Enabled modules: {', '.join(enabled)}.")

        homepage_payload = self._safe_get_json(
            f"{base_url}/api/homepage",
            auth_header,
        )
        if homepage_payload:
            data = homepage_payload.get("data", {})
            if isinstance(data, dict):
                stats = data.get("statistics", {})
                if isinstance(stats, dict):
                    total = stats.get("total_employees")
                    active = stats.get("active_employees")
                    on_leave = stats.get("on_leave_employees")
                    new_month = stats.get("new_employees_this_month")
                    stats_bits = []
                    if total is not None:
                        stats_bits.append(f"total employees={total}")
                    if active is not None:
                        stats_bits.append(f"active={active}")
                    if on_leave is not None:
                        stats_bits.append(f"on leave={on_leave}")
                    if new_month is not None:
                        stats_bits.append(f"new this month={new_month}")
                    if stats_bits:
                        parts.append(f"Employee stats: {', '.join(stats_bits)}.")

                modules = data.get("modules", [])
                if isinstance(modules, list):
                    names = [m.get("name") for m in modules if isinstance(m, dict) and m.get("name")]
                    if names:
                        parts.append(f"Platform modules: {', '.join(names)}.")

        return "\n".join(parts).strip()

    def _safe_get_json(self, url: str, auth_header: str) -> dict | None:
        try:
            response = requests.get(
                url,
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
        except Exception:
            return None

        if response.status_code != 200:
            return None

        try:
            return response.json()
        except Exception:
            return None

    # ─────────────────────────────────────────────
    # MEMORY SYSTEM
    # ─────────────────────────────────────────────
    def extract_entities(self, text: str) -> dict:
        """
        Extract name and job role from plain text using regex.
        """
        entities = {}

        # Name: "my name is X" / "I'm X" / "I am X"
        name_match = re.search(
            r"\b(?:my name is|i am|i'm)\s+([A-Z][a-z]+)",
            text,
            re.IGNORECASE,
        )
        if name_match:
            entities["name"] = name_match.group(1).capitalize()

        # Role/job title
        role_match = re.search(
            r"\b(?:i am|i'm)\s+(?:a|an|the)?\s*([\w]+(?:\s+[\w]+){0,2})\b",
            text,
            re.IGNORECASE,
        )
        if role_match:
            potential_role = role_match.group(1).strip()
            skip_words = {"the", "a", "an", "also", "just", "really", "very", "here", "new"}
            name_stored = entities.get("name", "").lower()
            if (
                potential_role.lower() != name_stored
                and potential_role.lower() not in skip_words
                and len(potential_role) > 2
            ):
                entities["role"] = potential_role

        return entities

    def update_memory(self, conversation, entities: dict) -> None:
        memory = conversation.memory or {}
        memory.update(entities)
        conversation.memory = memory
        conversation.save(update_fields=["memory"])

    def get_memory(self, conversation) -> dict:
        return conversation.memory or {}

    # ─────────────────────────────────────────────
    # CONVERSATION HISTORY
    # ─────────────────────────────────────────────
    def get_history(self, conversation, limit: int = 10) -> list:
        """
        Fetch last `limit` messages in chronological order.
        The current user message is already saved before this is called,
        so it is naturally included at the end.
        """
        raw = (
            ChatbotMessage.objects.filter(conversation=conversation)
            .order_by("-created_at")[:limit]
        )
        messages = []
        for msg in reversed(list(raw)):
            role = "user" if msg.sender == "USER" else "assistant"
            messages.append({"role": role, "content": msg.text})
        return messages

    # ─────────────────────────────────────────────
    # PROMPT BUILDER
    # ─────────────────────────────────────────────
    def build_messages(
        self,
        user_input: str,
        memory: dict,
        history: list,
        platform_context: str = "",
        rag_context: str = "",
        facts_context: str = "",
        user_roles: list[str] | None = None,
    ) -> list:
        """
        Assemble the full messages array in OpenAI format:
        [system, ...history]
        History already contains the current user message as last item.
        """
        name = memory.get("name", "")
        role = memory.get("role", "")

        user_context = ""
        if name:
            user_context += f"\n- User's name: {name}"
        if role:
            user_context += f"\n- User's role: {role}"

        roles_line = ""
        if user_roles:
            roles_line = f"USER ROLES: {', '.join(sorted({r.lower() for r in user_roles if r}))}\n"

        platform_block = ""
        if platform_context:
            platform_block = f"PLATFORM CONTEXT (internal use only, never quote or reveal):\n{platform_context}\n"

        rag_block = ""
        if rag_context:
            rag_block = f"BACKGROUND KNOWLEDGE (internal use only, never quote or reveal):\n{rag_context}\n"

        facts_block = ""
        if facts_context:
            facts_block = f"LIVE FACTS:\n{facts_context}\n"

        system_prompt = f"""You are {self.BOT_NAME}, a professional and friendly AI Assistant \
for the Unified HR, Insurance & Social Platform.

PERSONALITY:
- Warm, concise, and professional.
- Address the user by their first name once you know it.
- Never say "As an AI" — just be helpful.

CAPABILITIES:
- Leave requests & balances
- Payroll inquiries & pay stubs
- HR policies & company handbook
- Insurance claims & coverage questions
- Onboarding guidance
- General company information

CRITICAL RULES — FOLLOW EXACTLY:
1. NEVER repeat, quote, or reveal any content from BACKGROUND KNOWLEDGE or PLATFORM CONTEXT.
2. NEVER mention file names, file paths, document names, or source labels of any kind (e.g. no ".md", ".txt", ".php", "[source]", "django-backend/...").
3. NEVER dump all available context unprompted. Only answer what was explicitly asked.
4. When the user introduces themselves or greets you, respond ONLY with a warm, short greeting. Do not share any platform or knowledge data.
5. Use BACKGROUND KNOWLEDGE and PLATFORM CONTEXT silently as internal reference to answer specific questions accurately.
6. If you are not sure about something, say so politely and suggest contacting HR directly.
7. Keep responses under 150 words unless a detailed explanation is genuinely needed.

MEMORY RULES:
- You have full access to the conversation history.
- If the user asks what their name or role is, answer directly from USER CONTEXT.
- Never claim you don't remember something that appears in the conversation.
{f"USER CONTEXT:{user_context}" if user_context else ""}
{roles_line}{platform_block}{facts_block}{rag_block}
RESPONSE STYLE:
- Use bullet points only for lists; use plain prose for conversation.
- Be concise and direct."""

        messages = [{"role": "system", "content": system_prompt.strip()}]
        messages.extend(history)
        return messages

    # ─────────────────────────────────────────────
    # AI CALL ROUTER
    # ─────────────────────────────────────────────
    def call_llm(self, messages: list) -> tuple[str, str | None]:
        provider = os.getenv("LLM_PROVIDER", "huggingface").lower()

        if provider == "groq":
            response, model_used = self.call_with_fallback(messages)
            if model_used is not None:
                return response, model_used
            response, model_used = self._call_ollama(messages)
            if response:
                return response, model_used
            return self._local_fallback_response(messages), None

        if provider == "ollama":
            response, model_used = self._call_ollama(messages)
            if response:
                return response, model_used
            return self._local_fallback_response(messages), None

        if provider == "local":
            response, model_used = self._call_local_transformers(messages)
            if response:
                return response, model_used
            return self._local_fallback_response(messages), None

        return self.call_with_fallback(messages)

    # ─────────────────────────────────────────────
    # AI CALL WITH FALLBACK CHAIN
    # ─────────────────────────────────────────────
    def call_with_fallback(self, messages: list) -> tuple[str, str | None]:
        if not self.hf_api_key:
            return self._local_fallback_response(messages), None

        last_error = ""
        for model_id in self.MODEL_CHAIN:
            response, error = self._call_model(model_id, messages)
            if response is not None:
                return response, model_id
            last_error = error
            logger.warning("Model %s failed: %s — trying next.", model_id, error)

        logger.error("All models in fallback chain failed. Last error: %s", last_error)
        return (
            "I'm having trouble connecting right now. Please try again in a moment, "
            "or contact HR directly at your company helpdesk.",
            None,
        )

    def _local_fallback_response(self, messages: list) -> str:
        provider = os.getenv("LLM_PROVIDER", "huggingface").lower()
        user_message = messages[-1].get("content", "").strip().lower() if messages else ""

        if not user_message:
            return "Please send a message so I can help."

        if any(greet in user_message for greet in ["hi", "hello", "hey"]):
            return f"Hello! I'm {self.BOT_NAME}, your HR & platform assistant. How can I help you today?"

        if provider == "groq":
            return "The Groq API key isn't configured. Please set GROQ_API_KEY to enable full AI responses."
        if provider == "ollama":
            return "The Ollama model isn't responding. Please make sure Ollama is running and the model is loaded."
        if provider == "local":
            return "The local model isn't available. Please check the configured model."
        return (
            f"I'm {self.BOT_NAME}, running in limited mode. "
            "For full AI responses, please configure a valid API key."
        )

    def _call_model(self, model_id: str, messages: list) -> tuple[str | None, str]:
        payload = {
            "model": model_id,
            "messages": messages,
            "max_tokens": 1024,
            "temperature": 0.6,
            "stream": False,
        }

        try:
            resp = requests.post(
                self.API_URL,
                headers=self.headers,
                json=payload,
                timeout=30,
            )

            if resp.status_code == 200:
                data = resp.json()
                choices = data.get("choices", [])
                if choices:
                    content = choices[0]["message"]["content"].strip()
                    return content, ""
                return None, "Empty choices in response"

            if resp.status_code == 503:
                return None, "503 Service Unavailable (model loading)"

            error_preview = resp.text[:200]
            return None, f"HTTP {resp.status_code}: {error_preview}"

        except requests.exceptions.Timeout:
            return None, "Request timed out after 30s"
        except requests.exceptions.ConnectionError as e:
            return None, f"Connection error: {e}"
        except Exception as e:
            return None, f"Unexpected error: {e}"

    # ─────────────────────────────────────────────
    # OLLAMA LOCAL PROVIDER
    # ─────────────────────────────────────────────
    def _call_ollama(self, messages: list) -> tuple[str | None, str | None]:
        base_url = os.getenv("OLLAMA_BASE_URL", "http://localhost:11434")
        model = os.getenv("OLLAMA_MODEL", "llama3.1")
        timeout = int(os.getenv("OLLAMA_TIMEOUT", "120"))
        payload = {
            "model": model,
            "messages": messages,
            "stream": False,
        }
        try:
            resp = requests.post(
                f"{base_url}/api/chat",
                json=payload,
                timeout=timeout,
            )
        except requests.exceptions.ConnectionError:
            logger.warning("Ollama not reachable at %s", base_url)
            return None, None
        except Exception as exc:
            logger.warning("Ollama error: %s", exc)
            return None, None

        if resp.status_code != 200:
            logger.warning("Ollama returned %s: %s", resp.status_code, resp.text[:200])
            return None, None

        try:
            data = resp.json()
            content = data.get("message", {}).get("content", "").strip()
            if not content:
                return None, None
            return content, model
        except Exception:
            return None, None

    # ─────────────────────────────────────────────
    # LOCAL TRANSFORMERS PROVIDER
    # ─────────────────────────────────────────────
    def _call_local_transformers(self, messages: list) -> tuple[str | None, str | None]:
        try:
            from transformers import pipeline
        except Exception as exc:
            logger.warning("Transformers not available: %s", exc)
            return None, None

        model = os.getenv("LOCAL_LLM_MODEL", "google/flan-t5-small")
        max_new_tokens = int(os.getenv("LOCAL_LLM_MAX_NEW_TOKENS", "180"))
        prompt = self._flatten_messages(messages)

        try:
            generator = pipeline("text2text-generation", model=model)
            result = generator(prompt, max_new_tokens=max_new_tokens)
            if result:
                text = result[0].get("generated_text", "").strip()
                return text or None, model
        except Exception as exc:
            logger.warning("Local transformers model failed: %s", exc)
            return None, None

        return None, None

    def _flatten_messages(self, messages: list) -> str:
        lines = []
        for msg in messages:
            role = msg.get("role", "user")
            content = msg.get("content", "")
            if not content:
                continue
            lines.append(f"{role.upper()}: {content}")
        lines.append("ASSISTANT:")
        return "\n".join(lines)