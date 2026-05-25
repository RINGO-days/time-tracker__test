@extends('layouts.app')

@section('title')
    <title>勤怠登録</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/attendance.css')}}">
@endsection

@section('main')
    <div class="attendance-item__box">
        <p class="status-icon">{{ $status }}</p>
        @php
            $weeks = ['月','火','水','木','金','土','日'];
            $todayWeek = $weeks[now()->dayOfWeek];
        @endphp
        <p class="date">{{now()->format('Y年n月j日')}}({{ $todayWeek }})</p>
        <p class="time">{{now()->format('H:i')}}</p>
        <form action="/attendance" method="POST">
            @csrf
            <div class="attendance-button__inner">
                @switch($status)
                    @case ('勤務外')
                        <button class="attendance-button" name="status" value=1 type="submit">出勤</button>
                        @break
                    @case('出勤中')
                        <button class="attendance-button" name="status" value=3 type="submit">退勤</button>
                        <button class="rest-button" name="status" value=2 type="submit">休憩入</button>
                        @break
                    @case('休憩中')
                        <button class="rest-button" name="status" value=1  type="submit">休憩戻</button>
                        @break
                    @case('退勤済')
                        <p class="work-finish">お疲れ様でした。</p>
                        @break
                @endswitch
            </div>
        </form>
    </div>
@endsection