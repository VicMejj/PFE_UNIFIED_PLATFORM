from django.urls import path
from . import views
from . import pdf_reader_endpoint

urlpatterns = [
    path('ai/turnover/predict/', views.predict_turnover, name='api_predict_turnover'),
    path('ai/turnover/train/', views.train_turnover_model, name='api_train_turnover'),
    path('ai/leave/optimal-dates/', views.predict_optimal_leave_dates, name='api_optimal_leave_dates'),
    path('ai/leave/approval-probability/', views.predict_leave_approval_probability, name='api_leave_approval_probability'),
    path('ai/benefits/recommend/', views.recommend_benefits, name='api_recommend_benefits'),
    path('ai/insights/dashboard/', views.dashboard_insights, name='api_dashboard_insights'),
    path('ai/loan/assess-risk/', views.assess_loan_risk, name='api_assess_loan_risk'),
    path('ai/chatbot/message/', views.chatbot_send_message, name='api_chatbot_message'),

    path('ai/ocr/process/', views.ocr_process_document, name='api_ocr_process'),
    path('ai/document/classify/', views.classify_document, name='api_classify_document'),
    path('ai/fraud/detect/', views.detect_fraud, name='api_detect_fraud'),
    path('notifications/', views.get_notifications, name='api_notifications'),
    path('ai/maint/read-pdf/', pdf_reader_endpoint.read_doc),
]
