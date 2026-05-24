@extends('layouts.app')

@section('title')
    <title>メール認証</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/verify.css')}}">
@endsection

@section('main')
    <div class="flash-message__box">
        @if (session('status') == 'verification-link-sent')
            <span class="flash-message">新しい認証リンクを、登録されたメールアドレスに送信しました。</span>
        @endif
    </div>
    <p class="email-description">登録していただいたメールアドレスに認証メールを送付いたしました。<br>メール認証を完了してください。</p>
        <div class="verify-item__inner">
            <a class="mail-link" href="http://localhost:8025">認証はこちらから</a>
            <form action="{{ route('verification.send') }}" method="POST">
                @csrf
                <button class="resend-button" type="submit">認証メールを再送信する</button>
            </form>
        </div>

@endsection