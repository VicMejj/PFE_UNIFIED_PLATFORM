"""
=============================================================
  MEJJ AI CHATBOT — FULL TEST SUITE
  Tests: memory, entity extraction, history, fallback chain,
         edge cases, multi-user isolation, HR intent coverage
=============================================================
"""

import os
import sys
import uuid
import django
from unittest.mock import patch, MagicMock

BASE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "."))
sys.path.append(BASE_DIR)
os.environ.setdefault("DJANGO_SETTINGS_MODULE", "unified_platform.settings")
django.setup()

from ai_services.services.chatbot_engine import ChatbotEngine
from ai_services.models import ChatbotConversation, ChatbotMessage


# ─────────────────────────────────────────────────────────────
# HELPERS
# ─────────────────────────────────────────────────────────────

PASS = "✅ PASS"
FAIL = "❌ FAIL"
SKIP = "⚠️  SKIP"

results = []


def session() -> str:
    return f"test-{uuid.uuid4().hex[:8]}"


def assert_that(label: str, condition: bool, detail: str = ""):
    status = PASS if condition else FAIL
    results.append((status, label))
    icon = "✅" if condition else "❌"
    detail_str = f"  → {detail}" if detail else ""
    print(f"  {icon} {label}{detail_str}")
    return condition


def section(title: str):
    print(f"\n{'─' * 55}")
    print(f"  🧪 {title}")
    print(f"{'─' * 55}")


def cleanup(session_id: str):
    ChatbotConversation.objects.filter(session_uuid=session_id).delete()


# ─────────────────────────────────────────────────────────────
# MOCK HELPER — avoids real API calls for unit tests
# ─────────────────────────────────────────────────────────────

def mock_response(text: str):
    """Return a mocked successful _call_model result."""
    return (text, "")


# ─────────────────────────────────────────────────────────────
# TEST 1 — ENTITY EXTRACTION (pure unit test, no API)
# ─────────────────────────────────────────────────────────────

def test_entity_extraction():
    section("ENTITY EXTRACTION")
    engine = ChatbotEngine()

    cases = [
        ("My name is Montassar", {"name": "Montassar"}),
        ("I'm Victor", {"name": "Victor"}),
        ("Hi, I am Sarah", {"name": "Sarah"}),
        ("I am the lead developer of this project", None),   # no name, has role
        ("My name is John, I am a software engineer", {"name": "John"}),
        ("", {}),
        ("How do I request leave?", {}),
        ("I'm an HR manager", None),                          # has role
    ]

    for text, expected_name_entity in cases:
        entities = engine.extract_entities(text)
        if expected_name_entity == {}:
            assert_that(
                f'extract_entities("{text[:40]}") → empty',
                entities == {},
                f"got {entities}",
            )
        elif expected_name_entity is None:
            # just check it doesn't crash and returns a dict
            assert_that(
                f'extract_entities("{text[:40]}") → is dict',
                isinstance(entities, dict),
                f"got {entities}",
            )
        else:
            for key, val in expected_name_entity.items():
                assert_that(
                    f'extract "{key}" from "{text[:40]}"',
                    entities.get(key, "").lower() == val.lower(),
                    f"got {entities.get(key)!r}, expected {val!r}",
                )


# ─────────────────────────────────────────────────────────────
# TEST 2 — MEMORY PERSISTENCE (mocked API)
# ─────────────────────────────────────────────────────────────

def test_memory_persistence():
    section("MEMORY PERSISTENCE")
    sid = session()
    engine = ChatbotEngine()

    with patch.object(engine, "_call_model", return_value=mock_response(
        "Hi Montassar! Great to meet you. I'll remember you're the lead developer."
    )):
        engine.process_message("My name is Montassar. I am the lead developer.", sid)

    conv = ChatbotConversation.objects.get(session_uuid=sid)
    memory = conv.memory or {}

    assert_that("Name stored in memory", memory.get("name", "").lower() == "montassar", f"memory={memory}")
    assert_that("Memory persists in DB", bool(memory), f"memory={memory}")

    # Second message — memory should still be there
    with patch.object(engine, "_call_model", return_value=mock_response(
        "Yes, your name is Montassar and you are the lead developer."
    )):
        res = engine.process_message("Do you remember my name?", sid)

    assert_that("process_message returns response key", "response" in res)
    assert_that("process_message returns model_used key", "model_used" in res)
    assert_that("process_message returns entities key", "entities" in res)

    cleanup(sid)


# ─────────────────────────────────────────────────────────────
# TEST 3 — CONVERSATION HISTORY (mocked API)
# ─────────────────────────────────────────────────────────────

def test_conversation_history():
    section("CONVERSATION HISTORY")
    sid = session()
    engine = ChatbotEngine()

    turns = [
        ("Hi, my name is Leila.", "Hello Leila! How can I help you today?"),
        ("I want to check my leave balance.", "You have 12 days of leave remaining."),
        ("Can I take 3 days next week?", "Of course! What dates are you thinking?"),
    ]

    for user_msg, bot_reply in turns:
        with patch.object(engine, "_call_model", return_value=mock_response(bot_reply)):
            engine.process_message(user_msg, sid)

    conv = ChatbotConversation.objects.get(session_uuid=sid)
    history = engine.get_history(conv)

    assert_that("History has 6 entries (3 user + 3 bot)", len(history) == 6, f"got {len(history)}")
    assert_that("First entry is user role", history[0]["role"] == "user")
    assert_that("Second entry is assistant role", history[1]["role"] == "assistant")
    assert_that("Last entry is assistant", history[-1]["role"] == "assistant")
    assert_that(
        "History content is correct",
        "leila" in history[0]["content"].lower(),
        f"got: {history[0]['content']!r}",
    )

    # Test history limit
    limited = engine.get_history(conv, limit=4)
    assert_that("History limit=4 respected", len(limited) == 4, f"got {len(limited)}")

    cleanup(sid)


# ─────────────────────────────────────────────────────────────
# TEST 4 — MULTI-USER ISOLATION (mocked API)
# ─────────────────────────────────────────────────────────────

def test_multi_user_isolation():
    section("MULTI-USER SESSION ISOLATION")
    sid_a = session()
    sid_b = session()
    engine = ChatbotEngine()

    with patch.object(engine, "_call_model", return_value=mock_response("Hi Alice!")):
        engine.process_message("My name is Alice.", sid_a)

    with patch.object(engine, "_call_model", return_value=mock_response("Hi Bob!")):
        engine.process_message("My name is Bob.", sid_b)

    conv_a = ChatbotConversation.objects.get(session_uuid=sid_a)
    conv_b = ChatbotConversation.objects.get(session_uuid=sid_b)

    mem_a = conv_a.memory or {}
    mem_b = conv_b.memory or {}

    assert_that("Session A stores Alice", mem_a.get("name", "").lower() == "alice", f"mem_a={mem_a}")
    assert_that("Session B stores Bob", mem_b.get("name", "").lower() == "bob", f"mem_b={mem_b}")
    assert_that("Sessions are isolated", mem_a.get("name") != mem_b.get("name"))

    cleanup(sid_a)
    cleanup(sid_b)


# ─────────────────────────────────────────────────────────────
# TEST 5 — MODEL FALLBACK CHAIN (mocked)
# ─────────────────────────────────────────────────────────────

def test_fallback_chain():
    section("MODEL FALLBACK CHAIN")
    engine = ChatbotEngine()

    call_log = []

    def fake_call(model_id, messages):
        call_log.append(model_id)
        # Fail first two, succeed on third
        if len(call_log) < 3:
            return (None, f"HTTP 400: model not supported")
        return ("Fallback worked!", "")

    with patch.object(engine, "_call_model", side_effect=fake_call):
        response, model_used = engine.call_with_fallback([{"role": "user", "content": "test"}])

    assert_that("Tried multiple models on failure", len(call_log) >= 2, f"tried: {call_log}")
    assert_that("Returned successful response after fallback", response == "Fallback worked!")
    assert_that("model_used is the successful one", model_used == engine.MODEL_CHAIN[2])

    # All fail scenario
    call_log.clear()
    with patch.object(engine, "_call_model", return_value=(None, "HTTP 503")):
        response, model_used = engine.call_with_fallback([{"role": "user", "content": "test"}])

    assert_that("Returns friendly error when all models fail", "trouble" in response.lower() or "moment" in response.lower(), f"got: {response!r}")
    assert_that("model_used is None when all fail", model_used is None)


# ─────────────────────────────────────────────────────────────
# TEST 6 — PROMPT BUILDER
# ─────────────────────────────────────────────────────────────

def test_prompt_builder():
    section("PROMPT BUILDER")
    engine = ChatbotEngine()

    memory = {"name": "Karim", "role": "HR Manager"}
    history = [
        {"role": "user", "content": "Hello"},
        {"role": "assistant", "content": "Hi Karim!"},
        {"role": "user", "content": "What are my leave days?"},
    ]

    messages = engine.build_messages("What are my leave days?", memory, history)

    assert_that("First message is system", messages[0]["role"] == "system")
    assert_that("System prompt contains bot name", engine.BOT_NAME in messages[0]["content"])
    assert_that("System prompt contains user name", "Karim" in messages[0]["content"])
    assert_that("System prompt contains user role", "HR Manager" in messages[0]["content"])
    assert_that("History is appended after system", messages[1]["role"] == "user")
    assert_that("Total messages = 1 system + 3 history", len(messages) == 4, f"got {len(messages)}")

    # Empty memory case
    messages_no_mem = engine.build_messages("Hello", {}, [])
    assert_that("Builds messages with empty memory", len(messages_no_mem) == 1)
    assert_that("System prompt still valid with no memory", "system" == messages_no_mem[0]["role"])


# ─────────────────────────────────────────────────────────────
# TEST 7 — EDGE CASES (mocked API)
# ─────────────────────────────────────────────────────────────

def test_edge_cases():
    section("EDGE CASES")
    sid = session()
    engine = ChatbotEngine()

    # Empty message
    res = engine.process_message("   ", sid)
    assert_that("Empty/whitespace message handled gracefully", "response" in res)
    assert_that("Empty message returns intent=empty", res.get("intent") == "empty")

    # Very long message
    long_msg = "I need help with my leave request. " * 50
    with patch.object(engine, "_call_model", return_value=mock_response("Sure, I can help.")):
        res = engine.process_message(long_msg, sid)
    assert_that("Long message processed without crash", res.get("response") == "Sure, I can help.")

    # Special characters / SQL injection attempt
    malicious = "'; DROP TABLE chatbotmessage; --"
    with patch.object(engine, "_call_model", return_value=mock_response("I'm not sure what you mean.")):
        res = engine.process_message(malicious, sid)
    assert_that("Special characters handled safely", "response" in res)

    # Emoji in message
    emoji_msg = "Hello Mejj! 👋 Can I get some help? 😊"
    with patch.object(engine, "_call_model", return_value=mock_response("Of course! 😊")):
        res = engine.process_message(emoji_msg, sid)
    assert_that("Emoji message handled", "response" in res)

    # Missing API key
    engine_no_key = ChatbotEngine()
    engine_no_key.hf_api_key = None
    response, model = engine_no_key.call_with_fallback([{"role": "user", "content": "test"}])
    assert_that("Missing API key returns config error", "configuration" in response.lower() or "hf_api_key" in response.lower(), f"got: {response!r}")
    assert_that("Missing API key returns model_used=None", model is None)

    cleanup(sid)


# ─────────────────────────────────────────────────────────────
# TEST 8 — HR INTENT COVERAGE (real API call)
# ─────────────────────────────────────────────────────────────

def test_hr_intents_live():
    section("HR INTENT COVERAGE — LIVE API")
    sid = session()
    engine = ChatbotEngine()

    hr_cases = [
        (
            "My name is Montassar and I am an HR officer. How do I submit a leave request?",
            ["leave"],
            "Leave request guidance",
        ),
        (
            "When will I receive my pay stub this month?",
            ["pay", "salary", "payroll", "stub"],
            "Payroll inquiry",
        ),
        (
            "What is the company policy on remote work?",
            ["policy", "remote", "work"],
            "Policy question",
        ),
        (
            "I need to file an insurance claim for a medical expense.",
            ["insurance", "claim", "medical"],
            "Insurance claim",
        ),
        (
            "Do you remember my name and job title?",
            ["montassar", "hr"],
            "Memory recall",
        ),
    ]

    print()
    for user_msg, expected_keywords, label in hr_cases:
        res = engine.process_message(user_msg, sid)
        response_lower = res["response"].lower()
        keyword_hit = any(kw in response_lower for kw in expected_keywords)
        not_error = not response_lower.startswith("error:")
        model = res.get("model_used", "unknown")
        print(f"  ↳ [{label}]")
        print(f"    Q: {user_msg[:70]}...")
        print(f"    A: {res['response'][:120]}...")
        print(f"    model: {model}")
        assert_that(f"{label} — relevant response", keyword_hit and not_error, f"keywords={expected_keywords}")
        print()

    cleanup(sid)


# ─────────────────────────────────────────────────────────────
# TEST 9 — DB INTEGRITY
# ─────────────────────────────────────────────────────────────

def test_db_integrity():
    section("DATABASE INTEGRITY")
    sid = session()
    engine = ChatbotEngine()

    with patch.object(engine, "_call_model", return_value=mock_response("Got it!")):
        engine.process_message("My name is Youssef.", sid)
        engine.process_message("I work in accounting.", sid)

    conv = ChatbotConversation.objects.get(session_uuid=sid)
    msg_count = ChatbotMessage.objects.filter(conversation=conv).count()

    assert_that("Exactly 4 messages saved (2 user + 2 bot)", msg_count == 4, f"got {msg_count}")
    assert_that("Conversation linked correctly", conv.session_uuid == sid)

    user_msgs = ChatbotMessage.objects.filter(conversation=conv, sender="USER").count()
    bot_msgs = ChatbotMessage.objects.filter(conversation=conv, sender="BOT").count()
    assert_that("2 user messages in DB", user_msgs == 2, f"got {user_msgs}")
    assert_that("2 bot messages in DB", bot_msgs == 2, f"got {bot_msgs}")

    # Memory updated correctly
    conv.refresh_from_db()
    memory = conv.memory or {}
    assert_that("Memory has name=Youssef", memory.get("name", "").lower() == "youssef", f"memory={memory}")

    cleanup(sid)


# ─────────────────────────────────────────────────────────────
# RUNNER
# ─────────────────────────────────────────────────────────────

def print_summary():
    print("\n" + "=" * 55)
    print("  📊 TEST SUMMARY")
    print("=" * 55)

    passed = [r for r in results if r[0] == PASS]
    failed = [r for r in results if r[0] == FAIL]

    for status, label in results:
        print(f"  {status}  {label}")

    print(f"\n  Total : {len(results)}")
    print(f"  Passed: {len(passed)}")
    print(f"  Failed: {len(failed)}")
    print("=" * 55)

    if failed:
        print("\n  ❌ FAILED TESTS:")
        for _, label in failed:
            print(f"    • {label}")

    if not failed:
        print("\n  🎉 ALL TESTS PASSED — MEJJ IS PRODUCTION READY!")
    else:
        print(f"\n  ⚠️  {len(failed)} test(s) need attention.")

    print()


if __name__ == "__main__":
    print("\n" + "=" * 55)
    print("  🤖 MEJJ CHATBOT — FULL TEST SUITE")
    print("=" * 55)

    # Unit tests (fast, mocked)
    test_entity_extraction()
    test_memory_persistence()
    test_conversation_history()
    test_multi_user_isolation()
    test_fallback_chain()
    test_prompt_builder()
    test_edge_cases()
    test_db_integrity()

    # Live API tests (slower, real calls)
    print("\n  ⚡ Running live API tests (this may take ~20s)...")
    test_hr_intents_live()

    print_summary()