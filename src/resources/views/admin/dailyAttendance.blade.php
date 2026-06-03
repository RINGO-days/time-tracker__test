@extends('layouts.app')

@section('title')
    <title>勤怠一覧</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/list.css')}}">
@endsection

@section('main')
    <h1 class="title">{{$targetDay->format('Y年m月d日')}}の勤怠</h1>
    <div class="pagenation__box">
        <a class="page-link" href="?day={{$preDay}}"><span class="arrow">◀</span>前日</a>
            <form action="" mothod="POST">
                <label class="calender-label" for="">
                    <input class="date-input" type="date" name="day" value="{{$targetDay}}" onchange="this.form.submit()">
                    <span class="date-text">{{$targetDay->format('Y/m/d')}}</span>
                </label>
            </form>
        <a class="page-link" href="?day={{$nextDay}}">翌日<span class="arrow">▶</span></a>
    </div>
    <table class="list-table">
        <tr class="header-row">
            <th class="header-item">名前</th>
            <th class="header-item">出勤</th>
            <th class="header-item">退勤</th>
            <th class="header-item">休憩</th>
            <th class="header-item">合計</th>
            <th class="header-item">詳細</th>
        </tr>
        @foreach($dailyAttendances as $dailyAttendance)
            <tr class="item-row">
                <td>{{$dailyAttendance->user->name}}</td>
                <td>{{$dailyAttendance->attendance_time->format('H:i')}}</td>
                <td>{{$dailyAttendance->leave_time ? $dailyAttendance->leave_time->format(('H:i')) : ''}}</td>
                <td>{{$dailyAttendance->rest_total}}</td>
                <td>{{$dailyAttendance->actual_work_time}}</td>
                <td>
                    <a class="detail-link" href="/detail?date={{$targetDay->toDateString()}}&user_id={{$dailyAttendance->user->id}}">詳細</a>
                </td>
            </tr>
        @endforeach
        <tr class="empty-row">
            <td class="empty-item" colspan="6"></td>
        </tr>
@endsection