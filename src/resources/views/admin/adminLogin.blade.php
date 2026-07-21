@extends('layouts.app')

@section('title')
    <title>勤怠管理アプリ＿管理者ログイン</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/auth.css')}}">
@endsection

@section('main')
    <div class="flash-message__box">
        @if (session('message'))
            <span class="flash-message">{{ session('message') }}</span>
        @endif
    </div>
    <h1 class="page-title">管理者ログイン</h1>
    <form action="{{ route('login') }}" method="POST" novalidate>
    @csrf
        <div class="form-box">
            <div class="input-box">
                <label class="input-title">メールアドレス
                    <input class="input" type="email" name="email" value="{{old('email')}}">
                    @error('email')
                        <div class="error-box">
                            <span class="error-message">{{$message}}</span>
                        </div>
                    @enderror
                </label>
            </div>
            <div class="input-box">
                <label class="input-title">パスワード
                    <input class="input" type="password" name="password">
                    @error('password')
                        <div class="error-box">
                            <span class="error-message">{{$message}}</span>
                        </div>
                    @enderror
                </label>
            </div>
            <button class="button__submit" type="sumbit">管理者ログインする</button>
        </div>
    </form>
@endsection