from rest_framework import serializers
from .models import PredictiveReport

class PredictiveReportSerializer(serializers.ModelSerializer):
    class Meta:
        model = PredictiveReport
        fields = '__all__'
