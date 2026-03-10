from django.db import models
from core.models import BaseModel

class PredictiveReport(BaseModel):
    report_type = models.CharField(max_length=100)
    parameters = models.JSONField(default=dict)
    data = models.JSONField(default=dict)
    generated_by = models.IntegerField(help_text="References Laravel User ID")

    class Meta:
        db_table = 'analytics_predictive_reports'
