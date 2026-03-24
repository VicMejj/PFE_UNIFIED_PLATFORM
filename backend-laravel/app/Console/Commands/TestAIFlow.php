<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class TestAIFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:ai-flow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tests the E2E connection from Laravel to the Django AI Microservice';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $djangoUrl = env('DJANGO_AI_URL', 'http://localhost:8001');
        $this->info("=============================================");
        $this->info(" Testing Laravel -> Django AI Integration");
        $this->info(" Django Target URL: {$djangoUrl}");
        $this->info("=============================================\n");

        $token = $this->getJwtToken();
        if (! $token) {
            $this->error("❌ Could not generate a JWT token. Ensure an admin user exists (admin@example.com).");
            return Command::FAILURE;
        }

        $endpoints = [
            [
                'name' => 'Leave Optimal Dates',
                'path' => '/api/ai/leave/optimal-dates/',
                'payload' => ['employee_id' => 1]
            ],
            [
                'name' => 'Turnover Prediction',
                'path' => '/api/ai/turnover/predict/',
                'payload' => [
                    'employee_id' => 1,
                    'salary' => 60000,
                    'tenure_years' => 3,
                    'complaints_count' => 0,
                    'performance_score' => 4.2,
                    'leaves_taken' => 10
                ]
            ],
            [
                'name' => 'Loan Risk Assessment',
                'path' => '/api/ai/loan/assess-risk/',
                'payload' => [
                    'loan_id' => 101,
                    'employee_id' => 1,
                    'amount' => 5000,
                    'duration' => 12
                ]
            ]
        ];

        $allPassed = true;

        foreach ($endpoints as $ep) {
            $this->info("Pinging [{$ep['name']}] at {$ep['path']} ...");
            try {
                $response = Http::timeout(10)
                    ->withHeaders(['Authorization' => 'Bearer ' . $token])
                    ->post($djangoUrl . $ep['path'], $ep['payload']);
                
                if ($response->successful()) {
                    $this->info("✅ SUCCESS: " . json_encode($response->json()));
                } else {
                    $this->error("❌ FAILED: HTTP " . $response->status());
                    $this->error("Response Body: " . $response->body());
                    $allPassed = false;
                }
            } catch (\Exception $e) {
                $this->error("❌ EXCEPTION: " . $e->getMessage());
                $allPassed = false;
            }
            $this->info("---------------------------------------------");
        }

        if ($allPassed) {
            $this->info("\n🎉 All AI integration tests passed successfully! Laravel and Django are fully connected.");
            return Command::SUCCESS;
        } else {
            $this->error("\n⚠️ Some tests failed. Please check the Django logs and ensure the server is running.");
            return Command::FAILURE;
        }
    }

    private function getJwtToken(): ?string
    {
        $user = User::where('email', 'admin@example.com')->first() ?? User::first();

        if (! $user) {
            return null;
        }

        try {
            return JWTAuth::fromUser($user);
        } catch (\Throwable $e) {
            $this->error("JWT error: " . $e->getMessage());
            return null;
        }
    }
}
