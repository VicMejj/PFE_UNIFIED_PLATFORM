<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use App\Models\Payroll\PaySlip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PaySlipController extends ApiController
{
    public function index(Request $request)
    {
        $query = PaySlip::query()->with('employee')->latest('id');

        if ($employeeId = $request->query('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'payroll_month' => 'required|integer|min:1|max:12',
            'payroll_year' => 'required|integer|min:2000|max:2100',
            'basic_salary' => 'nullable|numeric|min:0',
            'gross_salary' => 'required|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $paySlip = new PaySlip();
        $paySlip->fill([
            'employee_id' => $data['employee_id'],
            'payroll_month' => $data['payroll_month'],
            'payroll_year' => $data['payroll_year'],
            'basic_salary' => $data['basic_salary'] ?? $data['gross_salary'],
            'gross_salary' => $data['gross_salary'],
            'deductions' => $data['deductions'] ?? 0,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->applyCalculatedTotals($paySlip);
        $paySlip->status = 'draft';
        $paySlip->save();

        return $this->successResponse($paySlip->load('employee'), 'Pay slip created', 201);
    }

    public function show($id)
    {
        return $this->successResponse(PaySlip::with('employee')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $paySlip = PaySlip::findOrFail($id);

        $data = $request->validate([
            'employee_id' => 'sometimes|exists:employees,id',
            'payroll_month' => 'sometimes|integer|min:1|max:12',
            'payroll_year' => 'sometimes|integer|min:2000|max:2100',
            'basic_salary' => 'sometimes|nullable|numeric|min:0',
            'gross_salary' => 'sometimes|nullable|numeric|min:0',
            'deductions' => 'sometimes|nullable|numeric|min:0',
            'payment_date' => 'sometimes|nullable|date',
            'status' => 'sometimes|nullable|string|max:50',
            'notes' => 'sometimes|nullable|string|max:1000',
        ]);

        $paySlip->fill($data);
        $this->applyCalculatedTotals($paySlip);
        $paySlip->save();

        return $this->successResponse($paySlip->load('employee'));
    }

    public function destroy($id)
    {
        $paySlip = PaySlip::findOrFail($id);
        $paySlip->delete();

        return response()->json(null, 204);
    }

    public function generate($id): JsonResponse
    {
        $paySlip = PaySlip::findOrFail($id);

        $missingFields = $this->getMissingRequiredFields($paySlip);
        if ($missingFields !== []) {
            return $this->errorResponse(
                'This payslip is incomplete. Add the required payroll details before generating it.',
                422,
                ['missing_fields' => $missingFields]
            );
        }

        $this->applyCalculatedTotals($paySlip);
        $paySlip->status = 'generated';
        $paySlip->save();

        return $this->successResponse($paySlip->load('employee'), 'Pay slip generated');
    }

    public function preview($id)
    {
        $paySlip = PaySlip::findOrFail($id);
        $this->applyCalculatedTotals($paySlip);

        return $this->successResponse($paySlip, 'Pay slip preview generated');
    }

    public function send($id)
    {
        $paySlip = PaySlip::findOrFail($id);

        $missingFields = $this->getMissingRequiredFields($paySlip);
        if ($missingFields !== []) {
            return $this->errorResponse(
                'This payslip is incomplete. Add the required payroll details before sending it.',
                422,
                ['missing_fields' => $missingFields]
            );
        }

        $this->applyCalculatedTotals($paySlip);
        $paySlip->status = 'sent';
        if (! $paySlip->payment_date) {
            $paySlip->payment_date = now()->toDateString();
        }
        $paySlip->save();

        return $this->successResponse($paySlip->load('employee'), 'Pay slip sent');
    }

    public function downloadPDF($id)
    {
        $paySlip = \App\Models\Payroll\PaySlip::findOrFail($id);

        return $this->successResponse([
            'payslip_id' => $paySlip->id,
            'message' => 'PDF generation not implemented',
        ], 'Pay slip PDF ready');
    }

    private function applyCalculatedTotals(PaySlip $paySlip): void
    {
        if ($paySlip->gross_salary !== null) {
            $paySlip->deductions = $paySlip->deductions ?? 0;
            $paySlip->net_payable = $paySlip->calculateNetPayable();
        }
    }

    private function getMissingRequiredFields(PaySlip $paySlip): array
    {
        $required = [
            'employee_id' => $paySlip->employee_id,
            'payroll_month' => $paySlip->payroll_month,
            'payroll_year' => $paySlip->payroll_year,
            'gross_salary' => $paySlip->gross_salary,
        ];

        return array_keys(array_filter(
            $required,
            fn ($value) => $value === null || $value === ''
        ));
    }
}
