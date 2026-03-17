from django.urls import path
from . import views
from . import pdf_reader_endpoint

urlpatterns = [
    path('ai/turnover/predict/', views.predict_turnover, name='api_predict_turnover'),
    path('ai/turnover/train/', views.train_turnover_model, name='api_train_turnover'),
    path('ai/leave/optimal-dates/', views.predict_optimal_leave_dates, name='api_optimal_leave_dates'),
    path('ai/loan/assess-risk/', views.assess_loan_risk, name='api_assess_loan_risk'),
    path('ai/chatbot/message/', views.chatbot_send_message, name='api_chatbot_message'),

    path('ai/ocr/process/', views.ocr_process_document, name='api_ocr_process'),
    path('ai/document/classify/', views.classify_document, name='api_classify_document'),
    path('ai/fraud/detect/', views.detect_fraud, name='api_detect_fraud'),
    
    path('ai/maint/read-pdf/', pdf_reader_endpoint.read_doc),
]
