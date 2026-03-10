<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use App\Models\Loan;
use Illuminate\Http\Request;

class LoanController extends ApiController
{
    use CrudTrait;

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
        return $this->crudUpdate($request,$id);
    }

    public function destroy($id)
    {
        return $this->crudDestroy($id);
    }

    public function assessRisk($id)
    {
        $loan = Loan::findOrFail($id);
        
        try {
            // Call the Django AI Backend
            $response = \Illuminate\Support\Facades\Http::post(env('DJANGO_AI_URL', 'http://localhost:8000') . '/api/ai/loan/assess-risk/', [
                'loan_id' => $loan->id,
                'employee_id' => $loan->employee_id,
                'amount' => $loan->amount,
                'duration' => $loan->duration_months ?? 12
            ]);

            if ($response->successful()) {
                return $this->successResponse($response->json());
            }

            return response()->json(['error' => 'Failed to connect to AI Service'], 502);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
