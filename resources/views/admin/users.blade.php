@extends('layouts.app', ['title' => 'Пользователи и права'])

{{-- Страница администрирования пользователей. --}}
@section('content')
    {{-- Заголовок страницы. --}}
    <header class="page-header">
        <div>
            <p class="eyebrow">Администрирование</p>
            <h1>Пользователи и права</h1>
        </div>
    </header>

    {{-- Панель создания нового пользователя. --}}
    <section class="panel user-create-panel">
        <h2>Создать пользователя</h2>
        {{-- Форма отправляется в AdminUserController@store. --}}
        <form
            method="POST"
            action="{{ route('admin.users.store') }}"
            class="user-create-form"
            data-role-permissions-form
            data-role-permissions='@json(\App\Models\User::defaultPermissionsByRole())'
        >
            @csrf
            {{-- Имя нового пользователя. --}}
            <label>
                Имя
                <input type="text" name="name" value="{{ old('name') }}" required>
            </label>
            {{-- Email для входа. --}}
            <label>
                Email
                <input type="email" name="email" value="{{ old('email') }}" required>
            </label>
            {{-- Пароль нового пользователя. --}}
            <label>
                Пароль
                <input type="password" name="password" required minlength="6">
            </label>
            {{-- Роль нового пользователя. --}}
            <label>
                Роль
                <select name="role" required>
                    {{-- Перебираем все роли из User::availableRoles(). --}}
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            {{-- Чекбоксы разрешений. --}}
            <div class="permission-grid permission-grid-wide">
                @foreach ($permissions as $value => $label)
                    <label class="checkbox-line">
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $value }}"
                            @checked(in_array($value, old('permissions', []), true))
                        >
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            {{-- Кнопка создания пользователя. --}}
            <button class="primary-button" type="submit">Создать</button>
        </form>
    </section>

    {{-- Таблица существующих пользователей. --}}
    {{-- Поиск сотрудников в таблице пользователей. --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="filters admin-user-search">
        {{-- Поле ищет по имени, email и роли. --}}
        <label>
            Поиск сотрудника
            <input
                type="search"
                name="q"
                value="{{ $search ?? '' }}"
                placeholder="Имя, email или роль"
            >
        </label>

        {{-- Кнопка запускает поиск. --}}
        <button class="primary-button" type="submit">Найти</button>

        {{-- Сброс очищает поиск и снова показывает всех пользователей. --}}
        @if (! empty($search))
            <a href="{{ route('admin.users.index') }}" class="ghost-button search-reset">Сбросить</a>
        @endif
    </form>

    <section class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Пользователь</th>
                    <th>Email</th>
                    <th>Доступ</th>
                    <th>Роль</th>
                    <th>Разрешения</th>
                    <th>Сохранить</th>
                </tr>
            </thead>
            <tbody>
                {{-- Проходим по каждому пользователю. --}}
                @forelse ($users as $user)
                    <tr>
                        {{-- Имя пользователя. --}}
                        <td>{{ $user->name }}</td>
                        {{-- Email пользователя. --}}
                        <td>{{ $user->email }}</td>
                        {{-- Статус одобрения пользователя. --}}
                        <td>
                            @if ($user->is_approved)
                                <span class="status approved">Одобрен</span>
                            @elseif ($user->is_rejected)
                                <span class="status rejected">Отклонен</span>
                            @else
                                <div class="actions">
                                    <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="small-button" type="submit">Одобрить</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.reject', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="danger-button" type="submit">Отклонить</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                        {{-- Форма обновления роли начинается здесь. --}}
                        <td>
                            <form
                                id="user-form-{{ $user->id }}"
                                method="POST"
                                action="{{ route('admin.users.update', $user) }}"
                                data-role-permissions-form
                                data-role-permissions='@json(\App\Models\User::defaultPermissionsByRole())'
                            >
                                @csrf
                                @method('PATCH')
                                {{-- Выбор роли. --}}
                                <select name="role">
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        {{-- Чекбоксы разрешений пользователя. --}}
                        <td>
                            <div class="permission-grid">
                                @foreach ($permissions as $value => $label)
                                    <label class="checkbox-line">
                                        <input
                                            form="user-form-{{ $user->id }}"
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $value }}"
                                            @checked(in_array($value, $user->permissions ?? [], true))
                                        >
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </td>
                        {{-- Кнопка сохраняет форму конкретного пользователя. --}}
                        <td>
                            <button form="user-form-{{ $user->id }}" class="small-button" type="submit">Сохранить</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-table">
                            Сотрудники не найдены. Попробуйте изменить текст поиска.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
