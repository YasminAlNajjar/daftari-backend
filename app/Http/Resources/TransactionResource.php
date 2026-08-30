<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'type' => $this->type,
            'amount' => number_format(
                (float) $this->amount,
                2,
                '.',
                ''
            ),
            'description' => $this->description,
            'transaction_date' => $this->transaction_date
                ?->toDateTimeString(),
            'created_at' => $this->created_at
                ?->toDateTimeString(),
            'updated_at' => $this->updated_at
                ?->toDateTimeString(),
        ];
    }
}