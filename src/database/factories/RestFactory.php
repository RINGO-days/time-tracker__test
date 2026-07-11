<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;

class RestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'attendance_id' => Attendance::factory(),
            'rest_start' => '12:00:00',
            'rest_end' => '13:00:00'
        ];
    }
}
