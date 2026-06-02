<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'ERP HR' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand">
                <span class="brand-mark">HR</span>
                <div>
                    <strong>ERP Кадры</strong>
                    <small>Документы сотрудников</small>
                </div>
            </div>

            @isset($currentUser)
                <div class="user-panel">
                    <span>{{ $currentUser->name }}</span>
                    <strong>{{ $currentUser->roleLabel() }}</strong>
                </div>
            @endisset

            <nav class="nav">
                <a href="{{ route('requests.index') }}" class="{{ request()->routeIs('requests.index') ? 'active' : '' }}">Журнал заявок</a>
                <a href="{{ route('requests.create', ['type' => 'vacation']) }}">Отпуск</a>
                <a href="{{ route('requests.create', ['type' => 'sick_leave']) }}">Больничный</a>
            </nav>

            @isset($currentUser)
                <form method="POST" action="{{ route('logout') }}" class="logout-form">
                    @csrf
                    <button class="ghost-button" type="submit">Выйти</button>
                </form>
            @endisset
        </aside>

        <main class="content">
            @if (session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
