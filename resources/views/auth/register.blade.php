@extends('layouts.auth', ['title' => 'Регистрация в ERP Кадры'])

@section('content')
    <main class="auth-split">
        <section class="auth-visual">
            <div class="mesh-bg" aria-hidden="true">
                <span></span><span></span><span></span><span></span><span></span>
            </div>

            <div class="auth-hero">
                <div class="auth-mark">
                    <img src="{{ asset('images/erp-logo.png') }}" alt="ERP Кадры">
                </div>
                <h1>Регистрация в ERP Кадры</h1>
                <p>
                    Создайте аккаунт кандидата. Администратор проверит заявку,
                    одобрит доступ и отдельно назначит роль с нужными правами.
                </p>

                <div class="auth-benefits">
                    <div>
                        <strong>01</strong>
                        <span>Кандидат ждет решения администратора</span>
                    </div>
                    <div>
                        <strong>02</strong>
                        <span>После одобрения можно войти в систему</span>
                    </div>
                    <div>
                        <strong>03</strong>
                        <span>Права выдаются отдельно и безопасно</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="auth-panel">
            <div class="auth-brand">
                <div class="auth-brand-icon">
                    <img src="{{ asset('images/erp-logo.png') }}" alt="ERP Кадры">
                </div>
                <strong>ERP <span>Кадры</span></strong>
            </div>

            <div class="auth-heading">
                <h2>Создать аккаунт</h2>
                <p>Заполните данные, чтобы отправить заявку на доступ к системе.</p>
            </div>

            <form method="POST" action="{{ route('register.store') }}" class="auth-form">
                @csrf

                <label>
                    Имя
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Ваше имя" required autofocus>
                </label>

                <label>
                Адрес электронной почты
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required>
                </label>

                <label>
                    Пароль
                    <input type="password" name="password" placeholder="Минимум 6 символов" required minlength="6">
                </label>

                <label>
                    Повторите пароль
                    <input type="password" name="password_confirmation" placeholder="Повторите пароль" required minlength="6">
                </label>

                <button class="auth-submit" type="submit">Зарегистрироваться</button>
            </form>

            <p class="auth-switch">
                Уже есть аккаунт?
                <a href="{{ route('login') }}">Войти.</a>
            </p>
        </section>
    </main>
@endsection
