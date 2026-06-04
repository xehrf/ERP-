@extends('layouts.auth', ['title' => 'Вход в ERP Кадры'])

@section('content')
    <main class="auth-split">
        <section class="auth-visual">
            <div class="mesh-bg" aria-hidden="true">
                <span></span><span></span><span></span><span></span><span></span>
            </div>

            <div class="auth-hero">
                <div class="auth-mark">HR</div>
                <h1>Администратор ERP Кадры</h1>
                <p>
                    Единая система для заявок на отпуск, больничных,
                    согласования документов и контроля статусов сотрудников.
                </p>

                <div class="auth-benefits">
                    <div>
                        <strong>01</strong>
                        <span>Отпуска и больничные в одном журнале</span>
                    </div>
                    <div>
                        <strong>02</strong>
                        <span>Согласование кадровиком и директором</span>
                    </div>
                    <div>
                        <strong>03</strong>
                        <span>Комментарии, уведомления и история заявки</span>
                    </div>
                    <div>
                        <strong>04</strong>
                        <span>Админ управляет ролями и правами</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="auth-panel">
            <div class="auth-brand">
                <div class="auth-brand-icon">HR</div>
                <strong>ERP <span>Кадры</span></strong>
            </div>

            <div class="auth-heading">
                <h2>Добро пожаловать</h2>
                <p>Войдите в свою учетную запись, чтобы продолжить работу с кадровыми документами.</p>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="auth-form">
                @csrf

                <label>
                Адрес электронной почты
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
                </label>

                <label>
                    Пароль
                    <input type="password" name="password" placeholder="Введите пароль" required>
                </label>

                <label class="checkbox-line auth-remember">
                    <input type="checkbox" name="remember" value="1">
                    Запомнить меня
                </label>

                <button class="auth-submit" type="submit">Войти</button>
            </form>

            <p class="auth-switch">
                У вас нет аккаунта?
                <a href="{{ route('register') }}">Создайте его.</a>
            </p>
        </section>
    </main>
@endsection
