<?php

namespace App\Services;

use App\Mail\AuthOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthOtpService
{
    public function issueAndSend(string $email, string $purpose, ?User $user = null): EmailOtp
    {
        $code = $this->generateCode();

        $otp = EmailOtp::query()->updateOrCreate(
            [
                'email' => $email,
                'purpose' => $purpose,
            ],
            [
                'user_id' => $user?->id,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes($this->expiresInMinutes()),
                'used_at' => null,
            ]
        );

        Mail::to($email)->send(
            new AuthOtpMail(
                code: $code,
                purpose: $purpose,
                expiresAt: $otp->expires_at,
            )
        );

        return $otp;
    }

    public function consume(string $email, string $purpose, string $code): ?EmailOtp
    {
        $otp = EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->first();

        if (! $otp || $otp->isExpired() || ! $otp->matches($code)) {
            return null;
        }

        $otp->forceFill([
            'used_at' => now(),
        ])->save();

        return $otp;
    }

    public function expiresInMinutes(): int
    {
        return max(1, (int) config('auth_otp.expire_minutes', 10));
    }

    public function otpLength(): int
    {
        return max(4, (int) config('auth_otp.length', 6));
    }

    private function generateCode(): string
    {
        $length = $this->otpLength();
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }
}
