@extends('layouts.app')

@section('title')
    <title>申請一覧</title>
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
    <h1 class="title approve">勤怠詳細</h1>
    <form action="/admin/stamp_correction_request/approve/update/{{$proposal->id}}" method="post">
        @csrf
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
                        <span>{{date('Y年',strtotime($proposal->target_date))}}</span>
                        <span></span>
                        <span>{{date('m月d日',strtotime($proposal->target_date))}}</span>
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
            @foreach($proposal->proposed_rest ?? [] as $rest)
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
        @if($proposal->status === 1)
            <div class="fix-button__inner">
                <button class="fix-button" type="submit">承認</button>
            </div>
        @else
            <div class="approve-icon__inner">
                <span class="approve-icon">承認済み</span>
            </div>
        @endif
    </form>
@endsection