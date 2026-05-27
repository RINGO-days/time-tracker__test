@extends('layouts.app')

@section('title')
<title>勤怠一覧</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/list.css')}}">
@endsection

@section('main')
    <h1 class="title">勤怠一覧</h1>
    <div class="pagenation__box">
        <a class="page-link" href="/list?month={{$preMonth}}"><span class="arrow">◀</span>先月</a>
            <form action="/list" mothod="POST">
                <label class="calender-label" for="">
                    <input class="date-input" type="month" name="month" value="{{$targetMonth}}" onchange="this.form.submit()">
                    <span class="date-text">{{$targetMonth}}</span>
                </label>
            </form>
        <a class="page-link" href="/list?month={{$nextMonth}}">翌月<span class="arrow">▶</span></a>
    </div>
    <table class="table">
        <tr class="header_row">
            <th class="header_item">日付</th>
            <th class="header_item">出勤</th>
            <th class="header_item">退勤</th>
            <th class="header_item">休憩</th>
            <th class="header_item">合計</th>
            <th class="header_item">詳細</th>
        </tr>
        @foreach($records as $record)
            <tr class="item_row">
                <td>{{$record['date']}}（{{$record['week']}}）</td>
                <td>{{$record['attendance']}}</td>
                <td>{{$record['leave']}}</td>
                <td>{{$record['rest']}}</td>
                <td>{{$record['workingTime']}}</td>
                <td>
                    <button class="detail_button" type="submit">詳細</button>
                </td>
            </tr>
        @endforeach
    </table>
@endsection