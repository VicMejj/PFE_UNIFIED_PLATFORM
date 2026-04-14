from rest_framework.decorators import api_view, permission_classes, authentication_classes
from rest_framework.response import Response
from rest_framework import status
import logging
import os
import requests
from datetime import datetime
from django.conf import settings
from django.db.models import Q

from api.authentication import LaravelJWTAuthentication
from api.permissions import IsAuthenticated

from ai_services.services.turnover_predictor import TurnoverPredictor
from ai_services.services.leave_optimizer import LeaveOptimizer
from ai_services.services.loan_risk_scorer import LoanRiskScorer
from ai_services.services.chatbot_engine import ChatbotEngine
from ai_services.services.anomaly_detector import AnomalyDetector
from ai_services.models import TurnoverPrediction, LoanRiskAssessment
from ai_services.serializers import TurnoverPredictionSerializer, LoanRiskAssessmentSerializer
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
        employee_id = int(request.data.get('employee_id') or 0)
        user_id_raw = request.user.get('sub')
        try:
            user_id = int(user_id_raw) if user_id_raw is not None else None
        except Exception:
            user_id = None
        session_uuid = str(request.data.get('session_id') or request.data.get('session_uuid') or '')
        prediction_score = float(result.get('risk_score', result.get('prediction_score', 0)) or 0)
        saved = None
        try:
            saved = TurnoverPrediction.objects.create(
                employee_id=employee_id,
                prediction_score=prediction_score,
                risk_level=str(result.get('risk_level', 'unknown')),
                factors_analyzed=result.get('factors', result.get('factors_analyzed', {})) or {},
                user_id=user_id,
                session_uuid=session_uuid,
                input_data=dict(request.data),
            )
        except Exception as save_error:
            logger.exception("Turnover history save failed: %s", save_error)
        payload = TurnoverPredictionSerializer(saved).data if saved else {
            'employee_id': employee_id,
            'prediction_score': prediction_score,
            'risk_level': str(result.get('risk_level', 'unknown')),
            'factors_analyzed': result.get('factors', result.get('factors_analyzed', {})) or {},
            'user_id': user_id,
            'session_uuid': session_uuid,
            'input_data': dict(request.data),
        }
        payload['ai_result'] = result
        return Response({'success': True, 'data': payload, 'user_id': user_id}, status=status.HTTP_200_OK)
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
def predict_leave_approval_probability(request):
    try:
        payload = request.data
        total_days = int(payload.get('total_days', 1))
        base_score = 0.85
        if total_days > 10:
            base_score -= 0.2
        elif total_days > 5:
            base_score -= 0.1

        if payload.get('leave_type_id') and int(payload['leave_type_id']) == 1:
            base_score += 0.05

        score = max(0.05, min(0.99, base_score))
        return Response({'success': True, 'data': {'approval_probability': round(score, 3)}}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Leave approval probability error: {e}")
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def recommend_benefits(request):
    try:
        employee = request.data.get('employee', {}) or {}
        benefits = request.data.get('available_benefits', [])
        tenure = float(employee.get('tenure_months', 0) or 0)
        performance = float(employee.get('performance_score', 0) or 0)
        attendance = float(employee.get('attendance_rate', 0) or 0)
        overall_score = float(employee.get('overall_score', 70) or 70)
        department = str(employee.get('department', '') or '').lower()
        role = str(employee.get('role', '') or '').lower()
        recent = {str(item).lower() for item in (employee.get('recent_benefits') or []) if item}

        recommendations = []
        for benefit in benefits:
            name = str(benefit.get('name') or '').lower()
            description = str(benefit.get('description') or '').lower()
            score = 0.05
            # Distribution of weights (Total 1.0)
            score += min(0.20, tenure / 60)
            score += min(0.15, performance / 5)
            score += min(0.15, attendance / 100)
            score += min(0.25, overall_score / 100) # Give high weight to the holistic score

            if any(term in name or term in description for term in ['health', 'medical', 'wellness', 'insurance']):
                score += 0.12
            if any(term in name or term in description for term in ['training', 'learning', 'education', 'course']):
                score += 0.08
            if any(term in name or term in description for term in ['remote', 'home', 'transport', 'commute']):
                score += 0.06
            if department and department in name:
                score += 0.08
            if role and role in name:
                score += 0.05
            if name in recent:
                score -= 0.15

            score = round(min(1.0, score), 3)
            status_text = 'not_eligible'
            if score >= 0.75:
                status_text = 'eligible'
            elif score >= 0.45:
                status_text = 'nearly_eligible'

            gaps = []
            if tenure < 24:
                gaps.append('Reach 24 months tenure')
            if performance < 4.0:
                gaps.append('Improve performance score to 4.0+')
            if attendance < 92:
                gaps.append('Maintain attendance above 92%')
            if overall_score < 75:
                gaps.append('Increase overall employee holistic score above 75')

            tags = []
            if score >= 0.75:
                tags.append('Top Match')
                tags.append('Highly Recommended')
            elif score >= 0.45:
                tags.append('Near Fit')
                tags.append('Unlock Milestone')
            
            if overall_score >= 85:
                tags.append('Elite Tier Benefit')
                
            if 'health' in name or 'medical' in description:
                tags.append('Wellness')
            if 'training' in name or 'course' in description:
                tags.append('Growth')
            if 'transport' in name or 'commute' in description:
                tags.append('Mobility')

            recommendations.append({
                'benefit_id': benefit.get('id'),
                'benefit_name': benefit.get('name'),
                'eligibility_score': score,
                'status': status_text,
                'gap_actions': gaps,
                'estimated_months_to_qualify': max(0, int((24 - tenure) / 2)) if score < 0.75 else 0,
                'reasoning': f'Your overall score of {overall_score} makes you a good candidate for this benefit.' if score >= 0.75 else 'This benefit is promising but still needs policy alignment.',
                'tags': tags or ['Balanced Fit'],
            })

        recommendations.sort(key=lambda x: x['eligibility_score'], reverse=True)
        return Response({'success': True, 'data': recommendations}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Benefit recommendation error: {e}")
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['GET'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def dashboard_insights(request):
    try:
        insights = {
            'burnout_risk_employees': [
                {'employee_id': 3, 'name': 'Montassar Mejri', 'risk_score': 0.78},
                {'employee_id': 9, 'name': 'Sara Abidine', 'risk_score': 0.71},
            ],
            'high_turnover_risk_count': 2,
            'anomaly_alerts': [
                {'type': 'attendance', 'message': 'Three days of low attendance detected in sales team.'},
            ],
            'low_workload_windows': ['2026-04-14 to 2026-04-18'],
            'benefit_utilization_rate': 0.67,
            'sentiment_trend': 'declining',
        }
        return Response({'success': True, 'data': insights}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Dashboard insights error: {e}")
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def assess_loan_risk(request):
    """Requires: Any authenticated user"""
    try:
        scorer = LoanRiskScorer()
        result = scorer.assess_risk(request.data)
        employee_id = int(request.data.get('employee_id') or 0)
        user_id_raw = request.user.get('sub')
        try:
            user_id = int(user_id_raw) if user_id_raw is not None else None
        except Exception:
            user_id = None
        session_uuid = str(request.data.get('session_id') or request.data.get('session_uuid') or '')
        safety_score = float(result.get('safety_score', 0) or 0)
        saved = None
        try:
            saved = LoanRiskAssessment.objects.create(
                loan_id=request.data.get('loan_id'),
                employee_id=employee_id,
                safety_score=int(round(safety_score * 100)),
                probability_of_default=float(result.get('probability_of_default', result.get('default_probability', 0)) or 0),
                recommendation=str(result.get('recommendation', result.get('risk_level', 'unknown'))),
                risk_tier=str(result.get('risk_tier', result.get('risk_level', 'unknown'))),
                user_id=user_id,
                session_uuid=session_uuid,
                input_data=dict(request.data),
            )
        except Exception as save_error:
            logger.exception("Loan history save failed: %s", save_error)
        payload = LoanRiskAssessmentSerializer(saved).data if saved else {
            'loan_id': request.data.get('loan_id'),
            'employee_id': employee_id,
            'safety_score': int(round(safety_score * 100)),
            'probability_of_default': float(result.get('probability_of_default', result.get('default_probability', 0)) or 0),
            'recommendation': str(result.get('recommendation', result.get('risk_level', 'unknown'))),
            'risk_tier': str(result.get('risk_tier', result.get('risk_level', 'unknown'))),
            'user_id': user_id,
            'session_uuid': session_uuid,
            'input_data': dict(request.data),
        }
        payload['ai_result'] = result
        return Response({'success': True, 'data': payload, 'user_id': user_id}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Loan risk assessment error: {e}")
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['GET'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def turnover_history(request):
    try:
        user_id = request.user.get('sub')
        query = TurnoverPrediction.objects.all().order_by('-created_at')
        if user_id is not None:
            query = query.filter(user_id=user_id)
        payload = TurnoverPredictionSerializer(query[:20], many=True).data
        return Response({'success': True, 'data': payload}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Turnover history error: {e}")
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['GET'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def loan_history(request):
    try:
        user_id = request.user.get('sub')
        query = LoanRiskAssessment.objects.all().order_by('-created_at')
        if user_id is not None:
            query = query.filter(user_id=user_id)
        payload = LoanRiskAssessmentSerializer(query[:20], many=True).data
        return Response({'success': True, 'data': payload}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Loan history error: {e}")
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
        text = request.data.get('message', '')
        session_id = request.data.get('session_id', 'default_session')
        raw_uid = request.user.get('sub')
        try:
            user_id = int(raw_uid)
        except (TypeError, ValueError):
            user_id = 0

        auth_header = request.headers.get('Authorization')
        allowed, status_code, reason, roles = _check_chatbot_access(auth_header)
        if not allowed:
            return Response({'success': False, 'error': reason}, status=status_code)

        # The engine persists the turn; the view only validates access and returns the result.
        engine = ChatbotEngine()
        result = engine.process_message(
            text,
            session_id=session_id,
            auth_header=auth_header,
            user_roles=roles,
            user_id=user_id,
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


@api_view(['GET'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def chatbot_history(request):
    """
    Return persisted chatbot conversations for the authenticated user.
    Supports optional session_id to load a specific thread.
    """
    try:
        from ai_services.models import ChatbotConversation
        from ai_services.serializers import ChatbotConversationSerializer

        auth_header = request.headers.get('Authorization')
        allowed, status_code, reason, _ = _check_chatbot_access(auth_header)
        if not allowed:
            return Response({'success': False, 'error': reason}, status=status_code)

        raw_uid = request.user.get('sub')
        try:
            user_id = int(raw_uid)
        except (TypeError, ValueError):
            user_id = 0

        session_id = request.query_params.get('session_id')

        # Legacy rows were saved with user_id=0; when a session_id is supplied, allow loading
        # that thread so existing clients keep their history after the fix.
        if session_id:
            query = (
                ChatbotConversation.objects.filter(session_uuid=session_id)
                .filter(Q(user_id=user_id) | Q(user_id=0))
                .order_by('-started_at')
            )
        else:
            query = ChatbotConversation.objects.filter(user_id=user_id).order_by('-started_at')

        conversations = query.prefetch_related('messages')[:10]
        payload = ChatbotConversationSerializer(conversations, many=True).data
        return Response({'success': True, 'data': payload}, status=status.HTTP_200_OK)
    except Exception as e:
        logger.error(f"Chatbot history error: {e}")
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

@api_view(['GET'])
@authentication_classes([LaravelJWTAuthentication])
@permission_classes([IsAuthenticated])
def get_notifications(request):
    user_id = request.user.get('sub') if isinstance(request.user, dict) else None
    now_iso = datetime.utcnow().isoformat() + 'Z'
    notifications = [
        {
            'id': f'hello-ai-backend-{user_id or "guest"}',
            'type': 'success',
            'title': 'Django AI backend is online',
            'message': 'Your AI services and prediction endpoints are ready to use for leave, turnover, and risk analysis.',
            'read': False,
            'created_at': now_iso,
            'action': '/manager',
        },
        {
            'id': f'leaves-ai-recommendation-{user_id or "guest"}',
            'type': 'info',
            'title': 'Leave prediction available',
            'message': 'Use the leave optimizer to find the best dates for the next team absence.',
            'read': False,
            'created_at': now_iso,
            'action': '/rh/leaves',
        },
    ]

    return Response({'success': True, 'data': notifications}, status=status.HTTP_200_OK)
