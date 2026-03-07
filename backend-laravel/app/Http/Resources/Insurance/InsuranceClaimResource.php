<?php

namespace App\Http\Resources\Insurance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'enrollment_id' => $this->enrollment_id,
            'claim_number' => $this->claim_number,
            'status' => $this->status,
            'total_amount' => (float) $this->total_amount,
            'date_filed' => $this->date_filed,
            'items_count' => $this->items()->count(),
            'documents_count' => $this->documents()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'enrollment' => $this->whenLoaded('enrollment'),
            'items' => $this->whenLoaded('items'),
            'documents' => $this->whenLoaded('documents'),
            'history' => $this->whenLoaded('history'),
        ];
    }
}
