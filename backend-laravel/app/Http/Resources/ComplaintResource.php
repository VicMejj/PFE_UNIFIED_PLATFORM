<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'description' => $this->description,
            'status' => $this->status,
            'resolution' => $this->resolution,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
