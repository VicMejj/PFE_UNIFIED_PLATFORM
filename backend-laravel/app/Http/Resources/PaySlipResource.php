<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaySlipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'month' => $this->month,
            'year' => $this->year,
            'basic_salary' => (float) $this->basic_salary,
            'total_allowances' => (float) $this->total_allowances,
            'total_deductions' => (float) $this->total_deductions,
            'net_salary' => (float) $this->net_salary,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
