<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\Salary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiFlowsTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    private function createEmployeeWithProfile(?array $userAttrs = []): array
    {
        $user = User::factory()->create($userAttrs);
        $employee = Employee::factory()->for($user)->create();
        return [$user, $employee];
    }

    public function test_login_returns_token_and_user(): void
    {
        $password = 'password123';
        $user = User::factory()->create(['password' => bcrypt($password)]);

        $res = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
        ])->assertStatus(200)
          ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);

        $this->assertNotEmpty($res->json('token'));
    }

    public function test_admin_can_create_employee(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $payload = [
            'name' => 'New Staff',
            'email' => 'staff@example.com',
            'password' => 'secret123',
            'role' => 'employee',
            'position' => 'Developer',
            'department' => 'IT',
            'status' => 'Đang làm việc',
        ];

        $this->postJson('/api/employees', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['message' => 'Employee created successfully']);

        $this->assertDatabaseHas('users', ['email' => 'staff@example.com', 'role' => 'employee']);
        $this->assertDatabaseHas('employees', ['position' => 'Developer', 'department' => 'IT']);
    }

    public function test_leave_request_flow_employee_create_admin_approve(): void
    {
        [$employeeUser, $employee] = $this->createEmployeeWithProfile();
        $admin = $this->createAdmin();

        Sanctum::actingAs($employeeUser);
        $start = Carbon::now()->addDays(2)->toDateString();
        $end = Carbon::now()->addDays(3)->toDateString();

        $create = $this->postJson('/api/my-leaves', [
            'start_date' => $start,
            'end_date' => $end,
            'reason' => 'Nghỉ phép năm',
            'type' => 'Nghỉ phép năm',
        ])->assertStatus(201);

        $leaveId = $create->json('data.id');
        $this->assertNotNull($leaveId);
        $this->assertDatabaseHas('leave_requests', ['id' => $leaveId, 'status' => 'Chờ duyệt']);

        // Admin approve
        Sanctum::actingAs($admin);
        $this->postJson("/api/leave-requests/{$leaveId}/status", ['status' => 'Đã duyệt'])
            ->assertStatus(200)
            ->assertJsonFragment(['status' => 'Đã duyệt']);

        $this->assertDatabaseHas('leave_requests', ['id' => $leaveId, 'status' => 'Đã duyệt']);

        // Employee sees in my list
        Sanctum::actingAs($employeeUser);
        $this->getJson('/api/my-leaves')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $leaveId]);
    }

    public function test_admin_can_manage_salaries_and_employee_can_view(): void
    {
        [$employeeUser, $employee] = $this->createEmployeeWithProfile();
        $admin = $this->createAdmin();

        Sanctum::actingAs($admin);
        $payload = [
            'employee_id' => $employee->id,
            'base_salary' => 10000000,
            'bonus' => 2000000,
            'month' => 12,
            'year' => 2025,
        ];

        $create = $this->postJson('/api/salaries', $payload)
            ->assertStatus(201)
            ->json('data');

        $this->assertEquals(12000000, $create['total']);

        // Employee can view own salaries
        Sanctum::actingAs($employeeUser);
        $this->getJson('/api/my-salaries')
            ->assertStatus(200)
            ->assertJsonFragment(['total' => 12000000]);
    }

    public function test_notifications_list_and_mark_as_read(): void
    {
        $user = User::factory()->create();
        $notifications = Notification::factory()->count(3)->for($user)->create(['is_read' => false]);

        Sanctum::actingAs($user);

        $list = $this->getJson('/api/notifications')
            ->assertStatus(200)
            ->json();

        $this->assertEquals(3, $list['unread_count']);
        $this->assertCount(3, $list['data']);

        $firstId = $notifications->first()->id;
        $this->postJson("/api/notifications/{$firstId}/mark-as-read")
            ->assertStatus(200);

        $this->assertDatabaseHas('notifications', ['id' => $firstId, 'is_read' => true]);

        $unread = $this->getJson('/api/notifications/unread-count')
            ->assertStatus(200)
            ->json('unread_count');
        $this->assertEquals(2, $unread);
    }
}
