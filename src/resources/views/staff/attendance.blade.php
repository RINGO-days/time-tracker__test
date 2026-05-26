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
        <p class="date">{{now()->format('Y年n月j日')}}({{ $todayWeek }})</p>
        <p class="time">{{now()->format('H:i')}}</p>
        <form method="POST">
            @csrf
            <div class="attendance-button__inner">
                @switch($status)
                    @case ('勤務外')
                        <button class="attendance-button" type="submit" formaction="/attendance">出勤</button>
                        @break
                    @case('出勤中')
                        <button class="attendance-button" type="submit" formaction="/attendance">退勤</button>
                        <button class="rest-button" type="submit" formaction="/rest">休憩入</button>
                        @break
                    @case('休憩中')
                        <button class="rest-button" type="submit" formaction="/rest">休憩戻</button>
                        @break
                    @case('退勤済')
                        <p class="work-finish">お疲れ様でした。</p>
                        @break
                @endswitch
            </div>
        </form>
    </div>
@endsection