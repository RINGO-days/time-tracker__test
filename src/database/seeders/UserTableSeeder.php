<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'is_admin' => 1,
            'name' => 'admin',
            'email' => 'admin@admin',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ];
        DB::table('users')->insert($param);
    }
}
