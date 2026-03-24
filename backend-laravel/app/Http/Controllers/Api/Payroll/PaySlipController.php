<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use App\Models\PaySlip;
use App\Http\Controllers\Api\CrudTrait;
use Illuminate\Http\Request;

class PaySlipController extends ApiController
{
    use CrudTrait;

    protected $modelClass = \App\Models\PaySlip::class;
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

    public function generate($id)
    {
        $paySlip = \App\Models\Payroll\PaySlip::findOrFail($id);
        $this->applyCalculatedTotals($paySlip);
        $paySlip->status = $paySlip->status ?? 'generated';
        $paySlip->save();

        return $this->successResponse($paySlip, 'Pay slip generated');
    }

    public function preview($id)
    {
        $paySlip = \App\Models\Payroll\PaySlip::findOrFail($id);
        $this->applyCalculatedTotals($paySlip);

        return $this->successResponse($paySlip, 'Pay slip preview generated');
    }

    public function send($id)
    {
        $paySlip = \App\Models\Payroll\PaySlip::findOrFail($id);
        $paySlip->status = 'sent';
        if (! $paySlip->payment_date) {
            $paySlip->payment_date = now()->toDateString();
        }
        $paySlip->save();

        return $this->successResponse($paySlip, 'Pay slip sent');
    }

    public function downloadPDF($id)
    {
        $paySlip = \App\Models\Payroll\PaySlip::findOrFail($id);

        return $this->successResponse([
            'payslip_id' => $paySlip->id,
            'message' => 'PDF generation not implemented',
        ], 'Pay slip PDF ready');
    }

    private function applyCalculatedTotals(\App\Models\Payroll\PaySlip $paySlip): void
    {
        if ($paySlip->gross_salary !== null && $paySlip->deductions !== null) {
            $paySlip->net_payable = $paySlip->calculateNetPayable();
        }
    }
}
