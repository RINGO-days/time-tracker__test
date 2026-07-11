<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class dailyTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_その日になされた全ユーザーの勤怠情報が正確に確認できる()
    {
        $user1 = User::factory()->create([
            'name' => 'staffUser1'
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user1->id,
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
        ]);

        $user2 = User::factory()->create([
            'name' => 'staffUser2'
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user2->id,
            'attendance_time' => '10:00:00',
            'leave_time' => '20:00:00',
        ]);

        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);
        $response = $this->actingAs($adminUser)->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee($user1->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee($user2->name);
        $response->assertSee('10:00');
        $response->assertSee('20:00');
    }
    public function test_遷移した際に現在の日付が表示される()
    {
        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);
        $response = $this->actingAs($adminUser)->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee(now()->format('Y/m/d'));
    }
    public function test_「前日」を押下した時に前の日の勤怠情報が表示される()
    {
        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);
        $staff = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'attendance_date' => now()->subDay()->toDateString()
        ]);

        $response = $this->actingAs($adminUser)->get('/admin/attendance/list?day='.now()->subDay()->format('Y-m-d'));
        $response->assertStatus(200);
        $response->assertSee(now()->subDay()->format('Y/m/d'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_「翌日」を押下した時に次の日の勤怠情報が表示される()
    {
        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);
        $staff = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'attendance_date' => now()->addDay()->toDateString()
        ]);

        $response = $this->actingAs($adminUser)->get('/admin/attendance/list?day='.now()->addDay()->format('Y-m-d'));
        $response->assertStatus(200);
        $response->assertSee(now()->addDay()->format('Y/m/d'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
}
