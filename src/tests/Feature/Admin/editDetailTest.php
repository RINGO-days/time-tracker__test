<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\Proposal;


class editDetailTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_承認待ちの修正申請が全て表示されている()
    {
        $staff1 = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff1->id,
            'attendance_date' => now()->subMonthNoOverflow()->format('Y-m-d'),
        ]);
        $proposal = Proposal::create([
            'user_id' => $staff1->id,
            'attendance_id' => $attendance->id,
            'proposal_attendance' => [
                'attendance_time' => '10:00',
                'leave_time' => '20:00',
            ],
            'proposal_rest' => [
                'rest_id' => 1,
                'rest_start' => '13:00',
                'rest_end' => '14:00',
            ],
            'remarks' => 'テスト',
            'status' => 1,
            'updated_at' => now()->toDateString()
        ]);

        $staff2 = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff2->id,
            'attendance_date' => now()->toDateString(),
        ]);
                $proposal = Proposal::create([
            'user_id' => $staff2->id,
            'attendance_id' => $attendance->id,
            'proposal_attendance' => [
                'attendance_time' => '10:00',
                'leave_time' => '20:00',
            ],
            'proposal_rest' => [
                'rest_id' => 1,
                'rest_start' => '13:00',
                'rest_end' => '14:00',
            ],
            'remarks' => 'テスト',
            'status' => 1,
            'updated_at' => now()->toDateString()
        ]);


        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);

        $response = $this->actingAs($adminUser)->get('/stamp_correction_request/list?tab=pending');
        $response->assertStatus(200);
        $response->assertSee($staff1->name);
        $response->assertSee(now()->subMonthNoOverflow()->format('Y/m/d'));
        $response->assertSee(now()->format('Y/m/d'));

        $response->assertSee($staff2->name);
        $response->assertSee(now()->format('Y/m/d'));

    }

    public function test_承認済みの修正申請が全て表示されている()
    {
        $staff1 = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff1->id,
            'attendance_date' => now()->subMonthNoOverflow()->format('Y-m-d'),
        ]);
        $proposal = Proposal::create([
            'user_id' => $staff1->id,
            'attendance_id' => $attendance->id,
            'proposed_attendance' => [
                'attendance_time' => '10:00',
                'leave_time' => '20:00',
            ],
            'proposed_rest' => [
                [
                    'rest_id' => 1,
                    'rest_start' => '13:00',
                    'rest_end' => '14:00',
                ]
            ],
            'remarks' => 'テスト',
            'status' => 2,
            'updated_at' => now()->toDateString()
        ]);

        $staff2 = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff2->id,
            'attendance_date' => now()->toDateString(),
        ]);
                $proposal = Proposal::create([
            'user_id' => $staff2->id,
            'attendance_id' => $attendance->id,
            'proposed_attendance' => [
                'attendance_time' => '10:00',
                'leave_time' => '20:00',
            ],
            'proposed_rest' => [
                [
                    'rest_id' => 1,
                    'rest_start' => '13:00',
                    'rest_end' => '14:00',
                ]
            ],
            'remarks' => 'テスト',
            'status' => 2,
            'updated_at' => now()->toDateString()
        ]);


        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);

        $response = $this->actingAs($adminUser)->get('/stamp_correction_request/list?tab=approved');
        $response->assertStatus(200);
        $response->assertSee($staff1->name);
        $response->assertSee(now()->subMonthNoOverflow()->format('Y/m/d'));
        $response->assertSee(now()->format('Y/m/d'));

        $response->assertSee($staff2->name);
        $response->assertSee(now()->format('Y/m/d'));
    }

    public function test_修正申請の詳細内容が正しく表示されている()
    {
        $staff = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'attendance_date' => now()->toDateString()
        ]);
        $rest = Rest::factory()->create([
            'attendance_id' => $attendance->id,
        ]);
        $proposal = Proposal::create([
            'user_id' => $staff->id,
            'attendance_id' => $attendance->id,
            'proposed_attendance' => [
                'attendance_time' => '10:00',
                'leave_time' => '20:00',
            ],
            'proposed_rest' => [
                [
                'rest_id' => 1,
                'rest_start' => '13:00',
                'rest_end' => '14:00',
                ]
            ],
            'remarks' => 'テスト',
            'status' => 1,
            'updated_at' => now()->toDateString()
        ]);

        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);
        $response = $this->actingAs($adminUser)->get('/admin/stamp_correction_request/approve/'.$proposal->id);
        $response->assertStatus(200);
        $response->assertSee($staff->name);
        $response->assertSee('10:00');
        $response->assertSee('20:00');
        $response->assertSee('13:00');
        $response->assertSee('14:00');
        $response->assertSee('テスト');
    }

    public function test_修正申請の承認処理が正しく行われる()
    {
        $staff = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'attendance_date' => now()->toDateString()
        ]);
        $rest = Rest::factory()->create();
        $proposal = Proposal::create([
            'user_id' => $staff->id,
            'attendance_id' => $attendance->id,
            'proposed_attendance' => [
                'attendance_time' => '10:00',
                'leave_time' => '20:00',
            ],
            'proposed_rest' => [
                [
                'rest_id' => $rest->id,
                'rest_start' => '13:00:00',
                'rest_end' => '14:00:00',
                ]
            ],
            'remarks' => 'テスト',
            'status' => 1,
            'updated_at' => now()->toDateString()
        ]);

        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);
        $response = $this->actingAs($adminUser)->post('/admin/stamp_correction_request/approve/update/'.$proposal->id);
        $response->assertStatus(302);

        $response = $this->get('/admin/stamp_correction_request/approve/'.$proposal->id);
        $response->assertSee('承認済み');
        $this->assertDatabaseHas('attendances',[
            'attendance_time' => '10:00',
            'leave_time' => '20:00',
            ]);
        $this->assertDatabaseHas('rests',[
            'id' => $rest->id,
            'rest_start' => '13:00:00',
            'rest_end' => '14:00:00',
            ]);
    }
}
