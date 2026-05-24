@extends('layouts.app')

@section('title')
    <title>ログイン</title>
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
    <h1 class="page-title">
        @if(request()->query('page') ==='admin')
            管理者ログイン
        @else
            ログイン
        @endif
    </h1>
    <form action="{{route('login')}}" method="post" novalidate>
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
            <button class="button__submit" type="sumbit">
                @if(request()->query('page') ==='admin')
                    管理者ログインする
                @else
                    ログインする
                @endif
            </button>
        </div>
    </form>
    @if(request()->query('page') !== 'admin')
        <div class="link__inner">
            <a href="/register" class="link">会員登録はこちら</a>
        </div>
    @endif
@endsection