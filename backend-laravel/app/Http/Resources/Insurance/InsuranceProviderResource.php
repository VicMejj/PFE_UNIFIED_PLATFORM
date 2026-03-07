<?php

namespace App\Http\Resources\Insurance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'contact_info' => $this->contact_info,
            'is_active' => (bool) $this->is_active,
            'policies_count' => $this->policies()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'policies' => $this->whenLoaded('policies'),
        ];
    }
}
