import re
import logging
import requests
from django.conf import settings
from ai_services.models import ChatbotConversation, ChatbotMessage

logger = logging.getLogger(__name__)


class ChatbotEngine:
    """
    Mejj — AI Assistant for the Unified HR & Insurance Platform.

    Model fallback chain (best → most available):
        1. meta-llama/Llama-3.3-70B-Instruct:sambanova   — fast, high quality
        2. meta-llama/Llama-3.1-8B-Instruct:cerebras     — very fast, lighter
        3. Qwen/Qwen2.5-72B-Instruct:nebius              — strong alternative
        4. meta-llama/Llama-3.1-8B-Instruct:auto         — HF auto-picks provider
    """

    API_URL = "https://router.huggingface.co/v1/chat/completions"

    MODEL_CHAIN = [
        "meta-llama/Llama-3.3-70B-Instruct:sambanova",
        "meta-llama/Llama-3.1-8B-Instruct:cerebras",
        "Qwen/Qwen2.5-72B-Instruct:nebius",
        "meta-llama/Llama-3.1-8B-Instruct:auto",
    ]

    BOT_NAME = "Mejj"

    # ─────────────────────────────────────────────
    # INIT
    # ─────────────────────────────────────────────
    def __init__(self):
        self.hf_api_key = getattr(settings, "HF_API_KEY", None)
        self.headers = {
            "Authorization": f"Bearer {self.hf_api_key}",
            "Content-Type": "application/json",
        }

    # ─────────────────────────────────────────────
    # MAIN ENTRY POINT
    # ─────────────────────────────────────────────
    def process_message(self, message: str, session_id: str) -> dict:
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

        # 5. Build prompt
        messages = self.build_messages(original_text, memory, history)

        # 6. Call AI (with fallback chain)
        ai_response, model_used = self.call_with_fallback(messages)

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

    # ─────────────────────────────────────────────
    # MEMORY SYSTEM
    # ─────────────────────────────────────────────
    def extract_entities(self, text: str) -> dict:
        """
        Extract name and job role from plain text using regex.
        Extend this later with an NER model for better accuracy.
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

        # Role/job title — grabs 1–3 word phrase after "I am a/an" or "I'm a/an"
        role_match = re.search(
            r"\b(?:i am|i'm)\s+(?:a|an|the)?\s*([\w]+(?:\s+[\w]+){0,2})\b",
            text,
            re.IGNORECASE,
        )
        if role_match:
            potential_role = role_match.group(1).strip()
            # Don't overwrite name with itself and filter single common words
            skip_words = {"the", "a", "an", "also", "just", "really", "very", "here"}
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
    def build_messages(self, user_input: str, memory: dict, history: list) -> list:
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

        system_prompt = f"""You are {self.BOT_NAME}, a professional and friendly AI Assistant \
for the Unified HR, Insurance & Social Platform.

PERSONALITY:
- Warm, concise, and professional.
- Always address the user by their name once you know it.
- Never say "As an AI" — just be helpful.

CAPABILITIES:
- Leave requests & balances
- Payroll inquiries & pay stubs
- HR policies & company handbook
- Insurance claims & coverage questions
- Onboarding guidance
- General company information

MEMORY RULES:
- You have full access to the conversation history above.
- If the user asks what their name or role is, answer directly — it is in USER CONTEXT.
- Never say you don't remember something that appears in the conversation.
{f"USER CONTEXT:{user_context}" if user_context else ""}

RESPONSE STYLE:
- Keep answers focused and under 150 words unless a detailed explanation is needed.
- Use bullet points for lists, plain prose for conversation.
- If you cannot help with something, say so politely and suggest contacting HR directly."""

        messages = [{"role": "system", "content": system_prompt.strip()}]
        messages.extend(history)
        # Note: user_input is already the last item in history (saved before this call)
        return messages

    # ─────────────────────────────────────────────
    # AI CALL WITH FALLBACK CHAIN
    # ─────────────────────────────────────────────
    def call_with_fallback(self, messages: list) -> tuple[str, str | None]:
        """
        Try each model in MODEL_CHAIN in order.
        Returns (response_text, model_id_used).
        """
        if not self.hf_api_key:
            return "Configuration error: HF_API_KEY is not set in Django settings.", None

        last_error = ""
        for model_id in self.MODEL_CHAIN:
            response, error = self._call_model(model_id, messages)
            if response is not None:
                return response, model_id
            last_error = error
            logger.warning("Model %s failed: %s — trying next.", model_id, error)

        # All models failed
        logger.error("All models in fallback chain failed. Last error: %s", last_error)
        return (
            "I'm having trouble connecting right now. Please try again in a moment, "
            "or contact HR directly at your company helpdesk.",
            None,
        )

    def _call_model(self, model_id: str, messages: list) -> tuple[str | None, str]:
        """
        Single attempt to call one model.
        Returns (response_text, "") on success, (None, error_msg) on failure.
        """
        payload = {
            "model": model_id,
            "messages": messages,
            "max_tokens": 512,
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

            # Retriable server errors — don't log full body for 503
            if resp.status_code == 503:
                return None, "503 Service Unavailable (model loading)"

            # Non-retriable client errors (400, 401, 422) — log and skip
            error_preview = resp.text[:200]
            return None, f"HTTP {resp.status_code}: {error_preview}"

        except requests.exceptions.Timeout:
            return None, "Request timed out after 30s"
        except requests.exceptions.ConnectionError as e:
            return None, f"Connection error: {e}"
        except Exception as e:
            return None, f"Unexpected error: {e}"