import os
import re
import json
import logging
import requests
from datetime import datetime
from typing import Optional, Dict, List, Any, Tuple
from django.conf import settings

logger = logging.getLogger(__name__)

INTENT_EXTRA_CONTEXT_KEY = "last_intent"


class SmartChatbotEngine:
    """
    Mejj - Intelligent HR Assistant

    A truly dynamic, AI-powered chatbot that:
    - Understands natural language intents
    - Queries real database data
    - Remembers conversation context
    - Falls back to LLM for complex queries
    - Provides helpful, contextual responses
    """

    BOT_NAME = "Mejj"
    API_URL = "https://api.groq.com/openai/v1/chat/completions"
    MODEL_CHAIN = [
        "llama-3.3-70b-versatile",
        "llama-3.1-8b-instant",
        "mixtral-8x7b-32768",
    ]

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

    # ═══════════════════════════════════════════════════════════════
    # MAIN ENTRY POINT
    # ═══════════════════════════════════════════════════════════════
    def process_message(
        self,
        message: str,
        session_id: str,
        auth_header: Optional[str] = None,
        user_roles: Optional[List[str]] = None,
        user_id: Optional[int] = None,
    ) -> Dict[str, Any]:
        """Process a user message and return a smart response."""

        original_text = message.strip()
        if not original_text:
            return self._create_response(
                "Please send a message so I can help you.",
                intent="empty",
                model_used=None,
            )

        # Import here to avoid circular imports
        from ai_services.models import ChatbotConversation, ChatbotMessage

        # Get or create conversation
        owner_id = int(user_id) if user_id is not None else 0
        conversation, _created = ChatbotConversation.objects.get_or_create(
            session_uuid=session_id,
            defaults={"user_id": owner_id},
        )
        if owner_id and conversation.user_id != owner_id:
            conversation.user_id = owner_id
            conversation.save(update_fields=["user_id"])

        # Save user message
        ChatbotMessage.objects.create(
            conversation=conversation,
            sender="USER",
            text=original_text,
        )

        # Get conversation context (memory + history)
        memory = self._get_memory(conversation)
        history = self._get_history(conversation)

        # Determine the intent and extract entities
        intent, entities, follow_up_context = self._analyze_message(
            original_text, memory, history
        )

        # Route to appropriate handler
        response_text, model_used = self._route_intent(
            intent=intent,
            message=original_text,
            entities=entities,
            memory=memory,
            history=history,
            auth_header=auth_header,
            user_roles=user_roles,
            follow_up_context=follow_up_context,
        )

        # Normalize and save bot response
        response_text = self._normalize_response(response_text)

        ChatbotMessage.objects.create(
            conversation=conversation,
            sender="BOT",
            text=response_text,
        )

        # Update memory with intent context
        self._update_memory(conversation, intent, entities, original_text)

        logger.info(
            "Session %s | intent=%s | model=%s | msg='%s'",
            session_id,
            intent,
            model_used,
            original_text[:50],
        )

        return {
            "response": response_text,
            "intent": intent,
            "entities": entities,
            "model_used": model_used,
        }

    # ═══════════════════════════════════════════════════════════════
    # INTENT ANALYSIS - Smart & Dynamic
    # ═══════════════════════════════════════════════════════════════
    def _analyze_message(
        self, message: str, memory: Dict, history: List[Dict]
    ) -> Tuple[str, Dict, Optional[Dict]]:
        """Analyze message to determine intent and extract entities."""

        text = message.strip()
        text_lower = text.lower()
        entities = {}

        # Get previous context
        last_intent = memory.get(INTENT_EXTRA_CONTEXT_KEY)
        follow_up_context = {"previous_intent": last_intent} if last_intent else None

        # === GREETINGS ===
        if self._is_greeting(text):
            return "greeting", entities, None

        # === GOODBYES ===
        if self._is_farewell(text):
            return "farewell", entities, None

        # === THANKS ===
        if self._is_thanks(text):
            return "thanks", entities, None

        # === HELP REQUESTS ===
        if self._is_help_request(text):
            return "help", entities, None

        # === EMPLOYEE SEARCH ===
        employee_name = self._extract_employee_name(text)
        if employee_name:
            entities["employee_name"] = employee_name
            return "employee_search", entities, follow_up_context

        # === STANDALONE NAME (follow-up) ===
        if self._is_standalone_name(text) and last_intent == "employee_search":
            entities["employee_name"] = text.strip()
            return "employee_search", entities, follow_up_context

        # === PERFORMANCE/SCORES ===
        if self._is_performance_query(text):
            employee_name = self._extract_employee_name(text)
            if employee_name:
                entities["employee_name"] = employee_name
            return "performance_query", entities, follow_up_context

        # === POLICY QUESTIONS (check BEFORE leave - "sick leave policy", "leave policy") ===
        if self._is_policy_query(text):
            return "policy", entities, follow_up_context

        # === LEAVE BALANCE ===
        if self._is_leave_balance_query(text):
            return "leave_balance", entities, follow_up_context

        # === LEAVE REQUEST/DAYS ===
        if self._is_leave_days_query(text):
            return "leave_days", entities, follow_up_context

        # === ATTENDANCE ===
        if self._is_attendance_query(text):
            return "attendance", entities, follow_up_context

        # === DEPARTMENT STATS ===
        if self._is_department_stats_query(text):
            dept = self._extract_department(text)
            if dept:
                entities["department"] = dept
            return "department_stats", entities, follow_up_context

        # === ROLE STATS ===
        if self._is_role_stats_query(text):
            return "role_stats", entities, follow_up_context

        # === EMPLOYEE COUNT ===
        if self._is_employee_count_query(text):
            return "employee_count", entities, follow_up_context

        # === PAYROLL/SALARY ===
        if self._is_payroll_query(text):
            return "payroll", entities, follow_up_context

        # === INSURANCE/BENEFITS ===
        if self._is_benefits_query(text):
            employee_name = self._extract_employee_name(text)
            if employee_name:
                entities["employee_name"] = employee_name
            return "benefits", entities, follow_up_context

        # === WHO IS / EMPLOYEE DETAILS ===
        if self._is_who_is_query(text):
            employee_name = self._extract_employee_name(text)
            if employee_name:
                entities["employee_name"] = employee_name
            return "employee_details", entities, follow_up_context

        # === DEFAULT - Use LLM ===
        return "general", entities, follow_up_context

    # ═══════════════════════════════════════════════════════════════
    # INTENT HELPERS
    # ═══════════════════════════════════════════════════════════════
    def _is_greeting(self, text: str) -> bool:
        text_lower = text.lower().strip()
        greetings = [
            "hi",
            "hello",
            "hey",
            "hiya",
            "howdy",
            "greetings",
            "good morning",
            "good afternoon",
            "good evening",
            "what's up",
            "whats up",
            "sup",
            "how are you",
            "salut",
            "bonjour",
            "salam",
            "مرحبا",
            "أهلا",
        ]
        return any(text_lower == g or text_lower.startswith(g + " ") for g in greetings)

    def _is_farewell(self, text: str) -> bool:
        text_lower = text.lower().strip()
        farewells = [
            "bye",
            "goodbye",
            "see you",
            "talk later",
            "catch you later",
            "later",
        ]
        return any(text_lower == f or text_lower.startswith(f) for f in farewells)

    def _is_thanks(self, text: str) -> bool:
        text_lower = text.lower().strip()
        return any(
            t in text_lower
            for t in ["thank", "thanks", "thx", "appreciate", "grateful"]
        )

    def _is_help_request(self, text: str) -> bool:
        text_lower = text.lower()
        return any(
            p in text_lower
            for p in [
                "help me",
                "can you help",
                "what can you do",
                "help with",
                "need help",
            ]
        )

    def _is_standalone_name(self, text: str) -> bool:
        text = text.strip()
        # Match "First Last" pattern like "Montassar Monta"
        return bool(re.match(r"^[A-Z][a-z]+\s+[A-Z][a-z]+$", text))

    def _extract_employee_name(self, text: str) -> Optional[str]:
        """Extract employee name from various formats."""
        patterns = [
            r"""['"]([^'"]+)['"]""",  # "John Doe" or 'John Doe'
            r"(?:named|called|is)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)",
            r"(?:employee|staff|member)\s+(?:named|called)?\s*([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)",
            r"(?:who is|whois)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)",
            r"\b([A-Z][a-z]+\s+[A-Z][a-z]+)\b",
        ]

        for pattern in patterns:
            match = re.search(pattern, text)
            if match:
                name = match.group(1).strip()
                # Avoid matching common words
                skip_words = {
                    "how are",
                    "what is",
                    "who is",
                    "thank you",
                    "good morning",
                    "good afternoon",
                }
                if name.lower() not in skip_words:
                    return name
        return None

    def _is_performance_query(self, text: str) -> bool:
        text_lower = text.lower()
        return any(
            p in text_lower
            for p in [
                "performance",
                "score",
                "rating",
                "appraisal",
                "evaluation",
                "考核",
                "成绩",
                "评分",
                "绩效",
                "how well",
            ]
        )

    def _is_leave_balance_query(self, text: str) -> bool:
        text_lower = text.lower()
        return any(
            p in text_lower
            for p in [
                "leave balance",
                "vacation balance",
                "how many days left",
                "my leaves",
                "remaining leave",
                "available leave",
            ]
        )

    def _is_leave_days_query(self, text: str) -> bool:
        text_lower = text.lower()
        return any(
            p in text_lower
            for p in [
                "leave days",
                "possible days",
                "when can i take leave",
                "book leave",
                "request leave",
                "apply for leave",
                "leave types",
                "leave options",
            ]
        )

    def _is_attendance_query(self, text: str) -> bool:
        text_lower = text.lower()
        return any(
            p in text_lower
            for p in [
                "attendance",
                "absent",
                "present",
                "who is absent",
                "attendance today",
                "who came",
                "late",
            ]
        )

    def _is_department_stats_query(self, text: str) -> bool:
        text_lower = text.lower()
        return any(
            p in text_lower
            for p in [
                "department",
                "how many in",
                "employees in",
                "it department",
                "hr department",
                "sales department",
            ]
        )

    def _is_role_stats_query(self, text: str) -> bool:
        text_lower = text.lower()
        return any(
            p in text_lower
            for p in [
                "admins",
                "managers",
                "role stats",
                "users by role",
                "how many admins",
                "how many managers",
                "how many hr",
            ]
        )

    def _is_employee_count_query(self, text: str) -> bool:
        text_lower = text.lower()
        return any(
            p in text_lower
            for p in [
                "employee count",
                "total employees",
                "how many employees",
                "number of employees",
                "staff count",
            ]
        )

    def _is_payroll_query(self, text: str) -> bool:
        text_lower = text.lower()
        return any(
            p in text_lower
            for p in [
                "salary",
                "pay",
                "payroll",
                "pay stub",
                "compensation",
                "bonus",
                "income",
                "how much",
            ]
        )

    def _is_benefits_query(self, text: str) -> bool:
        text_lower = text.lower()
        return any(
            p in text_lower
            for p in [
                "insurance",
                "benefits",
                "coverage",
                "medical",
                "health insurance",
                "claim",
                "policy",
            ]
        )

    def _is_policy_query(self, text: str) -> bool:
        text_lower = text.lower()
        return any(
            p in text_lower
            for p in [
                "policy",
                "policies",
                "rules",
                "guidelines",
                "handbook",
                "procedure",
                "explain",
                "sick leave",
                "annual leave",
                "leave policy",
                "vacation policy",
                "working hours",
                "dress code",
                "remote work",
                "overtime",
            ]
        )

    def _is_who_is_query(self, text: str) -> bool:
        text_lower = text.lower()
        return any(p in text_lower for p in ["who is", "whois", "details about"])

    def _extract_department(self, text: str) -> Optional[str]:
        departments = [
            "it",
            "hr",
            "finance",
            "sales",
            "marketing",
            "engineering",
            "operations",
            "support",
        ]
        text_lower = text.lower()
        for dept in departments:
            if dept in text_lower:
                return dept.title()
        return None

    # ═══════════════════════════════════════════════════════════════
    # ROUTE TO APPROPRIATE HANDLER
    # ═══════════════════════════════════════════════════════════════
    def _route_intent(
        self,
        intent: str,
        message: str,
        entities: Dict,
        memory: Dict,
        history: List[Dict],
        auth_header: Optional[str],
        user_roles: Optional[List[str]],
        follow_up_context: Optional[Dict],
    ) -> Tuple[str, str]:
        """Route to the appropriate handler based on intent."""

        handlers = {
            "greeting": self._handle_greeting,
            "farewell": self._handle_farewell,
            "thanks": self._handle_thanks,
            "help": self._handle_help,
            "employee_search": self._handle_employee_search,
            "employee_details": self._handle_employee_details,
            "performance_query": self._handle_performance,
            "leave_balance": self._handle_leave_balance,
            "leave_days": self._handle_leave_days,
            "attendance": self._handle_attendance,
            "department_stats": self._handle_department_stats,
            "role_stats": self._handle_role_stats,
            "employee_count": self._handle_employee_count,
            "payroll": self._handle_payroll,
            "benefits": self._handle_benefits,
            "policy": self._handle_policy,
            "general": self._handle_general,
        }

        handler = handlers.get(intent, self._handle_general)

        try:
            return handler(
                message=message,
                entities=entities,
                memory=memory,
                history=history,
                auth_header=auth_header,
                user_roles=user_roles,
                follow_up_context=follow_up_context,
            )
        except Exception as e:
            logger.error(f"Handler error for intent {intent}: {e}")
            return self._handle_error(intent), "error"

    # ═══════════════════════════════════════════════════════════════
    # INTENT HANDLERS
    # ═══════════════════════════════════════════════════════════════
    def _handle_greeting(self, **kwargs) -> Tuple[str, str]:
        name = kwargs.get("memory", {}).get("name", "")
        if name:
            return (
                f"Hello {name}! Good to see you again. How can I help you today?",
                "rule-based",
            )
        return (
            f"Hello! I'm {self.BOT_NAME}, your HR & platform assistant. How can I help you today?",
            "rule-based",
        )

    def _handle_farewell(self, **kwargs) -> Tuple[str, str]:
        return (
            "Goodbye! Feel free to come back anytime you need help. Have a great day!",
            "rule-based",
        )

    def _handle_thanks(self, **kwargs) -> Tuple[str, str]:
        return (
            "You're welcome! Is there anything else I can help you with?",
            "rule-based",
        )

    def _handle_help(self, **kwargs) -> Tuple[str, str]:
        return (
            "I can help you with many things:\n"
            "- Employee information (search, details, performance)\n"
            "- Leave requests and balances\n"
            "- Attendance and schedules\n"
            "- Payroll and compensation\n"
            "- Insurance and benefits\n"
            "- Company policies\n"
            "- And much more!\n\n"
            "What would you like to know?"
        ), "rule-based"

    def _handle_employee_search(self, **kwargs) -> Tuple[str, str]:
        """Search for an employee by name."""
        auth_header = kwargs.get("auth_header")
        if not auth_header:
            return "Please sign in so I can search for employees.", "auth-required"

        employee_name = kwargs.get("entities", {}).get("employee_name")
        if not employee_name:
            return (
                "Could you provide the employee name you're looking for?",
                "clarification",
            )

        base_url = self._get_laravel_base_url()

        try:
            response = requests.get(
                f"{base_url}/api/employees",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
        except Exception as e:
            logger.warning(f"Employee search failed: {e}")
            return (
                "I couldn't reach the employee service. Please try again shortly.",
                "error",
            )

        if response.status_code != 200:
            return (
                "I had trouble fetching employee data. Please try again later.",
                "error",
            )

        try:
            payload = response.json()
        except Exception:
            return (
                "I had trouble reading the employee data. Please try again later.",
                "error",
            )

        employees = payload.get("data", {})
        if isinstance(employees, dict):
            employees = employees.get("data", [])

        if not employees:
            return "No employees found in the system.", "empty"

        # Search for employee
        search_name = employee_name.lower()
        found = None
        for emp in employees:
            emp_name = (emp.get("name") or "").lower()
            if search_name in emp_name or emp_name in search_name:
                found = emp
                break

        if not found:
            return (
                f"I couldn't find an employee named '{employee_name}'. Would you like me to search for a different name?",
                "not-found",
            )

        # Format response
        name = found.get("name", employee_name)
        role = found.get("role") or found.get("position")
        dept = found.get("department") or found.get("dept")
        email = found.get("email")

        response = f"Yes! I found {name}:\n"
        if role:
            response += f"- Position: {role}\n"
        if dept:
            response += f"- Department: {dept}\n"
        if email:
            response += f"- Email: {email}\n"

        return response.strip(), "employee-api"

    def _handle_employee_details(self, **kwargs) -> Tuple[str, str]:
        """Get detailed employee information - same as search."""
        return self._handle_employee_search(**kwargs)

    def _handle_performance(self, **kwargs) -> Tuple[str, str]:
        """Get employee performance data."""
        auth_header = kwargs.get("auth_header")
        if not auth_header:
            return "Please sign in so I can look up performance data.", "auth-required"

        employee_name = kwargs.get("entities", {}).get("employee_name")

        base_url = self._get_laravel_base_url()

        try:
            response = requests.get(
                f"{base_url}/api/employees",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
        except Exception:
            return (
                "I couldn't reach the employee service. Please try again shortly.",
                "error",
            )

        if response.status_code != 200:
            return (
                "I had trouble fetching employee data. Please try again later.",
                "error",
            )

        try:
            payload = response.json()
        except Exception:
            return (
                "I had trouble reading the employee data. Please try again later.",
                "error",
            )

        employees = payload.get("data", {})
        if isinstance(employees, dict):
            employees = employees.get("data", [])

        if not employees:
            return "No employees found.", "empty"

        # Search for employee
        search_name = (employee_name or "").lower()
        found = None
        if search_name:
            for emp in employees:
                emp_name = (emp.get("name") or "").lower()
                if search_name in emp_name or emp_name in search_name:
                    found = emp
                    break

        if not found and not employee_name:
            return (
                "Which employee's performance would you like to know? Please provide a name.",
                "clarification",
            )

        if not found:
            return f"I couldn't find an employee named '{employee_name}'.", "not-found"

        name = found.get("name")
        perf = found.get("performance_score") or found.get("performance")
        attendance = found.get("attendance_rate") or found.get("attendance")
        overall = found.get("overall_score") or found.get("score")

        response = f"Here's the performance data for {name}:\n"
        if perf is not None:
            response += f"- Performance Score: {perf}/5\n"
        if attendance is not None:
            response += f"- Attendance: {attendance}%\n"
        if overall is not None:
            response += f"- Overall Score: {overall}/100\n"

        if not perf and not attendance and not overall:
            return (
                f"I found {name} in the system, but they don't have performance data recorded yet.",
                "no-data",
            )

        return response.strip(), "employee-api"

    def _handle_leave_balance(self, **kwargs) -> Tuple[str, str]:
        """Get user's leave balance."""
        auth_header = kwargs.get("auth_header")
        if not auth_header:
            return "Please sign in to check your leave balance.", "auth-required"

        base_url = self._get_laravel_base_url()

        try:
            response = requests.get(
                f"{base_url}/api/leave-balance",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
        except Exception:
            return (
                "I couldn't reach the leave service. Please try again shortly.",
                "error",
            )

        if response.status_code == 200:
            try:
                data = response.json()
                balances = data.get("data", {})
                if balances:
                    response = "Your leave balances:\n"
                    for leave_type, balance in balances.items():
                        response += f"- {leave_type}: {balance} days\n"
                    return response.strip(), "leave-api"
            except Exception:
                pass

        # Try leave requests endpoint
        try:
            response = requests.get(
                f"{base_url}/api/leave-requests",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
            if response.status_code == 200:
                return (
                    "To check your leave balance, please go to the Leave section in your dashboard. There you can see your available days and leave types.",
                    "leave-api",
                )
        except Exception:
            pass

        return (
            "Please check your leave balance in the Leave section of your dashboard.",
            "leave-api",
        )

    def _handle_leave_days(self, **kwargs) -> Tuple[str, str]:
        """Get available leave types and days."""
        auth_header = kwargs.get("auth_header")
        if not auth_header:
            return "Please sign in to view leave options.", "auth-required"

        base_url = self._get_laravel_base_url()

        try:
            response = requests.get(
                f"{base_url}/api/leave-types",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
        except Exception:
            return (
                "I couldn't reach the leave service. Please try again shortly.",
                "error",
            )

        if response.status_code == 200:
            try:
                payload = response.json()
                leave_types = payload.get("data", [])
                if leave_types:
                    response = "Here are your available leave types:\n"
                    for lt in leave_types:
                        name = lt.get("name", "Unknown")
                        max_days = lt.get("max_days") or lt.get("annual_quota", "N/A")
                        response += f"- {name}: up to {max_days} days/year\n"
                    response += (
                        "\nTo request leave, go to the Leave section in your dashboard."
                    )
                    return response.strip(), "leave-api"
            except Exception:
                pass

        return (
            "To see available leave days and request time off, please visit the Leave section in your dashboard.",
            "leave-api",
        )

    def _handle_attendance(self, **kwargs) -> Tuple[str, str]:
        """Get attendance information."""
        auth_header = kwargs.get("auth_header")
        if not auth_header:
            return "Please sign in to view attendance data.", "auth-required"

        base_url = self._get_laravel_base_url()

        try:
            response = requests.get(
                f"{base_url}/api/attendance/today",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
        except Exception:
            pass

        try:
            response = requests.get(
                f"{base_url}/api/homepage",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
            if response.status_code == 200:
                data = response.json()
                stats = data.get("data", {}).get("statistics", {})
                active = stats.get("active_employees")
                on_leave = stats.get("on_leave_employees")

                response = "Here's today's attendance overview:\n"
                if active is not None:
                    response += f"- Active employees: {active}\n"
                if on_leave is not None:
                    response += f"- On leave: {on_leave}\n"
                return response.strip(), "attendance-api"
        except Exception:
            pass

        return (
            "For detailed attendance information, please check the Attendance section in your dashboard.",
            "attendance-api",
        )

    def _handle_department_stats(self, **kwargs) -> Tuple[str, str]:
        """Get department employee counts."""
        auth_header = kwargs.get("auth_header")
        if not auth_header:
            return "Please sign in to view department statistics.", "auth-required"

        base_url = self._get_laravel_base_url()

        try:
            response = requests.get(
                f"{base_url}/api/employees",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
        except Exception:
            return (
                "I couldn't reach the employee service. Please try again shortly.",
                "error",
            )

        if response.status_code != 200:
            return (
                "I had trouble fetching employee data. Please try again later.",
                "error",
            )

        try:
            payload = response.json()
        except Exception:
            return "I had trouble reading the employee data.", "error"

        employees = payload.get("data", {})
        if isinstance(employees, dict):
            employees = employees.get("data", [])

        if not employees:
            return "No employees found.", "empty"

        # Count by department
        dept_counts = {}
        for emp in employees:
            dept = emp.get("department") or emp.get("dept") or "Unknown"
            dept_counts[dept] = dept_counts.get(dept, 0) + 1

        response = "Employees by department:\n"
        for dept, count in sorted(dept_counts.items()):
            response += f"- {dept}: {count}\n"

        return response.strip(), "employee-api"

    def _handle_role_stats(self, **kwargs) -> Tuple[str, str]:
        """Get user role statistics."""
        auth_header = kwargs.get("auth_header")
        user_roles = kwargs.get("user_roles", [])

        if not auth_header:
            return "Please sign in to view user statistics.", "auth-required"

        roles_normalized = {r.lower() for r in user_roles if r}
        if "admin" not in roles_normalized:
            return "Only administrators can access user role statistics.", "forbidden"

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
                    headers={
                        "Authorization": auth_header,
                        "Accept": "application/json",
                    },
                    timeout=10,
                )
                if response.status_code == 200:
                    data = response.json().get("data", {})
                    counts[label] = data.get("total_users", 0)
            except Exception:
                pass

        if counts:
            response = "Users by role:\n"
            for label, count in counts.items():
                response += f"- {label}: {count}\n"
            return response.strip(), "laravel-api"

        return (
            "I couldn't retrieve user role statistics. Please try again later.",
            "error",
        )

    def _handle_employee_count(self, **kwargs) -> Tuple[str, str]:
        """Get total employee count."""
        auth_header = kwargs.get("auth_header")
        if not auth_header:
            return "Please sign in to view employee count.", "auth-required"

        base_url = self._get_laravel_base_url()

        try:
            response = requests.get(
                f"{base_url}/api/homepage",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
            if response.status_code == 200:
                data = response.json()
                total = (
                    data.get("data", {}).get("statistics", {}).get("total_employees")
                )
                if total:
                    return (
                        f"There are {total} employees in the platform.",
                        "laravel-api",
                    )
        except Exception:
            pass

        try:
            response = requests.get(
                f"{base_url}/api/employees",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
            if response.status_code == 200:
                data = response.json()
                employees = data.get("data", {})
                if isinstance(employees, dict):
                    total = employees.get("meta", {}).get("total") or len(
                        employees.get("data", [])
                    )
                else:
                    total = len(employees)
                return f"There are {total} employees in the system.", "laravel-api"
        except Exception:
            pass

        return (
            "I couldn't retrieve the employee count. Please try again later.",
            "error",
        )

    def _handle_payroll(self, **kwargs) -> Tuple[str, str]:
        """Handle payroll inquiries."""
        return (
            "For payroll and salary information, please check the Payroll section in your dashboard. "
            "There you can view your pay stubs, salary details, and compensation information. "
            "If you have specific questions, please contact HR directly."
        ), "info"

    def _handle_benefits(self, **kwargs) -> Tuple[str, str]:
        """Handle insurance/benefits inquiries."""
        auth_header = kwargs.get("auth_header")
        employee_name = kwargs.get("entities", {}).get("employee_name")

        if employee_name:
            return (
                f"To view {employee_name}'s benefits and insurance details, please check the Employee profile in the admin dashboard, or contact HR for detailed information.",
                "info",
            )

        return (
            "For insurance and benefits information, please check the Insurance section in your dashboard. "
            "There you can view your coverage, submit claims, and see your benefit details. "
            "If you have specific questions about your benefits, please contact HR."
        ), "info"

    def _handle_policy(self, **kwargs) -> Tuple[str, str]:
        """
        Handle policy questions - ALWAYS returns useful content, never fails.
        Tries RAG/LLM first for best answer, but has comprehensive fallback.
        """
        message = kwargs.get("message", "")
        auth_header = kwargs.get("auth_header")
        user_roles = kwargs.get("user_roles")

        message_lower = message.lower()

        # Try RAG + LLM first for best response
        try:
            rag_context = self._get_rag_context(message)
            if rag_context:
                platform_context = self._get_platform_context(auth_header, user_roles)
                memory = kwargs.get("memory", {})
                history = kwargs.get("history", [])

                messages = self._build_messages(
                    user_input=message,
                    memory=memory,
                    history=history,
                    platform_context=platform_context,
                    rag_context=rag_context,
                    user_roles=user_roles,
                )
                response, model = self._call_llm(messages)
                if response and len(response) > 50:  # Only use if we got good response
                    logger.info(f"Policy query handled by LLM with RAG context")
                    return response, model
        except Exception as e:
            logger.warning(f"Policy RAG/LLM failed, using fallback: {e}")

        # Detect specific policy type from message
        if "sick" in message_lower:
            policy_content = self._get_sick_leave_policy()
        elif "annual" in message_lower or "vacation" in message_lower:
            policy_content = self._get_annual_leave_policy()
        elif "remote" in message_lower or "work from home" in message_lower:
            policy_content = self._get_remote_work_policy()
        elif "attendance" in message_lower or "late" in message_lower:
            policy_content = self._get_attendance_policy()
        elif "code of conduct" in message_lower or "behavior" in message_lower:
            policy_content = self._get_code_of_conduct_policy()
        elif "data" in message_lower or "security" in message_lower:
            policy_content = self._get_data_security_policy()
        elif "harassment" in message_lower or "discrimination" in message_lower:
            policy_content = self._get_anti_harassment_policy()
        else:
            policy_content = self._get_general_policies()

        return policy_content, "policy"

    def _get_sick_leave_policy(self) -> Tuple[str, str]:
        return (
            "SICK LEAVE POLICY:\n\n"
            "• Paid sick leave is provided as per your employment contract\n"
            "• Medical certificate required for sick leave exceeding 2 consecutive working days\n"
            "• Employees must notify their manager as soon as possible (preferably before start of shift)\n"
            "• Unused sick days: Check your contract - some companies allow rollover, others forfeiture\n"
            "• If sick leave exhausted, unpaid leave may be granted subject to management approval\n"
            "• Doctor's appointment during work hours requires prior approval\n\n"
            "To check your sick leave balance, go to the Leave section in your dashboard."
        ), "policy"

    def _get_annual_leave_policy(self) -> Tuple[str, str]:
        return (
            "ANNUAL LEAVE / VACATION POLICY:\n\n"
            "• Annual leave entitlement is based on your tenure and employment type\n"
            "• Standard: 18-24 days per year for full-time employees\n"
            "• Request submission: At least 2 weeks in advance for planned leave\n"
            "• Emergency leave: Can be requested with shorter notice subject to approval\n"
            "• Carry-over: Maximum 5 days can be carried to next year (varies by company)\n"
            "• Blackout periods: Some departments may have restrictions during peak times\n\n"
            "To request leave, go to Leave section in your dashboard."
        ), "policy"

    def _get_remote_work_policy(self) -> Tuple[str, str]:
        return (
            "REMOTE WORK POLICY:\n\n"
            "• Remote work eligibility depends on your role and manager approval\n"
            "• Typically 2-3 days per week from home is the standard allowance\n"
            "• Must have reliable internet and appropriate workspace at home\n"
            "• Core hours: May be required to be online during certain hours\n"
            "• Equipment: Company provides laptop; internet allowance may be provided\n"
            "• Must maintain productivity and attend virtual meetings\n\n"
            "Discuss remote work options with your direct manager."
        ), "policy"

    def _get_attendance_policy(self) -> Tuple[str, str]:
        return (
            "ATTENDANCE POLICY:\n\n"
            "• Employees must arrive on time for their scheduled shift\n"
            "• Late arrival: More than 15 minutes late requires notification\n"
            "• Unexcused absence: May result in disciplinary action\n"
            "• Overtime: Must be pre-approved by manager\n"
            "• Time tracking: Clock in/out using the attendance system\n"
            "• Remote employees: Must log start/end time daily\n\n"
            "Contact HR for specific attendance guidelines for your department."
        ), "policy"

    def _get_code_of_conduct_policy(self) -> Tuple[str, str]:
        return (
            "CODE OF CONDUCT POLICY:\n\n"
            "• Treat all colleagues with respect and professionalism\n"
            "• No harassment, discrimination, or bullying of any kind\n"
            "• Maintain confidentiality of company and employee information\n"
            "• Use company resources responsibly (email, internet, equipment)\n"
            "• Report any violations to HR or management\n"
            "• Conflict of interest must be disclosed\n"
            "• Social media: Don't speak negatively about company or colleagues publicly\n\n"
            "See Employee Handbook for complete Code of Conduct."
        ), "policy"

    def _get_data_security_policy(self) -> Tuple[str, str]:
        return (
            "DATA SECURITY POLICY:\n\n"
            "• Never share login credentials with anyone\n"
            "• Lock your computer when away from desk\n"
            "• Don't download unauthorized software\n"
            "• Report any suspicious emails (phishing attempts) to IT\n"
            "• Customer/employee data must be encrypted\n"
            "• Don't store company data on personal devices without approval\n"
            "• When leaving company, return all company equipment and data\n\n"
            "Contact IT Security for security awareness training."
        ), "policy"

    def _get_anti_harassment_policy(self) -> Tuple[str, str]:
        return (
            "ANTI-HARASSMENT POLICY:\n\n"
            "• Zero tolerance for harassment of any kind (verbal, physical, visual)\n"
            "• Includes harassment based on: gender, race, religion, age, disability, sexual orientation\n"
            "• Reporting: Report to HR, manager, or use anonymous hotline\n"
            "• Investigation: All complaints will be investigated confidentially\n"
            "• Retaliation: Strictly prohibited against anyone who reports in good faith\n"
            "• Training: All employees must complete mandatory anti-harassment training\n\n"
            "If you experience or witness harassment, contact HR immediately."
        ), "policy"

    def _get_general_policies(self) -> Tuple[str, str]:
        return (
            "COMPANY POLICIES OVERVIEW:\n\n"
            "SICK LEAVE:\n"
            "• Paid sick leave as per contract; medical cert required for 2+ days\n\n"
            "ANNUAL LEAVE:\n"
            "• 18-24 days/year for full-time employees; request 2 weeks in advance\n\n"
            "ATTENDANCE:\n"
            "• Be on time; notify manager of any absence\n\n"
            "REMOTE WORK:\n"
            "• Subject to manager approval; typically 2-3 days/week\n\n"
            "CODE OF CONDUCT:\n"
            "• Professional behavior, no harassment, maintain confidentiality\n\n"
            "DATA SECURITY:\n"
            "• Protect company data; don't share credentials\n\n"
            "ANTI-HARASSMENT:\n"
            "• Zero tolerance; report violations to HR\n\n"
            "For detailed information, check your Employee Handbook or contact HR."
        ), "policy"

    def _handle_general(self, **kwargs) -> Tuple[str, str]:
        """Handle general queries using LLM."""
        message = kwargs.get("message", "")
        memory = kwargs.get("memory", {})
        history = kwargs.get("history", [])
        auth_header = kwargs.get("auth_header")
        user_roles = kwargs.get("user_roles")

        # Get platform context
        platform_context = self._get_platform_context(auth_header, user_roles)
        rag_context = self._get_rag_context(message)

        # Build messages for LLM
        messages = self._build_messages(
            user_input=message,
            memory=memory,
            history=history,
            platform_context=platform_context,
            rag_context=rag_context,
            user_roles=user_roles,
        )

        # Call LLM
        return self._call_llm(messages)

    def _handle_error(self, intent: str) -> str:
        """Handle unexpected errors gracefully."""
        return f"I encountered an issue processing your request. Could you try rephrasing your question? I'm here to help with employee information, leave, payroll, policies, and more."

    # ═══════════════════════════════════════════════════════════════
    # LLM & API CALLS
    # ═══════════════════════════════════════════════════════════════
    def _call_llm(self, messages: list) -> Tuple[str, str]:
        """Call LLM with fallback chain."""

        if not self.hf_api_key:
            return self._smart_fallback(messages), None

        for model_id in self.MODEL_CHAIN:
            try:
                response = self._call_model(model_id, messages)
                if response:
                    return response, model_id
            except Exception as e:
                logger.warning(f"Model {model_id} failed: {e}")
                continue

        return self._smart_fallback(messages), None

    def _call_model(self, model_id: str, messages: list) -> Optional[str]:
        """Call a specific LLM model."""
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
                    return choices[0]["message"]["content"].strip()
        except Exception as e:
            logger.warning(f"LLM call failed: {e}")

        return None

    def _smart_fallback(self, messages: list) -> str:
        """Smart fallback when LLM is unavailable."""
        user_msg = messages[-1].get("content", "").lower() if messages else ""

        if any(g in user_msg for g in ["hi", "hello", "hey"]):
            return f"Hello! I'm {self.BOT_NAME}, your HR assistant. How can I help you today?"

        if "leave" in user_msg:
            return "For leave requests, please use the Leave section in your dashboard."

        if "employee" in user_msg or "staff" in user_msg:
            return "I can help you find employee information. Please sign in and try again, or contact HR."

        if "policy" in user_msg:
            return "For policies, please check the Employee Handbook in your dashboard or contact HR."

        if "payroll" in user_msg or "salary" in user_msg:
            return "For payroll information, please check the Payroll section or contact HR."

        if "insurance" in user_msg or "benefits" in user_msg:
            return "For insurance and benefits, please check the Insurance section in your dashboard."

        return (
            f"I'm {self.BOT_NAME}, here to help with employee info, leave, payroll, policies, and more. "
            "Could you try rephrasing your question?"
        )

    def _get_platform_context(
        self, auth_header: Optional[str], user_roles: Optional[List[str]]
    ) -> str:
        """Get platform context from Laravel API."""
        parts = []

        if user_roles:
            roles_str = ", ".join(sorted({r.lower() for r in user_roles if r}))
            if roles_str:
                parts.append(f"User roles: {roles_str}.")

        if not auth_header:
            return "\n".join(parts)

        base_url = self._get_laravel_base_url()

        try:
            response = requests.get(
                f"{base_url}/api/homepage",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                timeout=10,
            )
            if response.status_code == 200:
                data = response.json().get("data", {})
                stats = data.get("statistics", {})
                if stats:
                    parts.append(f"Platform stats: {stats}")
        except Exception:
            pass

        return "\n".join(parts)

    def _get_rag_context(self, query: str) -> str:
        """Get relevant knowledge base context."""
        try:
            from ai_services.services.rag_store import get_rag_store

            store = get_rag_store()
            results = store.query(query, top_k=2, min_score=0.3)
            if results:
                return "\n\n".join([r.get("text", "") for r in results])
        except Exception:
            pass
        return ""

    def _build_messages(
        self,
        user_input: str,
        memory: dict,
        history: list,
        platform_context: str = "",
        rag_context: str = "",
        user_roles: Optional[List[str]] = None,
    ) -> list:
        """Build messages for LLM."""

        name = memory.get("name", "")
        user_context = f"\n- User's name: {name}" if name else ""

        roles_line = ""
        if user_roles:
            roles_line = f"USER ROLES: {', '.join(sorted({r.lower() for r in user_roles if r}))}\n"

        system_prompt = f"""You are {self.BOT_NAME}, a professional and friendly AI Assistant for the Unified HR, Insurance & Social Platform.

PERSONALITY:
- Warm, concise, and professional
- Address user by name when known
- Never say "As an AI" — just be helpful

CAPABILITIES:
- Employee information and search
- Leave requests and balances
- Payroll and compensation
- HR policies and guidelines
- Insurance and benefits
- General company information

CRITICAL RULES:
1. Use PLATFORM CONTEXT and BACKGROUND KNOWLEDGE internally, never reveal sources
2. Keep responses under 150 words unless detailed explanation is genuinely needed
3. If unsure, say so and suggest contacting HR
4. Be helpful and contextual{user_context}
{roles_line}
{platform_context}
{rag_context}
RESPONSE STYLE: Use bullet points for lists, plain prose for conversation."""

        messages = [{"role": "system", "content": system_prompt.strip()}]

        # Add recent history (last 5 messages)
        for msg in history[-5:]:
            messages.append(msg)

        return messages

    # ═══════════════════════════════════════════════════════════════
    # MEMORY & HISTORY
    # ═══════════════════════════════════════════════════════════════
    def _get_memory(self, conversation) -> Dict:
        """Get conversation memory."""
        return conversation.memory or {}

    def _get_history(self, conversation, limit: int = 10) -> List[Dict]:
        """Get conversation history."""
        from ai_services.models import ChatbotMessage

        raw = ChatbotMessage.objects.filter(conversation=conversation).order_by(
            "-created_at"
        )[:limit]
        messages = []
        for msg in reversed(list(raw)):
            role = "user" if msg.sender == "USER" else "assistant"
            messages.append({"role": role, "content": msg.text})
        return messages

    def _update_memory(self, conversation, intent: str, entities: Dict, message: str):
        """Update conversation memory with context."""
        from datetime import datetime

        memory = conversation.memory or {}

        # Update intent context
        memory[INTENT_EXTRA_CONTEXT_KEY] = intent

        # Extract and store name if mentioned
        if "name" in message.lower() and "my name" in message.lower():
            name_match = re.search(
                r"(?:my name is|i am|i'm)\s+([A-Z][a-z]+)", message, re.IGNORECASE
            )
            if name_match:
                memory["name"] = name_match.group(1).capitalize()

        memory["last_interaction"] = datetime.now().isoformat()

        conversation.memory = memory
        conversation.save(update_fields=["memory"])

    # ═══════════════════════════════════════════════════════════════
    # UTILITIES
    # ═══════════════════════════════════════════════════════════════
    def _get_laravel_base_url(self) -> str:
        return (
            getattr(settings, "LARAVEL_API_URL", None)
            or os.getenv("LARAVEL_API_URL")
            or "http://localhost:8000"
        )

    def _normalize_response(self, text: str) -> str:
        single_line = os.getenv("CHATBOT_SINGLE_LINE", "true").lower() in {
            "1",
            "true",
            "yes",
        }
        if not single_line:
            return text.strip()
        return " ".join(text.split())

    def _create_response(
        self, text: str, intent: str = "general", model_used: Optional[str] = None
    ) -> Dict[str, Any]:
        return {
            "response": text,
            "intent": intent,
            "entities": {},
            "model_used": model_used,
        }
