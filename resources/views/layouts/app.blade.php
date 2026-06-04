<!DOCTYPE html>
{{-- Главный layout: общий каркас всех страниц сайта. --}}
<html lang="ru">
<head>
    {{-- Кодировка страницы. --}}
    <meta charset="utf-8">
    {{-- Адаптация под телефон и компьютер. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Заголовок вкладки браузера. --}}
    <title>{{ $title ?? 'ERP HR' }}</title>
    {{-- Подключаем CSS и JS через Vite. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{-- shell делит экран на боковое меню и основное содержимое. --}}
    <div class="shell">
        {{-- Левое меню сайта. --}}
        <aside class="sidebar">
            {{-- Логотип и название системы. --}}
            <div class="brand">
                <span class="brand-mark">
                    <img src="{{ asset('images/erp-logo.png') }}" alt="ERP Кадры">
                </span>
                <div>
                    <strong>ERP Кадры</strong>
                    <small>Документы сотрудников</small>
                </div>
            </div>

            {{-- Если пользователь вошел, показываем его данные и меню. --}}
            @auth
                {{-- Карточка текущего пользователя. --}}
                <div class="user-panel">
                    <span>{{ auth()->user()->name }}</span>
                    <strong>{{ auth()->user()->roleLabel() }}</strong>
                    <small>{{ auth()->user()->email }}</small>
                </div>

                {{-- Навигация по разделам. --}}
                <nav class="nav">
                    {{-- Журнал заявок доступен всем вошедшим пользователям. --}}
                    <a href="{{ route('requests.index') }}" class="{{ request()->routeIs('requests.*') ? 'active' : '' }}">Журнал заявок</a>
                    {{-- Кнопки создания заявок показываем только тем, кто имеет право создавать заявки. --}}
                    @if (auth()->user()->canCreateRequests())
                        <a href="{{ route('requests.create', ['type' => 'vacation']) }}">Отпуск</a>
                        <a href="{{ route('requests.create', ['type' => 'sick_leave']) }}">Больничный</a>
                    @endif
                    {{-- Админский раздел показываем только тем, кто может управлять пользователями. --}}
                    @if (auth()->user()->canManageUsers())
                        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.*') ? 'active' : '' }}">Пользователи и права</a>
                    @endif
                </nav>

                {{-- Форма выхода из системы. --}}
                <form method="POST" action="{{ route('logout') }}" class="logout-form">
                    {{-- CSRF-защита Laravel для POST-запроса. --}}
                    @csrf
                    <button class="ghost-button" type="submit">Выйти</button>
                </form>
            @else
                {{-- Если пользователь не вошел, показываем нейтральную карточку. --}}
                <div class="user-panel">
                    <span>Без входа</span>
                    <strong>Нужна авторизация</strong>
                </div>
            @endauth
        </aside>

        {{-- Основная область страницы. --}}
        <main class="content">
            {{-- Сообщение об успешном действии. --}}
            @if (session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif

            {{-- Ошибки валидации формы. --}}
            @if ($errors->any())
                <div class="alert error">
                    {{-- Выводим каждую ошибку отдельной строкой. --}}
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            {{-- Сюда вставляется содержимое конкретной страницы. --}}
            @yield('content')
        </main>
    </div>
</body>
</html>
