<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeductionController extends ApiController
{
    /**
     * Display deductions
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = app('App\Models\Payroll\SaturationDeduction')::query();

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $deductions = $query->paginate(15);

        return $this->successResponse($deductions, 'Deductions retrieved successfully');
    }

    /**
     * Store a new deduction
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'effective_date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $deduction = app('App\Models\Payroll\SaturationDeduction')::create($request->all());

        return $this->successResponse($deduction, 'Deduction created successfully', 201);
    }

    /**
     * Display the specified deduction
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $deduction = app('App\Models\Payroll\SaturationDeduction')::findOrFail($id);
        return $this->successResponse($deduction, 'Deduction retrieved successfully');
    }

    /**
     * Update the specified deduction
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $deduction = app('App\Models\Payroll\SaturationDeduction')::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'effective_date' => 'sometimes|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $deduction->update($request->all());

        return $this->successResponse($deduction, 'Deduction updated successfully');
    }

    /**
     * Delete the specified deduction
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $deduction = app('App\Models\Payroll\SaturationDeduction')::findOrFail($id);
        $deduction->delete();

        return $this->successResponse(null, 'Deduction deleted successfully');
    }

    /**
     * Get deduction options
     *
     * @return \Illuminate\Http\Response
     */
    public function getOptions()
    {
        try {
            $options = app('App\Models\Payroll\DeductionOption')::all();
            return $this->successResponse($options, 'Deduction options retrieved successfully');
        } catch (\Exception $e) {
            return $this->successResponse([], 'Deduction options fallback returned');
        }
    }
}
