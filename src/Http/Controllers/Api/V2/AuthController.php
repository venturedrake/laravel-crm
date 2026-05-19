<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\IssueTokenRequest;

class AuthController extends ApiController
{
    public function issueToken(IssueTokenRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $userModel = config('auth.providers.users.model');

        $user = $userModel::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! (bool) ($user->crm_access ?? false)) {
            return response()->json([
                'message' => 'This user does not have CRM access.',
            ], 403);
        }

        $deviceName = $credentials['device_name'] ?? ($request->userAgent() ?: 'api-token');

        $token = $user->createToken($deviceName);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $this->transformUser($user),
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->transformUser($request->user()),
        ]);
    }

    public function revokeToken(Request $request): Response
    {
        $token = $request->user()->currentAccessToken();

        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        return response()->noContent();
    }

    protected function transformUser($user): array
    {
        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
