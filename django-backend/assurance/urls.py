from django.urls import path
from . import views

urlpatterns = [
    path('api/insurance/claims/<int:id>/process-ocr', views.process_ocr_view, name='process_ocr'),
    path('api/insurance/claims/<int:id>/detect-anomalies', views.detect_anomalies_view, name='detect_anomalies'),
]
