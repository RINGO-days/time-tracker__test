<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class loginAdminTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_メールアドレスが未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->get('/admin/login');
        $formData = [
            'email' => '',
            'password' => 'password'
        ];
        $response = $this->post('/login',$formData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    public function test_パスワードが未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->get('/admin/login');
        $formData = [
            'email' => 'user3@example.com',
            'password' => ''
        ];
        $response = $this->post('/login',$formData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    public function test_登録内容と一致しない場合、バリデーションメッセージが表示される()
    {
        $response = $this->get('/admin/login');
        $formData = [
            'email' => 'wrong_user',
            'password' => 'password'
        ];
        $response = $this->post('/login',$formData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);
    }
}
