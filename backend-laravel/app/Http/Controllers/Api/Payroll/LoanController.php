<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Http\Controllers\Api\CallsDjangoAI;
use App\Models\Loan;
use Illuminate\Http\Request;

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
        $response = $this->djangoPost('/api/ai/loan/assess-risk/', [
            'loan_id' => $loan->id,
            'employee_id' => $loan->employee_id,
            'amount' => $loan->amount,
            'duration' => $loan->duration_months ?? 12
        ]);
        return $this->forwardDjangoResponse($response);
    }
}
