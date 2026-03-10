from rest_framework import serializers

# We can import serializers from individual apps, or define request/response structured serializers here.
# For simplicity and aggregation, we import them all here.

from ai_services.serializers import (
    TurnoverPredictionSerializer, LeavePredictionSerializer, LoanRiskAssessmentSerializer,
    AnomalyDetectionSerializer as AIAnomalySerializer, ChatbotConversationSerializer,
    ChatbotMessageSerializer, TrainingRecommendationSerializer, SentimentAnalysisSerializer
)

from insurance.serializers import (
    InsuranceClaimOCRSerializer, InsuranceClaimClassificationSerializer,
    InsuranceAnomalyDetectionSerializer
)

from analytics.serializers import PredictiveReportSerializer

class TurnoverPredictRequestSerializer(serializers.Serializer):
    employee_id = serializers.IntegerField()
    # add other features required for prediction

class LeaveOptimalDatesRequestSerializer(serializers.Serializer):
    employee_id = serializers.IntegerField()

class LoanRiskRequestSerializer(serializers.Serializer):
    loan_id = serializers.IntegerField(required=False)
    employee_id = serializers.IntegerField()
    amount = serializers.FloatField()
    duration = serializers.IntegerField()
