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
        <div class="link__box">
            <a class="page-link" href="?day={{$preDay}}">
                <img class="arrow-img" src="{{asset('img/矢印.png')}}" alt="先月へ">
                <span>先日</span>
            </a>
        </div>
            <form action="/list" method="GET">
                <label class="calender-label" for="date-input">
                    <img class="calendar-icon" src="{{asset('img/カレンダー.png')}}" alt="日時選択">
                    <span class="date-text">{{\Carbon\Carbon::parse($targetDay)->format('Y/m/d')}}</span>
                    <input class="date-input" id= "date-input" type="date" value="{{$targetDay}}" name="day" onchange="this.form.submit()">
                </label>
            </form>
        <div class="link__box">
            <a class="page-link" href="?day={{$nextDay}}">
                <span>翌日</span>
                <img class="arrow-img--inversion" src="{{asset('img/矢印.png')}}" alt="翌月へ">
            </a>
        </div>    
    </div>
    <table class="list-table">
        <tr class="header-row">
            <th class="header-item name">名前</th>
            <th class="header-item">出勤</th>
            <th class="header-item">退勤</th>
            <th class="header-item">休憩</th>
            <th class="header-item">合計</th>
            <th class="header-item">詳細</th>
        </tr>
        @foreach($dailyAttendances as $dailyAttendance)
            <tr class="item-row">
                <td class="item-cell name">{{$dailyAttendance->user->name}}</td>
                <td class="item-cell">{{$dailyAttendance->attendance_time->format('H:i')}}</td>
                <td class="item-cell">{{$dailyAttendance->leave_time ? $dailyAttendance->leave_time->format(('H:i')) : ''}}</td>
                <td class="item-cell">{{$dailyAttendance->rest_total}}</td>
                <td class="item-cell">{{$dailyAttendance->actual_work_time}}</td>
                <td class="item-cell">
                    <a class="detail-link" href="/detail?date={{$targetDay->toDateString()}}&user_id={{$dailyAttendance->user->id}}">詳細</a>
                </td>
            </tr>
        @endforeach
        <tr class="empty-row">
            <td class="empty-item" colspan="6"></td>
        </tr>
@endsection