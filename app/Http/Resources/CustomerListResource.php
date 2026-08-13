<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerListResource extends JsonResource
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
        ];
    }
}