@extends('layouts.app')

@section('title')
<title>勤怠一覧</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/attendance.css')}}">
@endsection

@section('main')
    <h1>勤怠一覧</h1>
    <div class="pagenation__box">
        ページねーしょん
    </div>
    <table>
        <tr>
            <th>日付</th>
            <th>出勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
        </tr>
        @foreach($recodes as $recode)
            <tr>
                <td>{{$recode['date']}}</td>
                <td>{{$recode['attendance']}}</td>
                <td>{{$recode['leave']}}</td>
                <td></td>
                <td>{{$recode['workingTimes']}}</td>
            </tr>
        @endforeach
    </table>
@endsection