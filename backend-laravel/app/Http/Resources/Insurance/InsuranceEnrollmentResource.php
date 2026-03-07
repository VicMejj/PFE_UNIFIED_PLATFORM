<?php

namespace App\Http\Resources\Insurance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceEnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'policy_id' => $this->policy_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'dependents_count' => $this->dependents()->count(),
            'claims_count' => $this->claims()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'employee' => $this->whenLoaded('employee'),
            'policy' => $this->whenLoaded('policy'),
            'dependents' => $this->whenLoaded('dependents'),
            'claims' => $this->whenLoaded('claims'),
        ];
    }
}
