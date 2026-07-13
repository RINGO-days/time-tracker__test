<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\Proposal;

class attendanceDetailProposeTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
        ]);
        $formData = [
            'attendance' => '19:00',
            'leave' => '18:00'
        ];

        $response = $this->actingAs($user)->get('/attendance/detail/'.$attendance->id);
        $response->assertStatus(200);

        $response = $this->post('/detail/propose/'.$attendance->id,$formData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'leave' => '出勤時間もしくは退勤時間が不適切な値です。'
        ]);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
        ]);
        $rest = Rest::factory()->create([
            'attendance_id' => $attendance->id,
        ]);

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

        $response = $this->actingAs($user)->get('/attendance/detail/'.$attendance->id);
        $response->assertStatus(200);

        $response = $this->post('/detail/propose/'.$attendance->id,$formData);
        $response->assertSessionHasErrors([
            "rest.{$rest->id}.rest_start"=> '休憩時間が不適切な値です。'
        ]);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
        ]);
        $rest = Rest::factory()->create([
            'attendance_id' => $attendance->id,
        ]);

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

        $response = $this->actingAs($user)->get('/attendance/detail/'.$attendance->id);
        $response->assertStatus(200);

        $response = $this->post('/detail/propose/'.$attendance->id,$formData);
        $response->assertSessionHasErrors([
            "rest.{$rest->id}.rest_end"=> '休憩時間もしくは退勤時間が不適切な値です。'
        ]);
    }

    public function test_備考欄が未入力の場合のエラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
        ]);
        $rest = Rest::factory()->create([
            'attendance_id' => $attendance->id,
        ]);

        $formData = [
            'attendance' => '09:00',
            'leave' => '18:00',
            'remarks' => ''
        ];

        $response = $this->actingAs($user)->get('/attendance/detail/'.$attendance->id);
        $response->assertStatus(200);

        $response = $this->post('/detail/propose/'.$attendance->id,$formData);
        $response->assertSessionHasErrors([
            'remarks'=> '備考を記入してください。'
        ]);
    }

    public function test_修正申請処理が実行される()
    {
        $user = User::factory()->create([
            'name' => 'staffUser'
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);
        $rest = Rest::factory()->create([
            'attendance_id' => $attendance->id,
        ]);
        $formData = [
            'attendance' => '09:00',
            'leave' => '18:00',
            'rest' => [
                $rest->id => [
                    'rest_start' => '12:00',
                    'rest_end' => '13:00'
                ]
            ],
            'remarks' => 'テスト',
            'date' => $attendance->attendance_date
        ];
        $response = $this->actingAs($user)->post('/detail/propose/'.$attendance->id,$formData);
        $response->assertStatus(200);

        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);
        $proposal = Proposal::where('attendance_id',$attendance->id)
                    ->first();

        $response = $this->actingAs($adminUser)->get('/admin/stamp_correction_request/approve/'.$proposal->id);
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('承認');

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('承認待ち');
    }
    public function test_「承認待ち」にログインユーザーが行った申請が全て表示されていること()
    {
        $user = User::factory()->create([
            'name' => 'staffUser'
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_date' => now()->toDateString()
        ]);
        $rest = Rest::factory()->create();

        $formData = [
            'attendance' => '09:00',
            'leave' => '18:00',
            'rest' => [
                $rest->id => [
                    'rest_start' => '12:00',
                    'rest_end' => '13:00'
                ]
            ],
            'remarks' => 'テスト',
            'date' => $attendance->attendance_date

        ];

        $response = $this->actingAs($user)->post('/detail/propose/'.$attendance->id,$formData);
        $response->assertStatus(200);

        $proposal = Proposal::where('attendance_id',$attendance->id)
                    ->first();

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('承認待ち');
        $response->assertSee(now()->format('Y/m/d'));
    }

    public function test_「承認済み」に管理者が承認した修正申請が全て表示されている()
    {
        $user = User::factory()->create([
            'name' => 'staffUser'
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'user_id' => $user->id,
            'attendance_date' => now()->toDateString(),
        ]);
        $rest = Rest::factory()->create();

        $formData = [
            'attendance' => '09:00',
            'leave' => '18:00',
            'rest' => [
                $rest->id => [
                    'rest_start' => '12:30',
                    'rest_end' => '13:30'
                ]
            ],
            'remarks' => 'テスト',
            'date' => $attendance->attendance_date
        ];

        $response = $this->actingAs($user)->post('/detail/propose/'.$attendance->id,$formData);
        $response->assertStatus(200);

        $proposal = Proposal::where('attendance_id',$attendance->id)
                    ->first();
        $proposal->update([
            'status' => 2
        ]);

        $response = $this->get('/stamp_correction_request/list?tab=approved');
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('承認済み');
        $response->assertSee(now()->format('Y/m/d'));
    }

    public function test_各申請の「詳細」を押下すると勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create([
            'name' => 'staffUser'
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);
        $rest = Rest::factory()->create();

        $formData = [
            'attendance' => '09:00',
            'leave' => '18:00',
            'rest' => [
                $rest->id => [
                    'rest_start' => '12:30',
                    'rest_end' => '13:30'
                ]
            ],
            'remarks' => 'テスト記入',
            'date' => $attendance->attendance_date
        ];

        $response = $this->actingAs($user)->post('/detail/propose/'.$attendance->id,$formData);
        $response->assertStatus(200);

        $proposal = Proposal::where('attendance_id',$attendance->id)
                    ->first();

        $response = $this->get('/stamp_correction_request/list?tab=approved');
        $response->assertStatus(200);

        $response = $this->get('/detail/propose/'.$proposal->id);
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('テスト記入');
    }
}
