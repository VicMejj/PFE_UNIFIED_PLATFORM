from rest_framework.decorators import api_view, permission_classes, authentication_classes
from rest_framework.response import Response
from rest_framework import status
import logging

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
    """Requires: Any authenticated user"""
    try:
        text = request.data.get('message', '')
        context = request.data.get('context', {})
        engine = ChatbotEngine()
        result = engine.process_message(text, context)
        return Response({'success': True, 'data': result, 'user_id': request.user.get('sub')}, status=status.HTTP_200_OK)
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
        result = classifier.classify(None)
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
