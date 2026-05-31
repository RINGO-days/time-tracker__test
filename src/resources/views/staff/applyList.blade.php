@extends('layouts.app')

@section('title')
    <title>申請一覧</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/applyList.css')}}">
@endsection

@section('main')
    <h1 class="title">申請一覧</h1>
    <div class="tab__box">
        <a href="">承認待ち</a>
        <a href="">承認済み</a>
    </div>
    <table>
        <tr>
            <th>状態</th>
            <th>名前</th>
            <th>対象日時</th>
            <th>申請理由</th>
            <th>申請日時</th>
            <th>詳細</th>
        </tr>
        @foreach($proposals as $proposal)
            <tr>
                @switch($proposal->status)
                    @case(1)
                        <td>承認待ち</td>
                        @break
                    @case(2)
                        <td>承認済み</td>
                        @break
                    @case(3)
                        <td>棄却</td>
                        @break
                @endswitch
                <td>{{$proposal->user->name}}</td>
                <td>{{$proposal->attendance->attendance_date}}</td>
                <td>{{$proposal->remarks}}</td>
                <td>{{$proposal->created_at}}</td>
                <td><a href="">詳細</a></td>
            </tr>
        @endforeach
    </table>
@endsection