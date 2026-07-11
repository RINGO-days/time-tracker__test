<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class dateTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_現在の日時情報がUIと同じ形式で出力されている()
    {
        $user = User::factory()->create();

        $weeks = ['日','月','火','水','木','金','土'];
        $todayWeek = $weeks[now()->dayOfWeek];

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee(now()->format('Y年n月j日').'(' . $todayWeek . ')');
    }
}
