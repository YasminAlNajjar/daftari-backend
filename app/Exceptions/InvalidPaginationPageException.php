<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;

class InvalidPaginationPageException extends Exception
{
    public function __construct(
        private int $requestedPage,
        private int $lastPage
    ) {
        parent::__construct(
            'PAGE_OUT_OF_RANGE'
        );
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error(
            'رقم الصفحة المطلوبة غير موجود.',
            'PAGE_OUT_OF_RANGE',
            422,
            [
                'requested_page' =>
                    $this->requestedPage,

                'last_page' =>
                    $this->lastPage,
            ]
        );
    }
}