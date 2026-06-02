@extends('layouts.app', ['title' => 'Вход в ERP'])

@section('content')
    <section class="login-screen">
        <div class="login-copy">
            <p class="eyebrow">Демо-доступ</p>
            <h1>ERP по кадровым документам</h1>
            <p>Выберите роль, чтобы проверить работу заявок на отпуск и больничный.</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="panel login-panel">
            @csrf
            <label for="user_id">Пользователь</label>
            <select id="user_id" name="user_id" required>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->roleLabel() }}</option>
                @endforeach
            </select>
            <button class="primary-button" type="submit">Войти</button>
        </form>
    </section>
@endsection
