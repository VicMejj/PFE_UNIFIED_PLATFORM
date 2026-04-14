from django.db import models
from core.models import BaseModel

class TurnoverPrediction(BaseModel):
    employee_id = models.IntegerField(help_text="References Laravel employee ID")
    prediction_score = models.FloatField()
    risk_level = models.CharField(max_length=50)
    factors_analyzed = models.JSONField(default=dict)
    user_id = models.IntegerField(null=True, blank=True)
    session_uuid = models.CharField(max_length=255, null=True, blank=True)
    input_data = models.JSONField(default=dict)
    
    class Meta:
        db_table = 'ai_turnover_predictions'


class LeavePrediction(BaseModel):
    employee_id = models.IntegerField(help_text="References Laravel employee ID")
    suggested_start_date = models.DateField()
    suggested_end_date = models.DateField()
    score = models.IntegerField()
    reason = models.TextField()

    class Meta:
        db_table = 'ai_leave_predictions'


class LoanRiskAssessment(BaseModel):
    loan_id = models.IntegerField(help_text="References Laravel loan ID", null=True, blank=True)
    employee_id = models.IntegerField(help_text="References Laravel employee ID")
    safety_score = models.IntegerField()
    probability_of_default = models.FloatField()
    recommendation = models.CharField(max_length=100)
    risk_tier = models.CharField(max_length=50)
    user_id = models.IntegerField(null=True, blank=True)
    session_uuid = models.CharField(max_length=255, null=True, blank=True)
    input_data = models.JSONField(default=dict)

    class Meta:
        db_table = 'ai_loan_risk_assessments'


class AnomalyDetection(BaseModel):
    event_type = models.CharField(max_length=100)
    entity_id = models.IntegerField(help_text="Generic ID reference to Laravel entity")
    is_anomaly = models.BooleanField()
    anomaly_score = models.FloatField()
    flags = models.JSONField(default=list)

    class Meta:
        db_table = 'ai_anomaly_detections'


class ChatbotConversation(BaseModel):
    user_id = models.IntegerField(help_text="References Laravel user ID")
    session_uuid = models.CharField(max_length=255)
    memory = models.JSONField(default=dict, help_text="Stored context like user name, role, etc.")
    started_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = 'ai_chatbot_conversations'


class ChatbotMessage(BaseModel):
    conversation = models.ForeignKey(ChatbotConversation, on_delete=models.CASCADE, related_name='messages')
    sender = models.CharField(max_length=50, choices=(('USER', 'User'), ('BOT', 'Bot')))
    text = models.TextField()
    intent = models.CharField(max_length=100, null=True, blank=True)
    entities = models.JSONField(default=dict)

    class Meta:
        db_table = 'ai_chatbot_messages'


class TrainingRecommendation(BaseModel):
    employee_id = models.IntegerField(help_text="References Laravel employee ID")
    recommended_course_id = models.IntegerField()
    confidence_score = models.FloatField()
    reason = models.TextField()

    class Meta:
        db_table = 'ai_training_recommendations'


class SentimentAnalysis(BaseModel):
    source_type = models.CharField(max_length=50, help_text="e.g., ticket, feedback, complaint")
    source_id = models.IntegerField()
    sentiment_score = models.FloatField()
    emotion = models.CharField(max_length=50)
    urgency_level = models.CharField(max_length=50)

    class Meta:
        db_table = 'ai_sentiment_analyses'
