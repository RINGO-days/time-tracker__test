@extends('layouts.app')

@section('title')
    <title>ログイン</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/auth.css')}}">
@endsection

@section('main')
    <h1 class="page-title">ログイン</h1>
    <form action="{{route('login')}}" method="post" novalidate>
    @csrf
        <div class="form-box">
            <div class="input-box">
                <label class="input-title">メールアドレス
                    <input class="input" type="email" name="email">
                </label>
            </div>
            <div class="input-box">
                <label class="input-title">パスワード
                    <input class="input" type="password" name="password">
                </label>
            </div>
            <button class="button__submit" type="sumbit">ログインする</button>
        </div>
    </form>
    <div class="link__inner">
        <a href="/register" class="link">会員登録はこちら</a>
    </div>
@endsection