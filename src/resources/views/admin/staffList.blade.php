@extends('layouts.app')

@section('title')
    <title>勤怠一覧</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/staffList.css')}}">
@endsection

@section('main')
    <h1 class="title">スタッフ一覧</h1>
    <table class="list-table">
        <tr class="header-row">
            <th class="header-item">名前</th>
            <th class="header-item">メールアドレス</th>
            <th class="header-item">月次勤怠</th>
        </tr>
        @foreach($staffs as $staff)
            <tr class="item-row">
                <td class="item-cell">{{$staff->name}}</td>
                <td class="item-cell">{{$staff->email}}</td>
                <td class="item-cell">
                    <a class="detail-link" href="/admin/attendance/staff/{{$staff->id}}">詳細</a>
                </td>
            </tr>
        @endforeach
        <tr class="empty-row">
            <td class="empty-item" colspan="6"></td>
        </tr>
    </table>
@endsection