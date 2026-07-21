@extends('layouts.app')

@section('title')
<title>勤怠一覧</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/list.css')}}">
@endsection

@section('main')
    <h1 class="title">{{$user->name}}さんの勤怠</h1>
    <div class="pagenation__box">
        <div class="link__box">
            <a class="page-link" href="?month={{$preMonth}}">
                <img class="arrow-img" src="{{asset('img/矢印.png')}}" alt="前月">
                <span>前月</span>
            </a>
        </div>
            <form action="/admin/attendance/staff/{{$user->id}}" method="GET">
                <label class="calender-label" for="date-input">
                    <img class="calendar-icon" src="{{asset('img/カレンダー.png')}}" alt="日時選択">
                    <span class="date-text">{{\Carbon\Carbon::parse($targetMonth)->format('Y/m')}}</span>
                    <input class="date-input" id= "date-input" type="month" value="{{$targetMonth}}" name="month" onchange="this.form.submit()">
                </label>
            </form>
        <div class="link__box">
            <a class="page-link" href="?month={{$nextMonth}}">
                <span>翌月</span>
                <img class="arrow-img--inversion" src="{{asset('img/矢印.png')}}" alt="翌月">
            </a>
        </div>
    </div>
    <table class="list-table">
        <tr class="header-row">
            <th class="header-item">日付</th>
            <th class="header-item">出勤</th>
            <th class="header-item">退勤</th>
            <th class="header-item">休憩</th>
            <th class="header-item">合計</th>
            <th class="header-item">詳細</th>
        </tr>
        @foreach($records as $record)
            <tr class="item-row">
                <td class="item-cell">{{\Carbon\Carbon::parse($record['date'])->format('m/d')}}（{{$record['week']}}）</td>
                <td class="item-cell">{{$record['attendance']}}</td>
                <td class="item-cell">{{$record['leave']}}</td>
                <td class="item-cell">{{$record['rest']}}</td>
                <td class="item-cell">{{$record['actualTime']}}</td>
                <td class="item-cell">
                    @if($record['attendance_id'])
                        <a class="detail-link" href="/admin/attendance/{{$record['attendance_id']}}">詳細</a>
                    @else
                        <a class="detail-link" href="/admin/attendance/newDetail/staff/{{$user->id}}?date={{$record['date']}}">詳細</a>
                    @endif
                </td>
            </tr>
        @endforeach
        <tr class="empty-row">
            <td class="empty-item" colspan="6"></td>
        </tr>
    </table>
    <div class="csv-button__inner">
        <a class="csv-button" href="/admin/csvExport/{{$user->id}}">CSV出力</a>
    </div>
@endsection