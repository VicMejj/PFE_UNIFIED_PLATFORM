<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Http\Controllers\Api\CallsDjangoAI;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LoanController extends ApiController
{
    use CrudTrait, CallsDjangoAI;

    protected $modelClass = \App\Models\Loan::class;
    protected $validationRules = [];

    public function index(Request $request)
    {
        return $this->crudIndex($request);
    }

    public function store(Request $request)
    {
        return $this->crudStore($request);
    }

    public function show($id)
    {
        return $this->crudShow($id);
    }

    public function update(Request $request, $id)
    {
        return $this->crudUpdate($request, $id);
    }

    public function destroy($id)
    {
        return $this->crudDestroy($id);
    }

    public function assessRisk($id)
    {
        $loan = Loan::findOrFail($id);
        $amount = $loan->approved_amount ?? $loan->principal_amount ?? 0;
        $duration = $this->resolveDurationMonths($loan);
        $response = $this->djangoPost('/api/ai/loan/assess-risk/', [
            'loan_id' => $loan->id,
            'employee_id' => $loan->employee_id,
            'amount' => $amount,
            'duration' => $duration
        ]);
        return $this->forwardDjangoResponse($response);
    }

    public function generateSchedule($id)
    {
        $loan = Loan::findOrFail($id);
        $principal = (float) ($loan->approved_amount ?? $loan->principal_amount ?? 0);
        $months = $this->resolveDurationMonths($loan);
        $rate = (float) ($loan->rate_of_interest ?? 0);

        $schedule = [];
        $balance = $principal;
        $monthlyRate = $rate > 0 ? ($rate / 100) / 12 : 0;
        $monthlyPrincipal = $months > 0 ? round($principal / $months, 2) : $principal;

        for ($i = 1; $i <= $months; $i++) {
            $interest = round($balance * $monthlyRate, 2);
            $principalPayment = min($monthlyPrincipal, $balance);
            $balance = round($balance - $principalPayment, 2);

            $schedule[] = [
                'installment' => $i,
                'principal' => $principalPayment,
                'interest' => $interest,
                'total' => round($principalPayment + $interest, 2),
                'remaining_balance' => $balance,
            ];
        }

        return $this->successResponse([
            'loan_id' => $loan->id,
            'principal' => $principal,
            'duration_months' => $months,
            'rate_of_interest' => $rate,
            'schedule' => $schedule,
        ], 'Loan schedule generated');
    }

    private function resolveDurationMonths(Loan $loan): int
    {
        if ($loan->loan_start_date && $loan->loan_end_date) {
            $start = Carbon::parse($loan->loan_start_date);
            $end = Carbon::parse($loan->loan_end_date);
            $months = $start->diffInMonths($end);
            if ($months > 0) {
                return $months;
            }
        }

        return 12;
    }
}
