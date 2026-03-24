<?php

namespace Tests\Feature\Organization;

use App\Models\Organization\Designation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_designation_update_persists_code_changes(): void
    {
        $designation = Designation::create([
            'title' => 'Logistics',
            'code' => 'old-code',
            'description' => 'Original description',
        ]);

        $response = $this
            ->withoutMiddleware()
            ->putJson("/api/organization/designations/{$designation->id}", [
                'code' => 'log',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.code', 'log');

        $this->assertDatabaseHas('designations', [
            'id' => $designation->id,
            'code' => 'log',
        ]);
    }
}
