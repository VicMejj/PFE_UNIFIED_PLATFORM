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
            'coverage_details' => $this->coverage_details,
            'premium' => (float) $this->premium,
            'is_active' => (bool) $this->is_active,
            'enrollments_count' => $this->enrollments()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'provider' => $this->whenLoaded('provider'),
            'enrollments' => $this->whenLoaded('enrollments'),
        ];
    }
}
