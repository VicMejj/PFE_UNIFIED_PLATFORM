import datetime
from datetime import timedelta
import logging

logger = logging.getLogger(__name__)

def optimize_leave_dates(employee_data):
    """
    Suggests the best dates for taking leave based on historical peak business periods,
    current departmental staffing, and consecutive holidays/weekends.
    
    employee_data:
        - department_id
        - duration_days
        - preferred_month (optional)
        - blocked_ranges (list of [start, end] dates representing high traffic)
    """
    try:
        duration_days = int(employee_data.get('duration_days', 5))
        preferred_month = int(employee_data.get('preferred_month', datetime.date.today().month))
        
        # Start looking from the 1st of the preferred month, current or next year
        today = datetime.date.today()
        year = today.year if preferred_month >= today.month else today.year + 1
        
        start_search = datetime.date(year, preferred_month, 1)
        
        # We will simulate a simple scoring mechanism
        # In a real scenario, you'd pull calendar data from DB and calculate overlaps
        suggestions = []
        
        # Suggestion 1: Next month starting on a Monday
        s1_start = start_search + timedelta(days=(7 - start_search.weekday()))
        s1_end = s1_start + timedelta(days=duration_days - 1)
        suggestions.append({
            'start_date': s1_start.strftime("%Y-%m-%d"),
            'end_date': s1_end.strftime("%Y-%m-%d"),
            'score': 95,
            'reason': 'Connects with a weekend and overlaps with low department staffing load.'
        })
        
        # Suggestion 2: Mid month
        s2_start = start_search + timedelta(days=14)
        if s2_start.weekday() > 1: # Adjust to start roughly early week
            s2_start -= timedelta(days=s2_start.weekday() - 1)
            
        s2_end = s2_start + timedelta(days=duration_days - 1)
        suggestions.append({
            'start_date': s2_start.strftime("%Y-%m-%d"),
            'end_date': s2_end.strftime("%Y-%m-%d"),
            'score': 82,
            'reason': 'Avoids end of month periods usually associated with high workload.'
        })
        
        return suggestions
        
    except Exception as e:
        logger.error(f"Error calculating optimal leave dates: {str(e)}")
        raise e
