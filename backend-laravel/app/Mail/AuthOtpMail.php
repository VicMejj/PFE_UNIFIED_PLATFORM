<?php

namespace App\Mail;

use App\Models\EmailOtp;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuthOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose,
        public CarbonInterface $expiresAt,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose === EmailOtp::PURPOSE_EMAIL_VERIFICATION
                ? 'Verify your email address'
                : 'Reset your password',
        );
    }

    public function content(): Content
    {
        $isVerification = $this->purpose === EmailOtp::PURPOSE_EMAIL_VERIFICATION;
        $secondsRemaining = max(0, now()->diffInSeconds($this->expiresAt, false));
        $expiresInMinutes = max(1, (int) ceil($secondsRemaining / 60));

        return new Content(
            view: 'emails.auth-otp',
            with: [
                'title' => $isVerification ? 'Email verification code' : 'Password reset code',
                'intro' => $isVerification
                    ? 'Use this one-time password to verify your email address and activate your account.'
                    : 'Use this one-time password to reset your account password.',
                'code' => $this->code,
                'expiresInMinutes' => $expiresInMinutes,
            ],
        );
    }
}
