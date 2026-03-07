<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'destination' => $this->destination,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'purpose' => $this->purpose,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
