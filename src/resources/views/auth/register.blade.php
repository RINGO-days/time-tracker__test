@extends('layouts.app')

@section('title')
    <title>会員登録</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/auth.css')}}">
@endsection

@section('main')
    <h1 class="page-title">会員登録</h1>
    <form action="{{route('register')}}" method="post" novalidate>
    @csrf
        <div class="form-box">
            <div class="input-box">
                <label class="input-title">名前
                    <input class="input" type="text" name="name">
                </label>
            </div>
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
            <div class="input-box">
                <label class="input-title">パスワード確認
                    <input class="input" type="password" name="password">
                </label>
            </div>
            <button class="button__submit" type="sumbit">登録する</button>
        </div>
    </form>
    <div class="link__inner">
        <a href="/login" class="link">ログインはこちら</a>
    </div>
@endsection