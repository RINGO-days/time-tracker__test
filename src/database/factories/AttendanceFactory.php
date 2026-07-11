<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'attendance_date' => now()->toDateString(),
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
            'status' => 3
        ];
    }
}
