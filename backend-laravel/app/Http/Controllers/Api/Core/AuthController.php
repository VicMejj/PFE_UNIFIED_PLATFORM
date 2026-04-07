<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Api\ApiController;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\AuthOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends ApiController
{
    /**
     * Register a new user and send an email verification OTP.
     */
    public function register(Request $request, AuthOtpService $authOtpService)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = DB::transaction(function () use ($data, $authOtpService) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'type' => 'user',
                'avatar' => 'avatars/default.png',
                'lang' => 'en',
                'created_by' => 'system',
            ]);

            // Missing role seed data should not block registration.
            try {
                $user->assignDefaultRole();
            } catch (\Throwable $e) {
                // Role might not exist in database yet.
            }

            $authOtpService->issueAndSend(
                email: $user->email,
                purpose: EmailOtp::PURPOSE_EMAIL_VERIFICATION,
                user: $user,
            );

            return $user;
        });

        return $this->successResponse([
            'user' => $user,
            'verification_required' => true,
            'otp_expires_in_minutes' => $authOtpService->expiresInMinutes(),
        ], 'User registered. A verification code has been sent to the email address.', 201);
    }

    /**
     * Verify a registration OTP and activate the user account.
     */
    public function verifyEmailOtp(Request $request, AuthOtpService $authOtpService)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:'.$authOtpService->otpLength(),
        ]);

        $otp = $authOtpService->consume(
            email: $data['email'],
            purpose: EmailOtp::PURPOSE_EMAIL_VERIFICATION,
            code: $data['otp'],
        );

        if (! $otp) {
            return $this->errorResponse(
                'The verification code is invalid or has expired.',
                422,
                ['otp' => ['The verification code is invalid or has expired.']]
            );
        }

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user) {
            return $this->notFoundResponse('User');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $token = JWTAuth::fromUser($user);
        $user = $user->fresh()->load('roles', 'permissions');

        return $this->successResponse([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'token' => $token,
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ], 'Email verified successfully.');
    }

    /**
     * Re-send an email verification OTP for an unverified user.
     */
    public function resendEmailOtp(Request $request, AuthOtpService $authOtpService)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse([
                'verification_required' => false,
            ], 'Email address is already verified.');
        }

        $authOtpService->issueAndSend(
            email: $user->email,
            purpose: EmailOtp::PURPOSE_EMAIL_VERIFICATION,
            user: $user,
        );

        return $this->successResponse([
            'verification_required' => true,
            'otp_expires_in_minutes' => $authOtpService->expiresInMinutes(),
        ], 'A new verification code has been sent to the email address.');
    }

    /**
     * Send a password reset OTP to the user email if the account exists.
     */
    public function forgotPassword(Request $request, AuthOtpService $authOtpService)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if ($user) {
            $authOtpService->issueAndSend(
                email: $user->email,
                purpose: EmailOtp::PURPOSE_PASSWORD_RESET,
                user: $user,
            );
        }

        return $this->successResponse([
            'otp_expires_in_minutes' => $authOtpService->expiresInMinutes(),
        ], 'If an account exists for that email, a password reset code has been sent.');
    }

    /**
     * Reset a user password with a valid OTP.
     */
    public function resetPassword(Request $request, AuthOtpService $authOtpService)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:'.$authOtpService->otpLength(),
            'password' => 'required|string|min:6|confirmed',
        ]);

        $otp = $authOtpService->consume(
            email: $data['email'],
            purpose: EmailOtp::PURPOSE_PASSWORD_RESET,
            code: $data['otp'],
        );

        if (! $otp) {
            return $this->errorResponse(
                'The reset code is invalid or has expired.',
                422,
                ['otp' => ['The reset code is invalid or has expired.']]
            );
        }

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user) {
            return $this->notFoundResponse('User');
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return $this->successResponse(null, 'Password reset successfully.');
    }

    /**
     * Attempt to log a user in and return token
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            return $this->errorResponse(
                'Email address has not been verified. Please verify it before logging in.',
                403,
                ['email' => ['Please verify your email address before logging in.']]
            );
        }

        $token = JWTAuth::fromUser($user);
        $user = $user->load('roles', 'permissions');

        return $this->successResponse([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'token' => $token,
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    /**
     * Invalidate the token (logout)
     */
    public function logout(Request $request)
    {
        try {
            if ($token = JWTAuth::getToken()) {
                JWTAuth::invalidate($token);
            }
        } catch (\Throwable $e) {
            // Logout should stay idempotent even if the token is already gone.
        }

        return $this->successResponse(null, 'Successfully logged out');
    }

    /**
     * Get the authenticated user
     */
    public function me()
    {
        $user = auth()->user()->load('roles','permissions');

        return $this->successResponse([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getPermissionNames(),
        ]);
    }

    /**
     * Update the authenticated user's basic profile details.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,'.$user->id,
            'lang' => 'sometimes|nullable|string|max:10',
        ]);

        $user->fill($data);
        $user->save();

        $user->load('roles', 'permissions');

        return $this->successResponse([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getPermissionNames(),
        ], 'Profile updated successfully.');
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return $this->errorResponse(
                'The current password is incorrect.',
                422,
                ['current_password' => ['The current password is incorrect.']]
            );
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return $this->successResponse(null, 'Password updated successfully.');
    }

    /**
     * Upload or replace the authenticated user's avatar.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $directory = public_path('uploads/avatars');
        File::ensureDirectoryExists($directory);

        $file = $data['avatar'];
        $filename = 'avatar-'.$user->id.'-'.now()->timestamp.'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        $user->avatar = 'uploads/avatars/'.$filename;
        $user->save();
        $user->load('roles', 'permissions');

        return $this->successResponse([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getPermissionNames(),
        ], 'Profile photo updated successfully.');
    }

    /**
     * Persist personal UI preferences for the authenticated user.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'dark_mode' => 'sometimes|boolean',
            'lang' => 'sometimes|nullable|string|max:10',
            'messenger_color' => 'sometimes|nullable|string|max:20',
        ]);

        $user->fill($data);
        $user->save();
        $user->load('roles', 'permissions');

        return $this->successResponse([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getPermissionNames(),
        ], 'Preferences updated successfully.');
    }

    /**
     * Refresh a token
     */
    public function refresh()
    {
        return $this->successResponse([
            'token' => JWTAuth::refresh(),
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }
}
