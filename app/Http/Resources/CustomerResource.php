<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,

            'phone' => $this->phone,

            'balance' => number_format(
                (float) $this->balance,
                2,
                '.',
                ''
            ),

            'credit_limit' => $this->credit_limit !== null
                ? number_format((float) $this->credit_limit, 2, '.', '')
                : null,

            'notes' => $this->notes,

            'created_at' => $this->created_at?->toDateTimeString(),

            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}