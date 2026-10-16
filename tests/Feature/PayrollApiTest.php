<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class PayrollApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
    }
    public function test_admin_can_login_and_receive_token(): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@zivbiyt.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'token',
                'user' => ['id', 'name', 'email', 'roles'],
            ]);

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        return $token;
    }

    public function test_admin_can_view_dashboard_kpis(): void
    {
        $token = $this->test_admin_can_login_and_receive_token();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_employees',
                    'present_today',
                    'on_duty_field_staff',
                    'departments',
                ],
            ]);

        $this->assertGreaterThan(0, $response->json('data.total_employees'));
    }

    public function test_admin_can_list_employees(): void
    {
        $token = $this->test_admin_can_login_and_receive_token();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/employees');

        $response->assertStatus(200);
        $this->assertCount(8, $response->json('data.data'));
    }

    public function test_admin_can_create_and_calculate_payroll(): void
    {
        $token = $this->test_admin_can_login_and_receive_token();

        // 1. Create run
        $createRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/payroll-runs', [
                'payroll_month' => '2026-10',
            ]);

        $createRes->assertStatus(200);
        $runId = $createRes->json('data.id');

        // 2. Calculate
        $calcRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/payroll-runs/{$runId}/calculate");

        $calcRes->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertGreaterThan(0, $calcRes->json('data.total_net_pay'));
    }
}
