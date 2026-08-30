<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
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
        ];
    }
}