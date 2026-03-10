from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import AllowAny
from rest_framework.response import Response
from rest_framework import status
import logging

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
@permission_classes([AllowAny])
def predict_turnover(request):
    try:
        predictor = TurnoverPredictor()
        result = predictor.predict(request.data)
        return Response({'success': True, 'data': result}, status=status.HTTP_200_OK)
    except Exception as e:
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@permission_classes([AllowAny])
def predict_optimal_leave_dates(request):
    try:
        optimizer = LeaveOptimizer()
        result = optimizer.calculate_optimal_dates()
        return Response({'success': True, 'data': result}, status=status.HTTP_200_OK)
    except Exception as e:
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@permission_classes([AllowAny])
def assess_loan_risk(request):
    try:
        scorer = LoanRiskScorer()
        result = scorer.assess_risk(request.data)
        return Response({'success': True, 'data': result}, status=status.HTTP_200_OK)
    except Exception as e:
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@permission_classes([AllowAny])
def chatbot_send_message(request):
    try:
        text = request.data.get('message', '')
        context = request.data.get('context', {})
        engine = ChatbotEngine()
        result = engine.process_message(text, context)
        return Response({'success': True, 'data': result}, status=status.HTTP_200_OK)
    except Exception as e:
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@permission_classes([AllowAny])
def ocr_process_document(request):
    try:
        processor = OCRProcessor()
        # Mock taking an image path
        result = processor.process_image(None)
        return Response({'success': True, 'data': result}, status=status.HTTP_200_OK)
    except Exception as e:
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@permission_classes([AllowAny])
def classify_document(request):
    try:
        classifier = DocumentClassifier()
        result = classifier.classify(None)
        return Response({'success': True, 'data': result}, status=status.HTTP_200_OK)
    except Exception as e:
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@api_view(['POST'])
@permission_classes([AllowAny])
def detect_fraud(request):
    try:
        detector = FraudDetector()
        result = detector.detect_fraud(request.data)
        return Response({'success': True, 'data': result}, status=status.HTTP_200_OK)
    except Exception as e:
        return Response({'success': False, 'error': str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)
