<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            'email' => 'a@a',
            'password' => 'password',
        ];
        DB::table('users')->insert($param);
    }
}
