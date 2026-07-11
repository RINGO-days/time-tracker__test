<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class registerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_名前が未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->get('/register');
        $formData = [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'testtest',
            'password_confirmation' => 'testtest',
        ];

        $response = $this->post('/register',$formData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
            ]);
    }

    public function test_メールアドレスが未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->get('/register');
        $formData = [
            'name' => 'test_user',
            'email' => '',
            'password' => 'testtest',
            'password_confirmation' => 'testtest'
        ];
        $response = $this->post('/register',$formData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    public function test_パスワードが8文字未満の場合、バリデーションメッセージが表示される()
    {
        $response = $this->get('/register');
        $formData = [
            'name' => 'test_user',
            'email' => 'test@example.com',
            'password' => 'testtes',
            'password_confirmation' => 'testtest'
        ];
        $response = $this->post('/register',$formData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください'
        ]);
    }

    public function test_パスワードが一致しない場合、バリデーションメッセージが表示される()
    {
        $response = $this->get('/register');
        $formData = [
            'name' => 'test_user',
            'email' => 'test@example.com',
            'password' => 'testtest',
            'password_confirmation' => 'testexample'
        ];
        $response = $this->post('/register',$formData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません'
        ]);
    }

    public function test_パスワードが未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->get('/register');
        $formData = [
            'name' => 'test_user',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => 'testexample'
        ];
        $response = $this->post('/register',$formData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }
    
    public function test_フォームに内容が入力されていた場合、データが正常に保存される()
    {
        $response = $this->get('/register');
        $formData = [
            'name' => 'test_user',
            'email' => 'test@example.com',
            'password' => 'testtest',
            'password_confirmation' => 'testtest'
        ];
        $response = $this->post('/register',$formData);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users',[
            'name' => 'test_user',
            'email' => 'test@example.com',
        ]);
        $response->assertRedirect('/email/verify');
    }
}
