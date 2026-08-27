<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(string $message,mixed $data = null,int $status = 200): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

   public static function error(string $message,string $code,int $status = 400,array $extra = []): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => array_merge(
                [
                    'code' => $code,
                ],
                $extra
            ),
        ], $status);
    }
}