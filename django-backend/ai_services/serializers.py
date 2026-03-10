from rest_framework import serializers
from .models import (
    TurnoverPrediction, LeavePrediction, LoanRiskAssessment,
    AnomalyDetection, ChatbotConversation, ChatbotMessage,
    TrainingRecommendation, SentimentAnalysis
)

class TurnoverPredictionSerializer(serializers.ModelSerializer):
    class Meta:
        model = TurnoverPrediction
        fields = '__all__'

class LeavePredictionSerializer(serializers.ModelSerializer):
    class Meta:
        model = LeavePrediction
        fields = '__all__'

class LoanRiskAssessmentSerializer(serializers.ModelSerializer):
    class Meta:
        model = LoanRiskAssessment
        fields = '__all__'

class AnomalyDetectionSerializer(serializers.ModelSerializer):
    class Meta:
        model = AnomalyDetection
        fields = '__all__'

class ChatbotMessageSerializer(serializers.ModelSerializer):
    class Meta:
        model = ChatbotMessage
        fields = '__all__'

class ChatbotConversationSerializer(serializers.ModelSerializer):
    messages = ChatbotMessageSerializer(many=True, read_only=True)
    class Meta:
        model = ChatbotConversation
        fields = '__all__'

class TrainingRecommendationSerializer(serializers.ModelSerializer):
    class Meta:
        model = TrainingRecommendation
        fields = '__all__'

class SentimentAnalysisSerializer(serializers.ModelSerializer):
    class Meta:
        model = SentimentAnalysis
        fields = '__all__'
