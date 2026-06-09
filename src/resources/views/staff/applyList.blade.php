@extends('layouts.app')

@section('title')
    <title>申請一覧</title>
@endsection

@section('css')
    <link rel="stylesheet" href="{{asset('css/applyList.css')}}">
@endsection

@section('main')
    <div class="flash-message__box">
        @if (session('message'))
            <span class="flash-message">{{ session('message') }}</span>
        @endif
    </div>
    <h1 class="title">申請一覧</h1>
    <div class="tab__box">
        <a class="tab-link {{request('tab') !== 'approved' ? 'active' : ''}}" href="?tab=pending">承認待ち</a>
        <a class="tab-link {{request('tab') === 'approved' ? 'active' : ''}}" href="?tab=approved">承認済み</a>
    </div>
    <table class="apply-table">
        <tr class="header-raw">
            <th class="header-item">状態</th>
            <th class="header-item">名前</th>
            <th class="header-item">対象日時</th>
            <th class="header-item">申請理由</th>
            <th class="header-item">申請日時</th>
            <th class="header-item">詳細</th>
        </tr>
        @if(request('tab') !== 'approved')
            @foreach($proposals as $proposal)
                <tr class="item-raw">
                    @switch($proposal->status)
                        @case(1)
                            <td class="table-item">承認待ち</td>
                            @break
                        @case(2)
                            <td class="table-item">承認済み</td>
                            @break
                        @case(3)
                            <td class="table-item">棄却</td>
                            @break
                    @endswitch
                    <td class="table-item">{{$proposal->user->name}}</td>
                    <td class="table-item">{{date('Y/m/d',strtotime($proposal->attendance->attendance_date))}}</td>
                    <td class="table-item">{{$proposal->remarks}}</td>
                    <td class="table-item">{{$proposal->updated_at->format('Y/m/d')}}</td>
                    <td class="table-item">
                        <a class="detail-link" href="/detail/propose/{{ $proposal->id }}">詳細</a>
                    </td>
                </tr>
            @endforeach
        @endif
        <tr class="empty-raw">
            <td class="empty-item" colspan="6"></td>
        </tr>
    </table>
@endsection