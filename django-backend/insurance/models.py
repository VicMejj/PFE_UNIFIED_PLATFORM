from django.db import models
from core.models import BaseModel

class InsuranceClaimOCR(BaseModel):
    claim_id = models.IntegerField(help_text="References Laravel claim ID")
    extracted_text = models.TextField()
    extracted_data = models.JSONField(default=dict)
    confidence_score = models.FloatField()

    class Meta:
        db_table = 'insurance_claim_ocr'

class InsuranceClaimClassification(BaseModel):
    claim_id = models.IntegerField(help_text="References Laravel claim ID")
    document_category = models.CharField(max_length=100)
    medical_specialty = models.CharField(max_length=100, null=True, blank=True)
    confidence_score = models.FloatField()

    class Meta:
        db_table = 'insurance_claim_classifications'

class InsuranceAnomalyDetection(BaseModel):
    claim_id = models.IntegerField(help_text="References Laravel claim ID")
    is_anomaly = models.BooleanField()
    anomaly_score = models.FloatField()
    flags = models.JSONField(default=list)

    class Meta:
        db_table = 'insurance_anomaly_detections'
