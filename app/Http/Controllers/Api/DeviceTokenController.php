<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeviceToken\DeleteDeviceTokenRequest;
use App\Http\Requests\DeviceToken\StoreDeviceTokenRequest;
use App\Models\DeviceToken;

class DeviceTokenController extends Controller
{
    public function store(StoreDeviceTokenRequest $request)
    {
        $validated = $request->validated();

        $deviceToken = ! empty($validated['installation_id'])
            ? $request->user()->deviceTokens()->where([
                'installation_id' => $validated['installation_id'],
            ])->first()
            : null;

        $deviceToken ??= DeviceToken::where(
            'token',
            $validated['token']
        )->first();

        $deviceToken ??= new DeviceToken;

        DeviceToken::where('token', $validated['token'])
            ->when(
                $deviceToken->exists,
                fn ($query) => $query->whereKeyNot($deviceToken->id)
            )
            ->delete();

        $deviceToken->fill([
            'user_id' => $request->user()->id,
            'token' => $validated['token'],
            'platform' => $validated['platform'],
            'installation_id' => $validated['installation_id'] ?? null,
            'device_name' => $validated['device_name'] ?? null,
            'last_used_at' => now(),
        ])->save();

        return ApiResponse::success(
            'تم تسجيل الجهاز بنجاح.'
        );
    }

    public function destroy(DeleteDeviceTokenRequest $request)
    {
        $validated = $request->validated();

        $request->user()
            ->deviceTokens()
            ->where('token', $validated['token'])
            ->delete();

        return ApiResponse::success(
            'تم إلغاء تسجيل الجهاز بنجاح.'
        );
    }
}
