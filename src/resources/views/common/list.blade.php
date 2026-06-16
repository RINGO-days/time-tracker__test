@extends('layouts.app')

@section('title')
<title>勤怠一覧</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/list.css')}}">
@endsection

@section('main')
    <div class="flash-message__box">
        @if (session('message'))
            <span class="flash-message">{{ session('message') }}</span>
        @endif
    </div>
    @if(auth()->user()->is_admin)
        <h1 class="title">{{$user->name}}の月次勤怠</h1>
    @else
        <h1 class="title">勤怠一覧</h1>
    @endif
    <div class="pagenation__box">
        <a class="page-link" href="/list?month={{$preMonth}}"><span class="arrow">◀</span>先月</a>
            <form action="/list" method="POST">
                <label class="calender-label" for="">
                    <input class="date-input" type="month" name="month" value="{{$targetMonth}}" onchange="this.form.submit()">
                    <span class="date-text">{{$targetMonth}}</span>
                </label>
            </form>
        <a class="page-link" href="/list?month={{$nextMonth}}">翌月<span class="arrow">▶</span></a>
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
                <td class="item-cell">{{$record['dateFormat']}}（{{$record['week']}}）</td>
                <td class="item-cell">{{$record['attendance']}}</td>
                <td class="item-cell">{{$record['leave']}}</td>
                <td class="item-cell">{{$record['rest']}}</td>
                <td class="item-cell">{{$record['actualTime']}}</td>
                <td class="item-cell">
                    @if(auth()->user()->is_admin)
                        <a class="detail-link" href="/detail?date={{$record['date']}}&user_id={{$user->id}}">詳細</a>
                    @else
                        <a class="detail-link" href="/detail?date={{$record['date']}}">詳細</a>
                    @endif
                </td>
            </tr>
        @endforeach
        <tr class="empty-row">
            <td class="empty-item" colspan="6"></td>
        </tr>
    </table>
    @if(auth()->user()->is_admin)
            <div class="csv-button__inner">
                <a class="csv-button" href="/admin/csvExport?user_id={{$user->id}}">CSV出力</a>
            </div>
    @endif
@endsection