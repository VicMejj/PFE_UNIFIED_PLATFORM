from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import AllowAny
from rest_framework.response import Response
from rest_framework import status
import logging
from .ml.turnover_model import predict_turnover

logger = logging.getLogger(__name__)

@api_view(['GET'])
@permission_classes([AllowAny]) # In production, this should be restricted
def predict_turnover_view(request, id):
    """
    Predict the turnover risk of an employee based on their ID.
    In a real system, this would fetch employee data from the Laravel DB
    or via an API call. For demonstration, we use query parameters or mock data.
    """
    try:
        # We simulate fetching employee features.
        # This could be parsed from query params or fetched using the id.
        employee_data = {
            'tenure_years': float(request.GET.get('tenure_years', 3.5)),
            'salary': float(request.GET.get('salary', 60000)),
            'complaints_count': int(request.GET.get('complaints_count', 0)),
            'performance_score': float(request.GET.get('performance_score', 4.0)),
            'leaves_taken': int(request.GET.get('leaves_taken', 12))
        }
        
        result = predict_turnover(employee_data)
        
        return Response({
            'success': True,
            'employee_id': id,
            'prediction': result['prediction'],
            'probability': result['probability'],
            'risk_level': result['risk_level'],
            'factors_analyzed': result['factors_analyzed']
        }, status=status.HTTP_200_OK)
        
    except Exception as e:
        logger.error(f"Error predicting turnover: {str(e)}")
        return Response({
            'success': False,
            'error': str(e)
        }, status=status.HTTP_500_INTERNAL_SERVER_ERROR)

from .ml.loan_risk import assess_loan_risk

@api_view(['POST'])
@permission_classes([AllowAny])
def assess_loan_risk_view(request, id):
    """
    Assess the risk of a loan application for an employee.
    """
    try:
        # In production this would come from request.data (the POST body)
        data = request.data
        
        loan_data = {
            'requested_amount': float(data.get('requested_amount', 5000)),
            'monthly_salary': float(data.get('monthly_salary', 4000)),
            'existing_deductions': float(data.get('existing_deductions', 500)),
            'tenure_months': float(data.get('tenure_months', 12)),
            'previous_loans_defaulted': int(data.get('previous_loans_defaulted', 0))
        }
        
        result = assess_loan_risk(loan_data)
        
        return Response({
            'success': True,
            'loan_id': id,
            'safety_score': result['safety_score'],
            'probability_of_default': result['probability_of_default'],
            'recommendation': result['recommendation'],
            'risk_tier': result['risk_tier']
        }, status=status.HTTP_200_OK)
        
    except Exception as e:
        logger.error(f"Error assessing loan risk: {str(e)}")
        return Response({
            'success': False,
            'error': str(e)
        }, status=status.HTTP_500_INTERNAL_SERVER_ERROR)
