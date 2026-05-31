@extends('layouts.app')

@section('title')
<title>勤怠詳細</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/detail.css')}}">
@endsection

@section('main')
    <h1 class="title">勤怠詳細</h1>
    <form action="detail/propose/{{$details['id']}}" method="POST">
        @csrf
        <table class="detail-table">
            <tr class="table-raw">
                <th class="header-item">名前</th>
                <td class="table-item name__box">{{$details['name']}}</td>
            </tr>
            <tr class="table-raw">
                <th class="header-item">日付</th>
                <td class="table-item">
                    <div class="item-input__box date-text__box">
                        <span>{{date('Y年',strtotime($details['date']))}}</span>
                        <span></span>
                        <span>{{date('n月j日',strtotime($details['date']))}}</span>
                    </div>
                </div>
                </td>
            </tr>
            <tr class="table-raw">
                <th class="header-item">出勤•退勤</th>
                <td class="table-item">
                    <div class="item-input__box">
                        <input
                            class="item-input"
                            type="text" name="attendance"
                            value="{{old("attendance",$details['attendance'])}}"
                        >
                        <span>〜</span>
                        <input
                            class="item-input"
                            type="text" name="leave"
                            value="{{old("leave",$details['leave'])}}"
                        >
                    </div>
                    @error('attendance')
                        <div class="error-box">
                            <span class="error-message">{{$message}}</span>
                        </div>
                    @enderror
                    @error('leave')
                        <div class="error-box">
                            <span class="error-message">{{$message}}</span>
                        </div>
                    @enderror
                </td>
            </tr>
            @foreach($rests as $rest)
                <tr class="table-raw">
                    @if(!$loop->first)
                        <th class="header-item">休憩{{$loop->iteration}}</th>
                    @else
                        <th class="header-item">休憩</th>
                    @endif
                    <td class="table-item">
                        <div class="item-input__box">
                            <input
                                class="item-input"
                                type="text" name="rest[{{$rest->id}}][rest_start]"
                                value="{{old("rest.{$rest->id}.rest_start", $rest->rest_start->format('H:i'))}}"
                            >
                            <span>〜</span>
                            <input
                                class="item-input" type="text"
                                name="rest[{{$rest->id}}][rest_end]"
                                value="{{old("rest.{$rest->id}.rest_end", $rest->rest_end->format('H:i'))}}"
                            >
                            <input type="hidden" name="rest[{{$rest->id}}][rest_id]" value="{{$rest->id}}">
                        </div>
                        @error("rest.{$rest->id}.rest_start")
                            <div class="error-box">
                                <span class="error-message">{{$message}}</span>
                            </div>
                        @enderror
                        @error("rest.{$rest->id}.rest_end")
                            <div class="error-box">
                                <span class="error-message">{{$message}}</span>
                            </div>
                        @enderror
                    </td>
                </tr>
            @endforeach
            <tr class="table-raw">
                <th class="header-item">休憩{{$rests->count() +1}}</th>
                <td class="table-item">
                    <div class="item-input__box">
                        <input
                            class="item-input"
                            type="text" name="rest[new][rest_start]"
                            value="{{old("rest.new.rest_start")}}"
                        >
                        <span>〜</span>
                        <input
                            class="item-input"
                            type="text" name="rest[new][rest_end]"
                            value="{{old("rest.new.rest_end")}}"
                        >
                    </div>
                    @error('rest')
                        <div class="error-box">
                            <span class="error-message">{{$message}}</span>
                        </div>
                    @enderror
                    @error('rest.new.rest_start')
                        <div class="error-box">
                            <span class="error-message">{{$message}}</span>
                        </div>
                    @enderror
                    @error('rest.new.rest_end')
                        <div class="error-box">
                            <span class="error-message">{{$message}}</span>
                        </div>
                    @enderror
                </td>
            </tr>
            <tr class="table-raw">
                <th class="header-item">備考</th>
                <td class="table-item">
                    <div class="item-input__box">
                        <textarea class="remarks-area" name="remarks" rows="3"></textarea>
                    </div>
                    @error('remarks')
                        <div class="error-box">
                            <span class="error-message">{{$message}}</span>
                        </div>
                    @enderror
                </td>
            </tr>
        </table>
        <div class="fix-button__inner">
            <button class="fix-button" type="submit">修正</button>
        </div>
    </form>
@endsection