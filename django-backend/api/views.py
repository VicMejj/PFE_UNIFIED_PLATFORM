from rest_framework.decorators import api_view, permission_classes, authentication_classes
from rest_framework.response import Response
from rest_framework import status
import logging
import os
import requests
from django.conf import settings

from api.authentication import LaravelJWTAuthentication
from api.permissions import IsAuthenticated

from ai_services.services.turnover_predictor import TurnoverPredictor
from ai_services.services.leave_optimizer import LeaveOptimizer
from ai_services.services.loan_risk_scorer import LoanRiskScorer
from ai_services.services.chatbot_engine import ChatbotEngine
from ai_services.services.anomaly_detector import AnomalyDetector
from insurance.services.ocr_processor import OCRProcessor
from insurance.services.document_classifier import DocumentClassifier
from insurance.services.fraud_detector import FraudDetector

logger = logging.getLogger(__name__)


ALLOWED_CHATBOT_ROLES = {"admin", "manager", "rh", "hr"}


def _get_laravel_base_url() -> str:
    return (
        getattr(settings, "LARAVEL_API_URL", None)
        or os.getenv("LARAVEL_API_URL")
        or "http://localhost:8000"
    )


def _check_chatbot_access(auth_header: str | None) -> tuple[bool, int, str, list[str] | None]:
    if not auth_header:
        return False, status.HTTP_401_UNAUTHORIZED, "Missing authorization token.", None

    base_url = _get_laravel_base_url()
    try:
        response = requests.get(
            f"{base_url}/api/core/auth/me",
            headers={"Authorization": auth_header, "Accept": "application/json"},
            timeout=10,
        )
    except Exception:
        return False, status.HTTP_503_SERVICE_UNAVAILABLE, "Unable to verify user role right now.", None

    if response.status_code == 401:
        return False, status.HTTP_401_UNAUTHORIZED, "Unauthorized. Please log in again.", None
    if response.status_code == 403:
        return False, status.HTTP_403_FORBIDDEN, "Forbidden. You are not allowed to use the chatbot.", None
    if response.status_code != 200:
        return False, status.HTTP_502_BAD_GATEWAY, "Failed to verify user role.", None

    try:
        payload = response.json()
    except Exception:
        return False, status.HTTP_502_BAD_GATEWAY, "Invalid role verification response.", None

    data = payload.get("data", {})
    roles = data.get("roles", [])
    roles_normalized = {str(r).lower() for r in roles if r}

    if roles_normalized.intersection(ALLOWED_CHATBOT_ROLES):
        return True, status.HTTP_200_OK, "ok", list(roles_normalized)

    return (
        False,
        status.HTTP_403_FORBIDDEN,
        "Access denied. Only Admin, Manager, or RH can use Mejj.",
        list(roles_normalized),
    )


@api_view(['POST'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def predict_turnover(request):
    """Requires: Any authenticated user (roles enforced by Laravel)"""
    try:
        predictor = TurnoverPredictor()
        result = predictor.predict(request.data)
        return Response({'success': True, 'data': result, 'user_id': request.user.get('sub')}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Turnover prediction error: {e}")
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def predict_optimal_leave_dates(request):
    """Requires: Any authenticated user"""
    try:
        optimizer = LeaveOptimizer()
        result = optimizer.calculate_optimal_dates()
        return Response({'success': True, 'data': result, 'user_id': request.user.get('sub')}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Leave optimizer error: {e}")
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def assess_loan_risk(request):
    """Requires: Any authenticated user"""
    try:
        scorer = LoanRiskScorer()
        result = scorer.assess_risk(request.data)
        return Response({'success': True, 'data': result, 'user_id': request.user.get('sub')}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Loan risk assessment error: {e}")
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def chatbot_send_message(request):
    """
    Handles chatbot messages, persists them to the database, and calls the AI engine.
    Now supports session-based memory.
    """
    try:
        from ai_services.models import ChatbotConversation, ChatbotMessage
        
        text = request.data.get('message', '')
        session_id = request.data.get('session_id', 'default_session')
        user_id = request.user.get('sub') # From Laravel JWT
        
        auth_header = request.headers.get('Authorization')
        allowed, status_code, reason, roles = _check_chatbot_access(auth_header)
        if not allowed:
            return Response({'success': False, 'error': reason}, status=status_code)

        # 1. Get or create conversation session
        conversation, created = ChatbotConversation.objects.get_or_create(
            session_uuid=session_id,
            defaults={'user_id': user_id or 0}
        )
        
        # 2. Persist User Message
        user_msg = ChatbotMessage.objects.create(
            conversation=conversation,
            sender='USER',
            text=text
        )
        
        # 3. Call AI Engine with session info
        engine = ChatbotEngine()
        result = engine.process_message(
            text,
            session_id=session_id,
            auth_header=auth_header,
            user_roles=roles,
        )
        
        # 4. Extract intent/entities and persist Bot Response
        user_msg.intent = result.get('intent')
        user_msg.entities = result.get('entities', {})
        user_msg.save()
        
        ChatbotMessage.objects.create(
            conversation=conversation,
            sender='BOT',
            text=result.get('response')
        )
        
        return Response({
            'success': True, 
            'data': result, 
            'user_id': user_id,
            'session_id': session_id
        }, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Chatbot error: {e}")
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def ocr_process_document(request):
    """Requires: Any authenticated user"""
    try:
        processor = OCRProcessor()
        result = processor.process_image(None)
        return Response({'success': True, 'data': result, 'user_id': request.user.get('sub')}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"OCR processing error: {e}")
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def classify_document(request):
    """Requires: Any authenticated user"""
    try:
        classifier = DocumentClassifier()
        text = (
            request.data.get('text')
            or request.data.get('content')
            or request.data.get('document_text')
            or request.data.get('ocr_text')
            or ''
        )
        result = classifier.classify(text)
        return Response({'success': True, 'data': result, 'user_id': request.user.get('sub')}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Document classification error: {e}")
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def detect_fraud(request):
    """Requires: Any authenticated user"""
    try:
        detector = FraudDetector()
        result = detector.detect_fraud(request.data)
        return Response({'success': True, 'data': result, 'user_id': request.user.get('sub')}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Fraud detection error: {e}")
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def train_turnover_model(request):
    """Triggers training of the turnover ML model using synthetic or provided data."""
    try:
        predictor = TurnoverPredictor()
        result = predictor.train_model(request.data.get('training_data'))
        return Response({'success': True, 'data': result}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Training error: {e}")
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)
