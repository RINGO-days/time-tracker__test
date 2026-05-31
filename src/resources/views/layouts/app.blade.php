<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @yield('title')
    <link rel="stylesheet" href="{{asset('css/common.css')}}">
    <link rel="stylesheet" href="{{asset('css/sanitize.css')}}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__logo">
            <img src="{{asset('img/COACHTECHヘッダーロゴ.png')}}" alt="ヘッダーロゴ">
        </div>
        @if(($nav ?? '') === 'admin')
            <nav>
                <ul class="header__nav-item">
                    <li><a href="">勤怠一覧</a></li>
                    <li><a href="">スタッフ一覧</a></li>
                    <li><a href="">申請一覧</a></li>
                    <li>
                        <form action="{{route('logout',['page' => 'admin'])}}" method="POST">
                            @csrf
                            <button class="logout-button">ログアウト</button>
                        </form>
                    </li>
                </ul>
            </nav>
        @elseif($nav ?? true)
            <nav>
                <ul class="header__nav-item">
                    <li><a href="/">勤怠</a></li>
                    <li><a href="/list">勤怠一覧</a></li>
                    <li><a href="/applyList">申請</a></li>
                    <li>
                        <form action="{{route('logout')}}" method="POST">
                            @csrf
                            <button class="logout-button">ログアウト</button>
                        </form>
                    </li>
                </ul>
            </nav>
        @endif
    </header>
    <main>
        @yield('main')
    </main>
</body>
</html>