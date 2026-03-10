from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import AllowAny
from rest_framework.response import Response
from rest_framework import status
import logging
from .services.ocr_service import process_claim_document

logger = logging.getLogger(__name__)

@api_view(['POST'])
@permission_classes([AllowAny])
def process_ocr_view(request, id):
    """
    Process an uploaded claim document to extract relevant data using OCR.
    """
    try:
        # In actual implementation, the file would be in request.FILES
        # For this prototype we process raw text or mock the extraction
        document_content = request.data.get('document_text', '')
        
        # If a file was uploaded
        if 'file' in request.FILES:
            # We would read the file bytes/text here
            # document_content = request.FILES['file'].read()
            # We will just pass the name for the mock to simulate
            document_content = request.FILES['file'].name + " " + document_content
            
        result = process_claim_document(document_content)
        
        return Response({
            'success': True,
            'claim_id': id,
            'extracted_data': result
        }, status=status.HTTP_200_OK)
        
    except Exception as e:
        logger.error(f"Error processing document: {str(e)}")
        return Response({
            'success': False,
            'error': str(e)
        }, status=status.HTTP_500_INTERNAL_SERVER_ERROR)

from .services.fraud_detection import detect_anomalies

@api_view(['POST'])
@permission_classes([AllowAny])
def detect_anomalies_view(request, id):
    """
    Analyze the claim for potential fraud or anomalies.
    """
    try:
        data = request.data
        claim_data = {
            'claim_amount': float(data.get('claim_amount', 0)),
            'claims_in_last_30_days': int(data.get('claims_in_last_30_days', 0))
        }
        
        result = detect_anomalies(claim_data)
        
        return Response({
            'success': True,
            'claim_id': id,
            'is_anomaly': result['is_anomaly'],
            'anomaly_score': result['anomaly_score'],
            'flags': result['flags']
        }, status=status.HTTP_200_OK)
        
    except Exception as e:
        logger.error(f"Error detecting anomalies: {str(e)}")
        return Response({
            'success': False,
            'error': str(e)
        }, status=status.HTTP_500_INTERNAL_SERVER_ERROR)
