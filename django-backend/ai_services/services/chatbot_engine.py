import os
import re
import json
import logging
import requests
from datetime import datetime, timedelta
from typing import Optional, Dict, List, Any, Tuple
from django.conf import settings
from django.utils import timezone

logger = logging.getLogger(__name__)

INTENT_EXTRA_CONTEXT_KEY = "last_intent"
WEEKDAY_TO_INDEX = {
    "monday": 0,
    "tuesday": 1,
    "wednesday": 2,
    "thursday": 3,
    "friday": 4,
    "saturday": 5,
    "sunday": 6,
}


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

        # === EMPLOYEE DIRECTORY / TEAM LISTS ===
        if self._is_employee_directory_query(text):
            entities["directory_filters"] = self._extract_directory_filters(text)
            return "employee_directory", entities, follow_up_context

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

        # === MATH/CALCULATION QUESTIONS (check BEFORE leave - "if I have 25 and take 5") ===
        if self._is_math_calculation(text):
            return "general", entities, follow_up_context

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
        text_lower = text.lower().strip()
        if text_lower in {"help", "help me", "can you help", "what can you do"}:
            return True
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
        request_markers = [
            "request",
            "apply",
            "book",
            "submit",
            "leave request",
            "time off request",
        ]
        explicit_balance_markers = [
            "balance",
            "remaining",
            "left",
            "available",
            "my leave",
            "my leaves",
        ]

        if any(marker in text_lower for marker in request_markers) and not any(
            marker in text_lower for marker in explicit_balance_markers
        ):
            return False

        if ("leave" in text_lower or "vacation" in text_lower) and re.search(
            r"\bhow\s+(many|much)\s+days\b", text_lower
        ):
            return True
        return any(
            p in text_lower
            for p in [
                "leave balance",
                "vacation balance",
                "how many days left",
                "how much days",
                "how many leave days",
                "how much leave",
                "days do i have",
                "days i can get",
                "my leaves",
                "my leave",
                "remaining leave",
                "available leave",
            ]
        )

    def _is_leave_days_query(self, text: str) -> bool:
        text_lower = text.lower()
        if any(token in text_lower for token in ["leave", "time off", "day off"]):
            if any(
                marker in text_lower
                for marker in [
                    "request",
                    "apply",
                    "book",
                    "submit",
                    "take leave",
                    "can i leave",
                    "can i take leave",
                    "leave request",
                    "time off",
                    "day off",
                    "next week",
                    "this week",
                    "tomorrow",
                    "today",
                    "next monday",
                    "next tuesday",
                    "next wednesday",
                    "next thursday",
                    "next friday",
                    "next saturday",
                    "next sunday",
                    "this monday",
                    "this tuesday",
                    "this wednesday",
                    "this thursday",
                    "this friday",
                    "this saturday",
                    "this sunday",
                ]
            ):
                return True
        return any(
            p in text_lower
            for p in [
                "leave days",
                "possible days",
                "when can i take leave",
                "book leave",
                "request leave",
                "leave request",
                "apply for leave",
                "leave types",
                "leave options",
            ]
        )

    def _is_employee_directory_query(self, text: str) -> bool:
        text_lower = text.lower()
        if self._is_who_is_query(text):
            return False

        if any(
            marker in text_lower
            for marker in ["how many", "count", "number of", "statistics", "stats"]
        ):
            return False

        list_markers = [
            "list",
            "show",
            "give me",
            "tell me",
            "names of",
            "name of",
            "who are",
            "who works in",
            "employee list",
            "staff list",
            "team members",
            "directory",
        ]
        group_markers = [
            "employee",
            "employees",
            "staff",
            "team",
            "developer",
            "developers",
            "engineer",
            "engineers",
            "manager",
            "managers",
            "admin",
            "admins",
            "hr",
            "rh",
            "department",
        ]

        return (
            any(marker in text_lower for marker in list_markers)
            and any(marker in text_lower for marker in group_markers)
        ) or bool(
            re.search(
                r"\b(who are the|who works in|list|show)\s+(developers?|engineers?|employees?|staff|team)\b",
                text_lower,
            )
        )

    def _extract_directory_filters(self, text: str) -> Dict[str, Any]:
        text_lower = text.lower()
        department = self._extract_department(text)

        filter_groups = [
            {
                "label": "developers",
                "triggers": [
                    "developer",
                    "developers",
                    "engineer",
                    "engineers",
                    "software",
                    "frontend",
                    "backend",
                    "full stack",
                    "fullstack",
                ],
                "designation_terms": [
                    "developer",
                    "engineer",
                    "software",
                    "frontend",
                    "backend",
                    "full stack",
                    "fullstack",
                ],
                "department_terms": ["engineering", "it", "technology", "development"],
            },
            {
                "label": "managers",
                "triggers": ["manager", "managers", "lead", "leads", "supervisor"],
                "designation_terms": ["manager", "lead", "supervisor"],
                "department_terms": [],
            },
            {
                "label": "HR team members",
                "triggers": ["hr", "human resources", "rh", "recruiter", "recruitment"],
                "designation_terms": ["hr", "human resources", "rh", "recruiter"],
                "department_terms": ["hr", "human resources", "rh"],
            },
            {
                "label": "admins",
                "triggers": ["admin", "admins", "administrator", "administrators"],
                "designation_terms": ["admin", "administrator"],
                "department_terms": [],
            },
        ]

        for group in filter_groups:
            if any(trigger in text_lower for trigger in group["triggers"]):
                return {
                    "label": group["label"],
                    "designation_terms": group["designation_terms"],
                    "department_terms": group["department_terms"],
                    "department": department,
                }

        label = (department + " team members") if department else "employees"
        return {
            "label": label,
            "designation_terms": [],
            "department_terms": [],
            "department": department,
        }

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
        if "by department" in text_lower or "department" in text_lower:
            return True
        if any(dept in text_lower for dept in WEEKDAY_TO_INDEX):
            return False
        return any(
            p in text_lower
            for p in [
                "how many in",
                "it department",
                "hr department",
                "sales department",
                "finance department",
                "marketing department",
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
        if re.search(r"\bhow\s+(many|much)\s+employees?\b", text_lower):
            return True
        return any(
            p in text_lower
            for p in [
                "employee count",
                "total employees",
                "how many employees",
                "how much employees",
                "number of employees",
                "staff count",
                "employees in the platform",
                "employees are in the platform",
                "staff in the platform",
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

    def _is_math_calculation(self, text: str) -> bool:
        """Detect math/calculation questions that might mention leave/days."""
        text_lower = text.lower()
        
        # Math indicators
        math_indicators = [
            "calculate",
            "how many remain",
            "how many left",
            "if i have",
            "what if",
            "how much would",
            "percentage",
            "%",
            "total would be",
            "remaining after",
            "left after",
            "minus",
            "plus",
            "times",
            "divide",
        ]
        
        # If it contains calculation keywords, it's a math question
        has_math = any(indicator in text_lower for indicator in math_indicators)
        
        if has_math:
            # Check it's not asking for actual leave management
            leave_action_keywords = [
                "request",
                "book",
                "submit",
                "apply for",
                "take leave",
                "balance",
                "available",
            ]
            has_leave_action = any(kw in text_lower for kw in leave_action_keywords)
            
            # If it's a calculation (has math) but NOT a leave action, treat as math
            return not has_leave_action
        
        return False

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
            "employee_directory": self._handle_employee_directory,
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
            logger.error("Handler error for intent %s: %s", intent, e)
            return self._handle_error(intent), "error"

    # ═══════════════════════════════════════════════════════════════
    # INTENT HANDLERS
    # ═══════════════════════════════════════════════════════════════
    def _handle_greeting(self, **kwargs) -> Tuple[str, str]:
        name = kwargs.get("memory", {}).get("name", "")
        # Validate name is legitimate (not an article or common word)
        excluded_words = {"the", "a", "an", "and", "or", "but", "if", "is", "am", "are"}
        if name and name.lower() not in excluded_words and len(name) > 2:
            return (
                "Hello " + name + "! Good to see you again. How can I help?",
                "rule-based",
            )
        return (
            "Hello! I'm " + self.BOT_NAME + ", your smart assistant. "
            "I can help with platform data, HR workflows, math, "
            "daily life, and general questions. What's on your mind?",
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
            "I can help you with many things:\n\n"
            "**Platform & HR:**\n"
            "- Employee information (search, team lists, details, performance)\n"
            "- Leave requests and balances\n"
            "- Attendance and schedules\n"
            "- Payroll and compensation\n"
            "- Insurance and benefits\n"
            "- Company policies\n"
            "- Live platform lookups (developers, departments, counts)\n\n"
            "**General Knowledge:**\n"
            "- Math calculations and conversions\n"
            "- Daily life tips and advice\n"
            "- Explanations and definitions\n"
            "- Coding and tech questions\n"
            "- Anything else you're curious about\n\n"
            "Examples:\n"
            "- Who are the developers?\n"
            "- What is 15% of 230?\n"
            "- How many employees are there?\n"
            "- What are some tips for time management?\n\n"
            "Ask me anything!"
        ), "rule-based"

    def _handle_employee_directory(self, **kwargs) -> Tuple[str, str]:
        """List employees or team members that match a requested group."""
        auth_header = kwargs.get("auth_header")
        if not auth_header:
            return (
                "Please sign in so I can search the employee directory.",
                "auth-required",
            )

        employees = self._collect_paginated_items("/api/employees", auth_header)
        if not employees:
            return "I couldn't find any employees in the directory right now.", "empty"

        departments = self._get_named_collection_map(
            "/api/organization/departments", auth_header
        )
        designations = self._get_named_collection_map(
            "/api/organization/designations", auth_header
        )

        filters = kwargs.get("entities", {}).get("directory_filters") or {}
        filtered = [
            employee
            for employee in employees
            if self._employee_matches_directory_filters(
                employee, departments, designations, filters
            )
        ]

        label = str(filters.get("label") or "employees")
        if not filtered:
            return (
                "I couldn't find any " + label + " in the employee directory.",
                "not-found",
            )

        preview_limit = 12
        lines = [
            self._format_employee_summary_line(
                employee,
                departments,
                designations,
                include_contact=False,
            )
            for employee in filtered[:preview_limit]
        ]

        response = (
            "I found " + str(len(filtered)) + " " + label + ":\n"
            + "\n".join(lines)
        )
        if len(filtered) > preview_limit:
            response += (
                "\n\nShowing first " + str(preview_limit) + ". "
                "Ask to see more."
            )
        else:
            response += (
                "\n\nI can show full profile for anyone on this list."
            )

        return response, "employee-api"

    def _handle_employee_search(self, **kwargs) -> Tuple[str, str]:
        """Search for an employee by name and show comprehensive details."""
        auth_header = kwargs.get("auth_header")
        if not auth_header:
            return "Please sign in so I can search for employees.", "auth-required"

        employee_name = kwargs.get("entities", {}).get("employee_name")
        if not employee_name:
            return (
                "Could you provide the employee name you're looking for?",
                "clarification",
            )

        employees = self._collect_paginated_items("/api/employees", auth_header)
        if not employees:
            return "No employees found in the system.", "empty"

        departments = self._get_named_collection_map(
            "/api/organization/departments", auth_header
        )
        designations = self._get_named_collection_map(
            "/api/organization/designations", auth_header
        )

        matches = self._find_employee_matches(employee_name, employees)

        if not matches:
            return (
                "I couldn't find an employee named '" + employee_name + "'. Would you like me to search for a different name?",
                "not-found",
            )

        if len(matches) > 1:
            lines = [
                self._format_employee_summary_line(
                    employee,
                    departments,
                    designations,
                    include_contact=False,
                )
                for employee in matches[:5]
            ]
            response = (
                "I found multiple employees matching '" + employee_name + "':\n"
                + "\n".join(lines)
                + "\n\nTell me which one you want and I will open their details."
            )
            return response, "employee-api"

        found = matches[0]
        employee_id = found.get("id")
        name = found.get("name", employee_name)
        
        # Build comprehensive employee profile
        response_lines = ["📋 **Employee Profile: " + name + "**\n"]
        
        # Basic Info
        response_lines.append("**Basic Information:**")
        basic_info = self._format_employee_summary_line(
            found,
            departments,
            designations,
            include_contact=True,
            include_identifier=True,
            include_phone=True,
        )
        response_lines.append(basic_info)
        
        # Add hire date if available
        if found.get("hired_on"):
            response_lines.append(f"• Hire Date: {found['hired_on']}")
        
        # Status
        status = found.get("status") or "Active"
        response_lines.append(f"• Status: {status}\n")
        
        # Get performance data
        if employee_id:
            performance = self._laravel_get_data(f"/api/employees/{employee_id}/performance", auth_header)
            if isinstance(performance, dict) and performance:
                response_lines.append("**Performance:**")
                if performance.get("overall_score"):
                    response_lines.append(f"• Overall Score: {performance['overall_score']}/100")
                if performance.get("last_review"):
                    response_lines.append(f"• Last Review: {performance['last_review']}")
                if performance.get("rating"):
                    response_lines.append(f"• Rating: {performance['rating']}")
                response_lines.append("")
            
            # Get leave balance
            balance = self._get_leave_balances_for_employee(auth_header, employee_id)
            if balance:
                response_lines.append("**Leave Balance:**")
                leave_types = self._get_leave_types_map(auth_header)
                for bal in balance[:5]:  # Show top 5 leave types
                    leave_type_id = bal.get("leave_type_id")
                    leave_type_name = leave_types.get(leave_type_id, f"Leave Type {leave_type_id}")
                    remaining = bal.get("balance", bal.get("remaining"))
                    response_lines.append(f"• {leave_type_name}: {remaining} days")
                response_lines.append("")
            
            # Get attendance info
            attendance = self._laravel_get_data(f"/api/employees/{employee_id}/attendance", auth_header)
            if isinstance(attendance, dict) and attendance:
                response_lines.append("**Attendance:**")
                if attendance.get("present_days"):
                    response_lines.append(f"• Present: {attendance['present_days']} days")
                if attendance.get("absent_days"):
                    response_lines.append(f"• Absent: {attendance['absent_days']} days")
                if attendance.get("late_days"):
                    response_lines.append(f"• Late: {attendance['late_days']} days")
                response_lines.append("")
        
        response_lines.append("*(All data read-only)*")
        return "\n".join(response_lines), "employee-api"

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
            return "I couldn't find an employee named '" + employee_name + "'.", "not-found"

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
                f"I found {name}, but no performance data is recorded yet.",
                "no-data",
            )

        return response.strip(), "employee-api"

    def _handle_leave_balance(self, **kwargs) -> Tuple[str, str]:
        """Get user's leave balance."""
        auth_header = kwargs.get("auth_header")
        if not auth_header:
            return "Please sign in to check your leave balance.", "auth-required"

        employee_id = self._get_current_employee_id(auth_header)
        if not employee_id:
            return (
                "I couldn't find an employee profile linked to your account yet, so I can't read your leave balance.",
                "leave-api",
            )

        leave_types = self._get_leave_types_map(auth_header)
        balances = self._get_leave_balances_for_employee(auth_header, employee_id)
        if balances:
            lines = self._format_leave_balance_lines(balances, leave_types)
            if lines:
                return (
                    "Here is your current leave balance:\n" + "\n".join(lines),
                    "leave-api",
                )

        if leave_types:
            option_lines = self._format_leave_type_lines(leave_types)
            return (
                "I couldn't find a configured personal leave balance for your employee profile yet.\n\n"
                "Here are your available leave types:\n"
                + "\n".join(option_lines)
                + "\n\nIf you'd like, tell me your dates and I can help preview the request."
            ), "leave-api"

        return (
            "I couldn't find a configured leave balance for your employee profile yet. You can still open Leave Requests to preview a request or contact HR to confirm your balance.",
            "leave-api",
        )

    def _handle_leave_days(self, **kwargs) -> Tuple[str, str]:
        """Get available leave types and days."""
        auth_header = kwargs.get("auth_header")
        if not auth_header:
            return "Please sign in to view leave options.", "auth-required"

        message = kwargs.get("message", "")
        single_date = self._extract_single_date_reference(message)
        if single_date:
            day_label = single_date["label"]
            day_value = single_date["date"]
            pretty_date = day_value.strftime("%A, %B %d, %Y")
            if day_value.weekday() >= 5:
                return (
                    f"{day_label.title()} is {pretty_date}. That falls on a weekend, so it wouldn't count as a working day. Send me a working-day date range if you'd like help with your request.",
                    "leave-preview",
                )
            return (
                f"{day_label.title()} is {pretty_date}. I need your end date or how many working days you want off to proceed.",
                "clarification",
            )

        employee_id = self._get_current_employee_id(auth_header)
        leave_types = self._get_leave_types_map(auth_header)
        balances = (
            self._get_leave_balances_for_employee(auth_header, employee_id)
            if employee_id
            else []
        )
        balance_by_type = self._index_latest_balances_by_type(balances)

        if leave_types:
            lines = self._format_leave_type_lines(leave_types, balance_by_type)

            return (
                "Here are your leave options:\n"
                + "\n".join(lines)
                + "\n\nOpen Leave Requests to submit one."
            ), "leave-api"

        return (
            "I can't load leave types right now. Open Leave Requests to start, and I'll help explain the rules when service is available.",
            "leave-api",
        )

    def _handle_attendance(self, **kwargs) -> Tuple[str, str]:
        """Get attendance information."""
        auth_header = kwargs.get("auth_header")
        if not auth_header:
            return "Please sign in to view attendance data.", "auth-required"

        stats = self._laravel_get_data("/api/attendance/statistics", auth_header)
        if isinstance(stats, dict):
            response = "Here is today's attendance overview:\n"
            mappings = [
                ("present_today", "Present"),
                ("late_today", "Late"),
                ("absent_today", "Absent"),
                ("on_leave_today", "On leave"),
                ("half_day_today", "Half day"),
            ]
            lines = [
                "- " + label + ": " + str(stats[key])
                for key, label in mappings
                if stats.get(key) is not None
            ]
            if lines:
                return response + "\n".join(lines), "attendance-api"

        homepage = self._laravel_get_data("/api/web/homepage", auth_header)
        if isinstance(homepage, dict):
            summary = homepage.get("statistics", {})
            active = summary.get("active_employees")
            on_leave = summary.get("on_leave_employees")
            lines = []
            if active is not None:
                lines.append("- Active employees: " + str(active))
            if on_leave is not None:
                lines.append("- On leave: " + str(on_leave))
            if lines:
                return "Here is today's attendance overview:\n" + "\n".join(lines), "attendance-api"

        return (
            "For detailed attendance information, please check the Attendance section in your dashboard.",
            "attendance-api",
        )

    def _handle_department_stats(self, **kwargs) -> Tuple[str, str]:
        """Get department employee counts."""
        auth_header = kwargs.get("auth_header")
        if not auth_header:
            return "Please sign in to view department statistics.", "auth-required"

        employees = self._collect_paginated_items("/api/employees", auth_header)
        if not employees:
            return "No employees found.", "empty"

        dept_counts = {}
        departments = self._collect_paginated_items("/api/organization/departments", auth_header)
        department_names = {
            self._coerce_int(dept.get("id")): dept.get("name")
            for dept in departments
            if self._coerce_int(dept.get("id")) is not None
        }

        for emp in employees:
            dept_id = self._coerce_int(emp.get("department_id"))
            dept = department_names.get(dept_id) or emp.get("department") or emp.get("dept") or "Unknown"
            dept_counts[dept] = dept_counts.get(dept, 0) + 1

        requested_department = kwargs.get("entities", {}).get("department")
        if requested_department:
            matched_name = next(
                (
                    name
                    for name in dept_counts
                    if str(name).lower() == str(requested_department).lower()
                ),
                requested_department,
            )
            count = dept_counts.get(matched_name, 0)
            return (
                "There are " + str(count) + " employees in " + matched_name + ".",
                "employee-api",
            )

        response = "Employees by department:\n"
        for dept, count in sorted(dept_counts.items(), key=lambda item: (-item[1], item[0])):
            response += "- " + dept + ": " + str(count) + "\n"

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
                response += "- " + label + ": " + str(count) + "\n"
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

        homepage = self._laravel_get_data("/api/web/homepage", auth_header)
        if isinstance(homepage, dict):
            total = homepage.get("statistics", {}).get("total_employees")
            if total is not None:
                return (
                    "There are " + str(total) + " employees total.",
                    "laravel-api",
                )

        employees_payload = self._laravel_get_data("/api/employees", auth_header)
        if isinstance(employees_payload, dict):
            meta = employees_payload.get("meta", {})
            total = meta.get("total")
            if total is not None:
                return "There are " + str(total) + " employees total.", "laravel-api"

        employees = self._collect_paginated_items("/api/employees", auth_header)
        if employees:
            return "There are " + str(len(employees)) + " employees total.", "laravel-api"

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
                "To view " + employee_name + "'s benefits and insurance, check Employee profile in admin dashboard or contact HR.",
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
                    logger.info("Policy query handled by LLM with RAG context")
                    return response, model
        except Exception as e:
            logger.warning("Policy RAG/LLM failed, using fallback: %s", e)

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

        return policy_content

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
        """Handle general queries using a two-stage LLM pipeline.

        Stage 1 (Think): The LLM privately reasons about what kind of
        question this is and what the best answer strategy would be.
        Stage 2 (Answer): The final answer prompt is enriched with the
        reasoning notes so the LLM produces a more accurate response.
        """
        message = kwargs.get("message", "")
        memory = kwargs.get("memory", {})
        history = kwargs.get("history", [])
        auth_header = kwargs.get("auth_header")
        user_roles = kwargs.get("user_roles")

        # Get platform context
        platform_context = self._get_platform_context(
            auth_header, user_roles, message=message
        )
        rag_context = self._get_rag_context(message)

        # ── Stage 1: Think (private chain-of-thought) ──
        thinking_context = ""
        try:
            thinking_messages = self._build_thinking_messages(
                message, platform_context, rag_context
            )
            thinking_result, _ = self._call_llm(thinking_messages)
            if thinking_result:
                thinking_context = thinking_result
                logger.debug("Thinking result: %s", thinking_context[:200])
        except Exception as exc:
            logger.warning("Thinking stage failed, proceeding without: %s", exc)

        # ── Stage 2: Answer (enriched with thinking context) ──
        messages = self._build_messages(
            user_input=message,
            memory=memory,
            history=history,
            platform_context=platform_context,
            rag_context=rag_context,
            user_roles=user_roles,
            thinking_context=thinking_context,
        )

        return self._call_llm(messages)

    def _handle_error(self, intent: str) -> str:
        """Handle unexpected errors gracefully."""
        return "I encountered an issue processing your request. Could you try rephrasing your question? I'm here to help with employee information, leave, payroll, policies, and more."

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
                logger.warning("Model %s failed: %s", model_id, e)
                continue

        return self._smart_fallback(messages), None

    def _call_model(self, model_id: str, messages: list) -> Optional[str]:
        """Call a specific LLM model."""
        payload = {
            "model": model_id,
            "messages": messages,
            "max_tokens": 2048,
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
            logger.warning("LLM call failed: %s", e)

        return None

    def _smart_fallback(self, messages: list) -> str:
        """Smart fallback when LLM is unavailable."""
        user_msg = messages[-1].get("content", "").lower() if messages else ""

        if any(g in user_msg for g in ["hi", "hello", "hey"]):
            return (
                "Hello! I'm " + self.BOT_NAME + ", your smart assistant. "
                "I can help with platform data, HR workflows, and general questions. "
                "How can I help you today?"
            )

        if "leave" in user_msg:
            return (
                "For leave requests, please use the Leave section in your dashboard. "
                "I can help you understand leave policies or check your balance "
                "once the AI service is back online."
            )

        if "employee" in user_msg or "staff" in user_msg:
            return (
                "I can help you find employee information. "
                "Please sign in and try again, or contact HR."
            )

        if "policy" in user_msg:
            return (
                "For policies, please check the Employee Handbook "
                "in your dashboard or contact HR."
            )

        if "payroll" in user_msg or "salary" in user_msg:
            return (
                "For payroll information, please check the Payroll section "
                "or contact HR."
            )

        if "insurance" in user_msg or "benefits" in user_msg:
            return (
                "For insurance and benefits, please check the Insurance section "
                "in your dashboard."
            )

        # General knowledge fallback - don't deflect, acknowledge honestly
        return (
            "I'm " + self.BOT_NAME + ". The AI service is temporarily unavailable, "
            "so I can't give you a full answer right now. "
            "Once it's back online, I can answer your questions, "
            "HR workflows, math, daily life, and much more. "
            "Meanwhile, you can explore dashboard modules directly."
        )

    def _get_platform_context(
        self,
        auth_header: Optional[str],
        user_roles: Optional[List[str]],
        message: str = "",
    ) -> str:
        """Get platform context from Laravel API."""
        parts = []
        roles_set = self._roles_set(user_roles)

        if roles_set:
            roles_str = ", ".join(sorted(roles_set))
            if roles_str:
                parts.append(f"User roles: {roles_str}.")
            if "admin" in roles_set:
                parts.append(
                    "Admin read-only platform scan is enabled for this chat session."
                )

        if not auth_header:
            return "\n".join(parts)

        current_user = self._get_current_user_data(auth_header)
        if current_user:
            user = current_user.get("user", {}) or {}
            employee = user.get("employee", {}) or {}
            identity_bits = []
            if user.get("name"):
                identity_bits.append(f"user name={user['name']}")
            if employee.get("department", {}).get("name"):
                identity_bits.append(
                    f"department={employee['department']['name']}"
                )
            if employee.get("designation", {}).get("name"):
                identity_bits.append(
                    f"designation={employee['designation']['name']}"
                )
            if current_user.get("employee_id"):
                identity_bits.append(f"employee_id={current_user['employee_id']}")
            if identity_bits:
                parts.append("Current user context: " + ", ".join(identity_bits) + ".")

        homepage = self._laravel_get_data("/api/web/homepage", auth_header)
        if isinstance(homepage, dict):
            stats = homepage.get("statistics", {})
            if stats:
                parts.append(
                    "Platform statistics snapshot: "
                    + json.dumps(stats, ensure_ascii=True)
                )
            modules = [
                module.get("name")
                for module in homepage.get("modules", [])
                if isinstance(module, dict) and module.get("name")
            ]
            if modules:
                parts.append("Platform modules: " + ", ".join(modules) + ".")

        if (
            auth_header
            and self._is_platform_related_query(message)
            and any(keyword in message.lower() for keyword in ["employee", "staff", "department"])
        ):
            employees = self._collect_paginated_items("/api/employees", auth_header)
            if employees:
                parts.append(
                    "Employee directory snapshot size: " + str(len(employees)) + " records on the fetched pages."
                )

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

    def _build_thinking_messages(
        self,
        user_input: str,
        platform_context: str = "",
        rag_context: str = "",
    ) -> list:
        """Build a private chain-of-thought prompt for Stage 1 reasoning."""

        context_summary = ""
        if platform_context:
            context_summary += "\nPLATFORM DATA AVAILABLE:\n" + platform_context[:800]
        if rag_context:
            context_summary += "\nKNOWLEDGE BASE DATA:\n" + rag_context[:600]

        thinking_prompt = (
            "You are an internal reasoning engine. "
            "Analyze this user question and produce brief reasoning notes.\n\n"
            'USER QUESTION: "' + user_input + '"\n'
            + context_summary + "\n\n"
            "Think through:\n"
            "1. What kind of question is this? "
            "(platform data / HR policy / math / general knowledge / daily life / coding / other)\n"
            "2. Is there relevant data in context or knowledge base?\n"
            "3. What is the best strategy to answer this clearly and helpfully?\n"
            "4. Are there any caveats or things to be careful about?\n\n"
            "Output ONLY your reasoning notes in 3-5 bullet points. Be brief and direct."
        )

        return [
            {
                "role": "system",
                "content": (
                    "You are an internal reasoning assistant. "
                    "Output only private reasoning notes. "
                    "Never produce a user-facing answer."
                ),
            },
            {"role": "user", "content": thinking_prompt},
        ]

    def _build_messages(
        self,
        user_input: str,
        memory: dict,
        history: list,
        platform_context: str = "",
        rag_context: str = "",
        user_roles: Optional[List[str]] = None,
        thinking_context: str = "",
    ) -> list:
        """Build messages for LLM."""

        name = memory.get("name", "")
        user_context = ("\n- User's name: " + name) if name else ""
        platform_hint = (
            "platform-related"
            if self._is_platform_related_query(user_input)
            else "general or unclear"
        )

        roles_line = ""
        if user_roles:
            roles_line = (
                "USER ROLES: "
                + ", ".join(sorted({r.lower() for r in user_roles if r}))
                + "\n"
            )

        thinking_block = ""
        if thinking_context:
            thinking_block = (
                "\n\nINTERNAL REASONING NOTES "
                "(use these to guide your answer, but never reveal them):\n"
                + thinking_context
            )

        system_prompt = (
            "You are " + self.BOT_NAME + ", a smart, professional, "
            "and friendly AI assistant.\n\n"
            "You serve users of the Unified HR, Insurance & Social Platform, "
            "but you are NOT limited to HR topics.\n"
            "You are a universal assistant that can answer ANY question.\n\n"
            "PERSONALITY:\n"
            "- Warm, concise, and professional\n"
            "- Address user by name when known\n"
            '- Never say "As an AI" - just be helpful and knowledgeable\n'
            "- Be confident and direct in your answers\n\n"
            "CAPABILITIES (you can answer ALL of these):\n"
            "- Platform & HR: Employee search, leave, payroll, attendance, "
            "policies, insurance, benefits\n"
            "- Math & Calculations: Percentages, conversions, equations, statistics\n"
            "- General Knowledge: History, science, geography, definitions, explanations\n"
            "- Daily Life: Tips, advice, recommendations, how-to guides\n"
            "- Tech & Coding: Programming questions, debugging help, tech explanations\n"
            "- Creative: Writing help, brainstorming, summaries\n"
            "- Anything else the user asks about\n\n"
            "CRITICAL RULES:\n"
            '1. NEVER deflect with "I can only help with X". '
            "Always try to answer the question directly.\n"
            "2. For platform questions, use PLATFORM CONTEXT and BACKGROUND "
            "KNOWLEDGE to answer with exact names, counts, dates, or statuses "
            "when available.\n"
            "3. For general questions (math, daily life, coding, etc.), "
            "answer them directly using your knowledge. "
            "These do NOT need platform context.\n"
            "4. Treat platform access as read-only. Never claim to create, "
            "update, approve, reject, or modify records.\n"
            "5. If the question is truly unclear, ask one short follow-up "
            "question instead of guessing.\n"
            "6. Never invent platform records, permissions, leave balances, "
            "or policies.\n"
            "7. If you genuinely do not know something, say so clearly and "
            "briefly - never give a generic redirect when a real answer "
            "is possible.\n"
            "8. Preserve helpful line breaks so status lines and bullet lists "
            "stay readable."
            + user_context + "\n"
            "QUESTION TYPE HINT: " + platform_hint + "\n"
            + roles_line + "\n"
            + platform_context + "\n"
            + rag_context
            + thinking_block + "\n"
            "RESPONSE STYLE: Use bullet points for lists, plain prose for "
            "conversation. Be thorough but concise."
        )

        messages = [{"role": "system", "content": system_prompt.strip()}]

        # Add recent history (last 5 messages)
        for msg in history[-5:]:
            messages.append(msg)

        if (
            not history
            or history[-1].get("role") != "user"
            or history[-1].get("content") != user_input
        ):
            messages.append({"role": "user", "content": user_input})

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
                extracted_name = name_match.group(1).capitalize()
                # Filter out common articles and words that aren't names
                excluded_words = {"the", "a", "an", "and", "or", "but", "if", "is", "am", "are"}
                if extracted_name.lower() not in excluded_words and len(extracted_name) > 2:
                    memory["name"] = extracted_name

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
        if not isinstance(text, str):
            text = str(text)

        single_line = os.getenv("CHATBOT_SINGLE_LINE", "false").lower() in {
            "1",
            "true",
            "yes",
        }
        if not single_line:
            cleaned = re.sub(r"\n{3,}", "\n\n", text)
            return cleaned.strip()
        return " ".join(text.split())

    def _roles_set(self, user_roles: Optional[List[str]]) -> set[str]:
        return {str(role).lower() for role in (user_roles or []) if role}

    def _coerce_int(self, value: Any) -> Optional[int]:
        try:
            return int(value)
        except (TypeError, ValueError):
            return None

    def _laravel_request(
        self,
        method: str,
        path: str,
        auth_header: Optional[str],
        *,
        params: Optional[Dict[str, Any]] = None,
        json_payload: Optional[Dict[str, Any]] = None,
        timeout: int = 15,
    ) -> Optional[requests.Response]:
        if not auth_header:
            return None

        try:
            return requests.request(
                method,
                f"{self._get_laravel_base_url()}{path}",
                headers={"Authorization": auth_header, "Accept": "application/json"},
                params=params,
                json=json_payload,
                timeout=timeout,
            )
        except Exception as exc:
            logger.warning("Laravel %s %s failed: %s", method, path, exc)
            return None

    def _laravel_get_data(
        self,
        path: str,
        auth_header: Optional[str],
        params: Optional[Dict[str, Any]] = None,
    ) -> Any:
        response = self._laravel_request(
            "GET", path, auth_header, params=params, timeout=15
        )
        if not response or response.status_code != 200:
            return None

        try:
            payload = response.json()
        except Exception:
            return None

        return payload.get("data")

    def _extract_collection_items(self, payload: Any) -> List[Dict[str, Any]]:
        if isinstance(payload, list):
            return [item for item in payload if isinstance(item, dict)]

        if isinstance(payload, dict):
            inner = payload.get("data")
            if isinstance(inner, list):
                return [item for item in inner if isinstance(item, dict)]

        return []

    def _collect_paginated_items(
        self,
        path: str,
        auth_header: Optional[str],
        params: Optional[Dict[str, Any]] = None,
        max_pages: int = 8,
    ) -> List[Dict[str, Any]]:
        items: List[Dict[str, Any]] = []
        page = 1

        while page <= max_pages:
            current_params = dict(params or {})
            current_params["page"] = page
            data = self._laravel_get_data(path, auth_header, current_params)
            if data is None:
                break

            page_items = self._extract_collection_items(data)
            items.extend(page_items)

            if not isinstance(data, dict):
                break

            meta = data.get("meta") or {}
            current_page = int(meta.get("current_page") or page)
            last_page = int(meta.get("last_page") or current_page)
            if current_page >= last_page or not page_items:
                break
            page = current_page + 1

        return items

    def _get_current_user_data(self, auth_header: Optional[str]) -> Dict[str, Any]:
        data = self._laravel_get_data("/api/core/auth/me", auth_header)
        return data if isinstance(data, dict) else {}

    def _get_current_employee_id(self, auth_header: Optional[str]) -> Optional[int]:
        current_user = self._get_current_user_data(auth_header)
        employee_id = self._coerce_int(current_user.get("employee_id"))
        if employee_id is not None:
            return employee_id

        user = current_user.get("user", {}) or {}
        employee = user.get("employee", {}) or {}
        return self._coerce_int(employee.get("id"))

    def _get_leave_types_map(
        self, auth_header: Optional[str]
    ) -> Dict[int, Dict[str, Any]]:
        items = self._collect_paginated_items("/api/leaves/types", auth_header)
        result = {}
        for item in items:
            type_id = self._coerce_int(item.get("id"))
            if type_id is not None:
                result[type_id] = item
        return result

    def _get_leave_balances_for_employee(
        self, auth_header: Optional[str], employee_id: Optional[int]
    ) -> List[Dict[str, Any]]:
        if employee_id is None:
            return []

        balances = self._collect_paginated_items(
            "/api/leaves/balances", auth_header, max_pages=12
        )
        return [
            item
            for item in balances
            if self._coerce_int(item.get("employee_id")) == employee_id
        ]

    def _index_latest_balances_by_type(
        self, balances: List[Dict[str, Any]]
    ) -> Dict[int, Dict[str, Any]]:
        latest: Dict[int, Dict[str, Any]] = {}
        for balance in balances:
            type_id = self._coerce_int(balance.get("leave_type_id"))
            if type_id is None:
                continue

            candidate_year = self._coerce_int(balance.get("year")) or 0
            existing = latest.get(type_id)
            existing_year = (
                self._coerce_int(existing.get("year")) if existing else None
            ) or -1

            if existing is None or candidate_year >= existing_year:
                latest[type_id] = balance

        return latest

    def _format_leave_balance_lines(
        self,
        balances: List[Dict[str, Any]],
        leave_types: Dict[int, Dict[str, Any]],
    ) -> List[str]:
        lines = []
        for type_id, balance in sorted(
            self._index_latest_balances_by_type(balances).items(),
            key=lambda item: str(
                leave_types.get(item[0], {}).get("name")
                or item[1].get("leave_type_name")
                or item[0]
            ),
        ):
            leave_type = leave_types.get(type_id, {})
            name = (
                leave_type.get("name")
                or balance.get("leave_type_name")
                or f"Leave Type #{type_id}"
            )

            details = []
            remaining = balance.get("remaining")
            if remaining not in (None, ""):
                details.append(f"{remaining} days remaining")

            used_days = balance.get("used_days")
            if used_days not in (None, ""):
                details.append(f"{used_days} used")

            opening_balance = balance.get("opening_balance")
            if opening_balance not in (None, ""):
                details.append(f"{opening_balance} opening")

            year = balance.get("year")
            if year not in (None, ""):
                details.append(f"year {year}")

            lines.append(
                f"- {name}: {', '.join(details) if details else 'balance configured'}"
            )

        return lines

    def _format_leave_type_lines(
        self,
        leave_types: Dict[int, Dict[str, Any]],
        balance_by_type: Optional[Dict[int, Dict[str, Any]]] = None,
    ) -> List[str]:
        lines = []
        balances_index = balance_by_type or {}

        for leave_type in sorted(
            leave_types.values(), key=lambda item: str(item.get("name") or "")
        ):
            type_id = self._coerce_int(leave_type.get("id"))
            name = leave_type.get("name") or f"Leave Type #{type_id}"
            max_days = (
                leave_type.get("maximum_days")
                or leave_type.get("max_days")
                or leave_type.get("annual_quota")
            )
            balance = balances_index.get(type_id) if type_id is not None else None

            details = []
            if max_days not in (None, ""):
                details.append(f"configured maximum: {max_days} days")
            if balance:
                remaining = balance.get("remaining")
                if remaining not in (None, ""):
                    details.append(f"{remaining} days remaining")

            lines.append(
                f"- {name}: {', '.join(details) if details else 'configured'}"
            )

        return lines

    def _get_named_collection_map(
        self, path: str, auth_header: Optional[str]
    ) -> Dict[int, str]:
        items = self._collect_paginated_items(path, auth_header)
        result: Dict[int, str] = {}
        for item in items:
            item_id = self._coerce_int(item.get("id"))
            name = item.get("name")
            if item_id is not None and name:
                result[item_id] = str(name)
        return result

    def _resolve_employee_department(
        self, employee: Dict[str, Any], department_names: Dict[int, str]
    ) -> Optional[str]:
        department_id = self._coerce_int(employee.get("department_id"))
        return (
            department_names.get(department_id)
            or employee.get("department")
            or employee.get("dept")
        )

    def _resolve_employee_designation(
        self, employee: Dict[str, Any], designation_names: Dict[int, str]
    ) -> Optional[str]:
        designation_id = self._coerce_int(employee.get("designation_id"))
        return (
            designation_names.get(designation_id)
            or employee.get("designation")
            or employee.get("position")
            or employee.get("role")
        )

    def _format_employee_summary_line(
        self,
        employee: Dict[str, Any],
        department_names: Dict[int, str],
        designation_names: Dict[int, str],
        *,
        include_contact: bool = False,
        include_identifier: bool = False,
        include_phone: bool = False,
    ) -> str:
        name = employee.get("name") or employee.get("email") or "Unknown employee"
        designation = self._resolve_employee_designation(employee, designation_names)
        department = self._resolve_employee_department(employee, department_names)

        details = []
        if designation:
            details.append(f"designation: {designation}")
        if department:
            details.append(f"department: {department}")
        if include_contact and employee.get("email"):
            details.append(f"email: {employee['email']}")
        if include_identifier and employee.get("employee_id"):
            details.append(f"employee ID: {employee['employee_id']}")
        if include_phone and employee.get("phone"):
            details.append(f"phone: {employee['phone']}")

        if not details:
            return f"- {name}"
        return f"- {name}: {', '.join(details)}"

    def _employee_matches_directory_filters(
        self,
        employee: Dict[str, Any],
        department_names: Dict[int, str],
        designation_names: Dict[int, str],
        filters: Dict[str, Any],
    ) -> bool:
        designation = str(
            self._resolve_employee_designation(employee, designation_names) or ""
        ).lower()
        department = str(
            self._resolve_employee_department(employee, department_names) or ""
        ).lower()

        requested_department = str(filters.get("department") or "").lower().strip()
        if requested_department and department != requested_department:
            return False

        designation_terms = [
            str(term).lower() for term in (filters.get("designation_terms") or []) if term
        ]
        department_terms = [
            str(term).lower() for term in (filters.get("department_terms") or []) if term
        ]
        if designation_terms and not any(term in designation for term in designation_terms):
            if not any(term in department for term in department_terms):
                return False

        return True

    def _find_employee_matches(
        self, employee_name: str, employees: List[Dict[str, Any]]
    ) -> List[Dict[str, Any]]:
        search_name = employee_name.lower().strip()
        if not search_name:
            return []

        exact_matches = []
        partial_matches = []
        token_matches = []

        for employee in employees:
            candidate = str(employee.get("name") or "").strip()
            candidate_lower = candidate.lower()
            if not candidate_lower:
                continue

            if candidate_lower == search_name:
                exact_matches.append(employee)
                continue

            if search_name in candidate_lower or candidate_lower in search_name:
                partial_matches.append(employee)
                continue

            search_tokens = {token for token in re.split(r"\s+", search_name) if token}
            candidate_tokens = {
                token for token in re.split(r"\s+", candidate_lower) if token
            }
            if search_tokens and search_tokens.issubset(candidate_tokens):
                token_matches.append(employee)

        return exact_matches or partial_matches or token_matches

    def _resolve_relative_weekday(
        self, base_date, target_weekday: int, modifier: str
    ):
        delta = target_weekday - base_date.weekday()
        if modifier == "this":
            if delta < 0:
                delta += 7
        else:
            if delta <= 0:
                delta += 7
        return base_date + timedelta(days=delta)

    def _extract_single_date_reference(
        self, text: str
    ) -> Optional[Dict[str, Any]]:
        lower = text.lower()
        today = timezone.localdate()

        if "day after tomorrow" in lower:
            return {"label": "day after tomorrow", "date": today + timedelta(days=2)}
        if "tomorrow" in lower:
            return {"label": "tomorrow", "date": today + timedelta(days=1)}
        if "today" in lower:
            return {"label": "today", "date": today}

        weekday_match = re.search(
            r"\b(this|next)\s+(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b",
            lower,
        )
        if weekday_match:
            modifier, weekday = weekday_match.groups()
            return {
                "label": f"{modifier} {weekday}",
                "date": self._resolve_relative_weekday(
                    today, WEEKDAY_TO_INDEX[weekday], modifier
                ),
            }

        iso_match = re.search(r"\b(\d{4}-\d{2}-\d{2})\b", text)
        if iso_match:
            try:
                return {
                    "label": iso_match.group(1),
                    "date": datetime.strptime(iso_match.group(1), "%Y-%m-%d").date(),
                }
            except ValueError:
                return None

        return None

    def _is_platform_related_query(self, text: str) -> bool:
        text_lower = text.lower()
        return any(
            keyword in text_lower
            for keyword in [
                "employee",
                "staff",
                "department",
                "designation",
                "branch",
                "leave",
                "attendance",
                "timesheet",
                "policy",
                "payroll",
                "salary",
                "benefit",
                "insurance",
                "claim",
                "contract",
                "dashboard",
                "platform",
                "system",
                "role",
                "user",
            ]
        )

    def _create_response(
        self, text: str, intent: str = "general", model_used: Optional[str] = None
    ) -> Dict[str, Any]:
        return {
            "response": text,
            "intent": intent,
            "entities": {},
            "model_used": model_used,
        }

    # Backward-compatible helpers for older scripts/tests.
    build_messages = _build_messages
    get_history = _get_history

    def extract_entities(self, text: str) -> Dict[str, Any]:
        entities: Dict[str, Any] = {}

        name_match = re.search(
            r"(?:my name is|i am|i'm)\s+([A-Z][a-z]+)\b", text, re.IGNORECASE
        )
        if name_match:
            candidate = name_match.group(1).capitalize()
            if candidate.lower() not in {"the", "an", "a"}:
                entities["name"] = candidate

        employee_name = self._extract_employee_name(text)
        if employee_name:
            entities["employee_name"] = employee_name

        return entities

    def call_with_fallback(self, messages: list) -> Tuple[str, Optional[str]]:
        if not self.hf_api_key:
            return (
                "LLM configuration is unavailable right now, so I can only provide a basic fallback response.",
                None,
            )
        return self._call_llm(messages)


ChatbotEngine = SmartChatbotEngine
