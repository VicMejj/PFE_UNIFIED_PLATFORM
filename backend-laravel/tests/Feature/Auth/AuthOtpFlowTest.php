<?php

namespace Tests\Feature\Auth;

use App\Mail\AuthOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\AuthOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthOtpFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_must_verify_email_otp_before_login(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.verification_required', true)
            ->assertJsonPath('data.user.email', 'test@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'secret123',
        ])->assertForbidden();

        $otp = $this->captureOtpCode('test@example.com', EmailOtp::PURPOSE_EMAIL_VERIFICATION);

        $this->postJson('/api/auth/verify-email-otp', [
            'email' => 'test@example.com',
            'otp' => $otp,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'test@example.com')
            ->assertJsonStructure([
                'data' => ['token'],
            ]);

        $this->assertNotNull(User::query()->where('email', 'test@example.com')->value('email_verified_at'));

        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'secret123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['token'],
            ]);
    }

    public function test_user_can_reset_password_with_password_reset_otp(): void
    {
        Mail::fake();

        $user = User::query()->create([
            'name' => 'Reset User',
            'email' => 'reset@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('oldsecret'),
            'type' => 'user',
            'avatar' => 'avatars/default.png',
            'lang' => 'en',
            'created_by' => 'system',
        ]);

        $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $otp = $this->captureOtpCode($user->email, EmailOtp::PURPOSE_PASSWORD_RESET);

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'newsecret',
            'password_confirmation' => 'newsecret',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('newsecret', $user->fresh()->password));

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'newsecret',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['token'],
            ]);
    }

    public function test_registration_rolls_back_if_otp_delivery_fails(): void
    {
        $this->mock(AuthOtpService::class, function ($mock) {
            $mock->shouldReceive('issueAndSend')
                ->once()
                ->andThrow(new \RuntimeException('SMTP failed'));
        });

        $this->postJson('/api/auth/register', [
            'name' => 'Rollback User',
            'email' => 'rollback@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(500);

        $this->assertDatabaseMissing('users', [
            'email' => 'rollback@example.com',
        ]);
    }

    private function captureOtpCode(string $email, string $purpose): string
    {
        $code = null;

        Mail::assertSent(AuthOtpMail::class, function (AuthOtpMail $mail) use ($email, $purpose, &$code) {
            if (! $mail->hasTo($email) || $mail->purpose !== $purpose) {
                return false;
            }

            $code = $mail->code;

            return true;
        });

        $this->assertNotNull($code);

        return $code;
    }
}
