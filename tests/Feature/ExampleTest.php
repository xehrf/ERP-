<?php

namespace Tests\Feature;

use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_requests(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('requests.index'));
    }

    public function test_employee_can_create_vacation_request(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this
            ->withSession(['user_id' => $user->id])
            ->post(route('requests.store'), [
                'type' => 'vacation',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-03',
                'comment' => 'Ежегодный отпуск',
            ]);

        $response->assertRedirect(route('requests.index'));

        $this->assertDatabaseHas(DocumentRequest::class, [
            'user_id' => $user->id,
            'type' => 'vacation',
            'calendar_days' => 3,
            'working_days' => 3,
            'status' => 'pending_hr',
        ]);
    }
}
