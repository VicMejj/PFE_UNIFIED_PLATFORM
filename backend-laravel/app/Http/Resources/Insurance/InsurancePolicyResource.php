<?php

namespace App\Http\Resources\Insurance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsurancePolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'name' => $this->name,
            'policy_name' => $this->policy_name,
            'policy_type' => $this->policy_type,
            'coverage_amount' => (float) ($this->coverage_amount ?? 0),
            'coverage_details' => $this->coverage_details,
            'premium' => (float) ($this->premium ?? 0),
            'premium_amount' => (float) ($this->premium_amount ?? 0),
            'waiting_period_days' => (int) ($this->waiting_period_days ?? 0),
            'is_active' => (bool) $this->is_active,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'enrollments_count' => $this->enrollments()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'provider' => $this->whenLoaded('provider'),
            'enrollments' => $this->whenLoaded('enrollments'),
        ];
    }
}
