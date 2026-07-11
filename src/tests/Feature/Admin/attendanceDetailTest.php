<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;

class attendanceDetailTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_勤怠詳細画面に表示されるデータが選択したものになっている()
    {
        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);
        $staff = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'attendance_date' => now()->toDateString(),
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
            'status' => 3
        ]);
        $response = $this->actingAs($adminUser)->get('/admin/attendance/'.$attendance->id);
        $response->assertStatus(200);
        $response->assertSee($staff->name);
        $response->assertSee(now()->format('Y年'));
        $response->assertSee(now()->format('n月j日'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);
        $staff = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
        ]);
        $response = $this->actingAs($adminUser)->get('/admin/attendance/'.$attendance->id);
        $response->assertStatus(200);

        $formData = [
            'attendance' => '20:00',
            'leave' => '19:00'
        ];

        $response = $this->post('/detail/propose/'.$attendance->id,$formData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'leave' => '出勤時間もしくは退勤時間が不適切な値です。'
        ]);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);
        $staff = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
        ]);
        $rest = Rest::factory()->create([
            'attendance_id' => $attendance->id,
        ]);

        $response = $this->actingAs($adminUser)->get('/admin/attendance/'.$attendance->id);
        $response->assertStatus(200);

        $formData = [
            'attendance' => '09:00',
            'leave' => '18:00',
            'rest' => [
                $rest->id => [
                    'rest_start' => '19:00',
                    'rest_end' => '20:00'
                ]
            ],
            'remarks' => 'テスト'
        ];

        $response = $this->post('/detail/propose/'.$attendance->id,$formData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            "rest.{$rest->id}.rest_start"=> '休憩時間が不適切な値です。'
        ]);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);
        $staff = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
        ]);
        $rest = Rest::factory()->create();

        $response = $this->actingAs($adminUser)->get('/admin/attendance/'.$attendance->id);
        $response->assertStatus(200);

        $formData = [
            'attendance' => '09:00',
            'leave' => '18:00',
            'rest' => [
                $rest->id => [
                    'rest_start' => '17:30',
                    'rest_end' => '18:30'
                ]
            ],
            'remarks' => 'テスト'
        ];

        $response = $this->post('/detail/propose/'.$attendance->id,$formData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            "rest.{$rest->id}.rest_end"=> '休憩時間もしくは退勤時間が不適切な値です。'
        ]);
    }

    public function test_備考欄が未入力の場合のエラーメッセージが表示される()
    {
        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);
        $staff = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
        ]);
        $rest = Rest::factory()->create([
            'attendance_id' => $attendance->id,
        ]);

        $response = $this->actingAs($adminUser)->get('/admin/attendance/'.$attendance->id);
        $response->assertStatus(200);

        $formData = [
            'attendance' => '09:00',
            'leave' => '18:00',
            'rest' => [
                $rest->id => [
                    'rest_start' => '17:30',
                    'rest_end' => '18:30'
                ]
            ],
            'remarks' => ''
        ];

        $response = $this->post('/detail/propose/'.$attendance->id,$formData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'remarks'=> '備考を記入してください。'
        ]);
    }
}
