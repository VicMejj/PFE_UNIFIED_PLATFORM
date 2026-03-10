from rest_framework import serializers
from .models import InsuranceClaimOCR, InsuranceClaimClassification, InsuranceAnomalyDetection

class InsuranceClaimOCRSerializer(serializers.ModelSerializer):
    class Meta:
        model = InsuranceClaimOCR
        fields = '__all__'

class InsuranceClaimClassificationSerializer(serializers.ModelSerializer):
    class Meta:
        model = InsuranceClaimClassification
        fields = '__all__'

class InsuranceAnomalyDetectionSerializer(serializers.ModelSerializer):
    class Meta:
        model = InsuranceAnomalyDetection
        fields = '__all__'
