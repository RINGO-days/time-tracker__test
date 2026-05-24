@extends('layouts.app')

@section('title')
    <title>勤怠登録</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/attendance.css')}}">
@endsection

@section('main')
    <div class="attendance-item__box">
        <p class="status-icon">勤務外（後で変更）</p>
        @php
            $weeks = ['月','火','水','木','金','土','日'];
            $todayWeek = $weeks[now()->dayOfWeek];
        @endphp
        <p class="date">{{now()->format('Y年n月j日')}}({{ $todayWeek }})</p>
        <p class="time">{{now()->format('H:i')}}</p>
        <form action="">
            <div class="attendance-button__inner">
                <button class="attendance-button">出勤</button>
                <button class="rest-button">休憩入</button>
            </div>
        </form>
    </div>
@endsection