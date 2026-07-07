<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function ApiLogin(LoginRequest $request)
    {
        $user = User::where('email',$request->email)
                ->first();

        if(!$user || !Hash::check($request->password,$user->password)){
            return response()->json([
                'error' => 'ログイン情報が正しくありません。'
            ],401);
        }

        $token = $user->createToken('authApiToken')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }
}
