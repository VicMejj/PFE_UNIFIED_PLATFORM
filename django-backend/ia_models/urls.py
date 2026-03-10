from django.urls import path
from . import views

urlpatterns = [
    path('api/employees/<int:id>/turnover-prediction', views.predict_turnover_view, name='predict_turnover'),
    path('api/loans/<int:id>/assess-risk', views.assess_loan_risk_view, name='assess_loan_risk'),
    path('api/leaves/optimal-dates', views.optimal_leave_dates_view, name='optimal_leave_dates'),
]
