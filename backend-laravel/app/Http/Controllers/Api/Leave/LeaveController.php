<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Http\Controllers\Api\CallsDjangoAI;
use App\Models\Leave\Leave;
use Illuminate\Http\Request;

class LeaveController extends ApiController
{
    use CrudTrait, CallsDjangoAI;

    protected $modelClass = Leave::class;
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

    public function approveByManager($id)
    {
        $leave = Leave::findOrFail($id);
        $leave->status = 'approved_by_manager';
        $leave->approved_by = auth()->id();
        $leave->save();

        return $this->successResponse($leave, 'Leave approved by manager');
    }

    public function approveByHR($id)
    {
        $leave = Leave::findOrFail($id);
        $leave->status = 'approved';
        $leave->approved_by = auth()->id();
        $leave->save();

        return $this->successResponse($leave, 'Leave approved by HR');
    }

    public function reject(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);
        $leave->status = 'rejected';
        $leave->rejected_by = auth()->id();
        $leave->save();

        return $this->successResponse($leave, 'Leave rejected');
    }

    public function getOptimalDates(Request $request)
    {
        try {
            $response = $this->djangoPost('/api/ai/leave/optimal-dates/', [
                'employee_id' => auth()->id() ?? 1,
            ]);
            return $this->forwardDjangoResponse($response);
        } catch (\Throwable $e) {
            return $this->successResponse([
                'suggested_dates' => [],
                'note' => 'AI service unavailable',
            ], 'Optimal dates fallback returned');
        }
    }
}
