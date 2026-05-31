@extends('layouts.app')

@section('title')
<title>勤怠詳細</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/detail.css')}}">
@endsection

@section('main')
    <div class="flash-message__box">
        @if (session('success'))
            <span class="flash-message">{{ session('success') }}</span>
        @endif
    </div>
    <h1 class="title">勤怠詳細</h1>
    <table class="detail-table">
            <tr class="table-raw">
                <th class="header-item">名前</th>
                <td class="table-item name__area">
                    {{$proposal->user->name}}
                </td>
            </tr>
            <tr class="table-raw">
                <th class="header-item">日付</th>
                <td class="table-item">
                    <div class="item-input__box">
                        <span>{{date('Y年',strtotime($proposal->attendance->attendance_date))}}</span>
                        <span></span>
                        <span>{{date('m月d日',strtotime($proposal->attendance->attendance_date))}}</span>
                    </div>
                </td>
            </tr>
            <tr class="table-raw">
                <th class="header-item">出勤•退勤</th>
                <td class="table-item">
                    <div class="item-input__box">
                        <span>{{$proposal->proposed_attendance['attendance_time']}}</span>
                        <span>〜</span>
                        <span>{{$proposal->proposed_attendance['leave_time']}}</span>
                    </div>
                </td>
            </tr>
            @foreach($proposal->proposed_rest as $rest)
                <tr class="table-raw">
                    @if(!$loop->first)
                        <th class="header-item">休憩{{$loop->iteration}}</th>
                    @else
                        <th class="header-item">休憩</th>
                    @endif
                    <td class="table-item">
                        <div class="item-input__box">
                            <span>{{$rest['rest_start']}}</span>
                            <span>〜</span>
                            <span>{{$rest['rest_end']}}</span>
                        </div>
                    </td>
                </tr>
            @endforeach
            <tr class="table-raw">
                <th class="header-item">備考</th>
                <td class="table-item">
                    <span class="remarks-box">{{$proposal->remarks}}</span>
                </td>
            </tr>
        </table>
        <div class="comment__inner">
            <p class="comment">※申請中のため修正できません</p>
        </div>
@endsection