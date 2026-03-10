from datetime import date, timedelta
import random


class LeaveOptimizer:
    """
    Suggests optimal leave dates based on:
    - Day of week: Fridays/Mondays preferred (longer effective breaks)
    - Peak periods: Avoids month-end, quarter-end
    - Consecutive dates for proper rest
    """

    PEAK_MONTHS = [3, 6, 9, 12]  # Quarter-end months (high workload)
    PEAK_DAYS_IN_MONTH = list(range(25, 32))  # End-of-month days (payroll, closings)

    def _is_peak_day(self, d: date) -> bool:
        """Returns True if a date falls on a high-workload period."""
        return d.month in self.PEAK_MONTHS and d.day in self.PEAK_DAYS_IN_MONTH

    def _score_date(self, d: date) -> float:
        """
        Score a date from 0.0 (worst) to 1.0 (best) for taking leave.
        Higher score = better to take leave.
        """
        score = 0.5

        # Weekdays only (skip weekends — they don't consume leave days)
        if d.weekday() >= 5:
            return 0.0

        # Prefer Fridays and Mondays (extends the weekend)
        if d.weekday() == 4:  # Friday
            score += 0.25
        elif d.weekday() == 0:  # Monday
            score += 0.20

        # Penalize peak periods
        if self._is_peak_day(d):
            score -= 0.50

        # Prefer mid-month (less pressure)
        if 10 <= d.day <= 20:
            score += 0.15

        return round(max(0.0, min(1.0, score)), 2)

    def calculate_optimal_dates(self, employee_id: int = None, num_suggestions: int = 5) -> dict:
        """
        Analyze the next 60 days and return the top optimal leave windows.
        """
        today = date.today()
        candidates = []

        for i in range(7, 61):  # Start 1 week from now
            d = today + timedelta(days=i)
            score = self._score_date(d)
            if score > 0.4:  # Only include reasonable days
                candidates.append({"date": d.isoformat(), "score": score, "weekday": d.strftime("%A")})

        # Sort by score desc
        candidates.sort(key=lambda x: x["score"], reverse=True)

        # Build consecutive windows (2-day, 3-day blocks)
        windows = self._find_windows(today)

        return {
            "recommended_single_days": candidates[:num_suggestions],
            "recommended_windows": windows[:3],
            "avoid_periods": self._get_peak_periods(today),
            "analysis_period": f"{today.isoformat()} to {(today + timedelta(days=60)).isoformat()}"
        }

    def _find_windows(self, today: date) -> list:
        """Find the best 3-5 day consecutive leave windows."""
        windows = []
        for start_offset in range(7, 55):
            start = today + timedelta(days=start_offset)
            if start.weekday() == 0:  # Start on Monday
                end = start + timedelta(days=4)  # Full work week
                avg_score = sum(self._score_date(start + timedelta(days=k)) for k in range(5)) / 5
                if avg_score > 0.45:
                    windows.append({
                        "start": start.isoformat(),
                        "end": end.isoformat(),
                        "duration_days": 5,
                        "suitability_score": round(avg_score, 2),
                        "note": "Full week — maximizes recovery, minimal disruption if planned"
                    })

        windows.sort(key=lambda x: x["suitability_score"], reverse=True)
        return windows[:3]

    def _get_peak_periods(self, today: date) -> list:
        """Return dates to avoid in the next 60 days."""
        avoid = []
        for i in range(0, 61):
            d = today + timedelta(days=i)
            if self._is_peak_day(d):
                avoid.append(d.isoformat())
        return avoid
