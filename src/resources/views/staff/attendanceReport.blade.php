@extends('layouts.app')

@section('title')
    <title>勤怠登録</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/attendanceReport.css')}}">
@endsection

@section('main')
    <h1 class="title">マイ勤怠レポート</h1>
    <p class="title__description">過去６ヶ月の勤怠データから集計しています。</p>
    <h2 class="sub-title">基本サマリー</h2>
    <div class="item__box__inner">
        <div class="item__box">
            <p class="box-title">総労働時間</p>
            <p class="box-content">{{$formattedTotalWorkTime}}</p>
        </div>
        <div class="item__box">
            <p class="box-title">総残業時間</p>
            <p class="box-content">{{$formattedTotalOverTime}}</p>
        </div>
        <div class="item__box">
            <p class="box-title">平均労働時間/日</p>
            <p class="box-content">{{$formattedAvgTime}}</p>
        </div>
    </div>
    <h2 class="sub-title">月次推移（過去６ヶ月）</h2>
    <div class="monthly-table__inner">
        <table class="monthly-table">
            <tr class="header-row">
                <th class="header-item">月</th>
                <th class="header-item">労働時間</th>
                <th class="header-item">残業時間</th>
            </tr>
            @foreach($monthlyData as $month => $days)
                <tr class="item-row">
                    <td class="item-cell">{{$month}}</td>
                    <td class="item-cell">{{$monthlyTotalWorkTime[$month]}}</td>
                    <td class="item-cell">{{$monthlyTotalOverTime[$month]}}</td>
                </tr>
            @endforeach
        </table>
    </div>
    <h2 class="sub-title">今月の異常検知</h2>
    <p class="sub-title__description">基準：始業 09:00/終業 18:00/長時間労働は1日平均10時間超</p>
    <div class="item__box__inner">
        <div class="item__box">
            <p class="box-title">遅刻回数</p>
            <p class="box-content">{{$lateCount}}回</p>
        </div>
        <div class="item__box">
            <p class="box-title">早退回数</p>
            <p class="box-content">{{$earlyCount}}回</p>
        </div>
        <div class="item__box">
            <p class="box-title">長時間労働日数</p>
            <p class="box-content">{{$over10hourCount}}日</p>
        </div>
    </div>
@endsection