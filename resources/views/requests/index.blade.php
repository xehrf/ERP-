@extends('layouts.app', ['title' => 'Журнал заявок'])

{{-- Страница со списком заявок. --}}
@section('content')
    {{-- Верхняя часть страницы: заголовок и кнопка создания. --}}
    <header class="page-header">
        <div>
            <p class="eyebrow">Кадровые документы</p>
            <h1>Журнал заявок</h1>
        </div>
        {{-- Кнопка создания показывается только тем, кто имеет право создавать заявки. --}}
        @if ($currentUser->canCreateRequests())
            <a class="primary-button" href="{{ route('requests.create') }}">Новая заявка</a>
        @endif
    </header>

    {{-- Карточки статистики. --}}
    <section class="metrics">
        <div><span>Всего</span><strong>{{ $stats['total'] }}</strong></div>
        <div><span>У кадровика</span><strong>{{ $stats['pending_hr'] }}</strong></div>
        <div><span>У директора</span><strong>{{ $stats['pending_director'] }}</strong></div>
        <div><span>Утверждено</span><strong>{{ $stats['approved'] }}</strong></div>
    </section>

    {{-- Последние уведомления пользователя. --}}
    <section class="notifications-strip">
        <div class="section-title">
            <span>Уведомления</span>
            <strong>{{ $notifications->count() }}</strong>
        </div>
        <div class="notification-list">
            @forelse ($notifications as $notification)
                <a class="notification-card" href="{{ $notification->document_request_id ? route('requests.show', $notification->document_request_id) : '#' }}">
                    <strong>{{ $notification->title }}</strong>
                    <span>{{ $notification->body }}</span>
                    <small>{{ $notification->created_at->format('d.m.Y H:i') }}</small>
                </a>
            @empty
                <p class="empty">Новых уведомлений пока нет.</p>
            @endforelse
        </div>
    </section>

    {{-- Фильтры списка заявок. --}}
    <form class="filters" method="GET">
        {{-- Фильтр по типу документа. --}}
        <select name="type">
            <option value="">Все приложения</option>
            <option value="vacation" @selected(request('type') === 'vacation')>Отпуск</option>
            <option value="sick_leave" @selected(request('type') === 'sick_leave')>Больничный</option>
        </select>
        {{-- Фильтр по статусу заявки. --}}
        <select name="status">
            <option value="">Все статусы</option>
            <option value="pending_hr" @selected(request('status') === 'pending_hr')>На проверке кадровика</option>
            <option value="pending_director" @selected(request('status') === 'pending_director')>На подписи директора</option>
            <option value="approved" @selected(request('status') === 'approved')>Утверждено</option>
            <option value="rejected" @selected(request('status') === 'rejected')>Отклонено</option>
        </select>
        {{-- Кнопка применяет фильтры через GET-запрос. --}}
        <button class="secondary-button" type="submit">Фильтр</button>
    </form>

    {{-- Таблица заявок. --}}
    <section class="table-wrap">
        <table>
            <thead>
                {{-- Заголовки колонок. --}}
                <tr>
                    <th>Приложение</th>
                    <th>Работник</th>
                    <th>Период</th>
                    <th>Календ.</th>
                    <th>Рабоч.</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                {{-- Проходим по всем заявкам текущей страницы. --}}
                @forelse ($requests as $item)
                    <tr>
                        {{-- Тип заявки с цветным бейджем. --}}
                        <td><span class="type-badge {{ $item->type }}">{{ $item->typeLabel() }}</span></td>
                        {{-- Имя работника, который создал заявку. --}}
                        <td>{{ $item->user->name }}</td>
                        {{-- Период заявки. --}}
                        <td>{{ $item->start_date->format('d.m.Y') }} - {{ $item->end_date->format('d.m.Y') }}</td>
                        {{-- Календарные дни. --}}
                        <td>{{ $item->calendar_days }}</td>
                        {{-- Рабочие дни. --}}
                        <td>{{ $item->working_days }}</td>
                        {{-- Статус заявки. --}}
                        <td><span class="status {{ $item->status }}">{{ $item->statusLabel() }}</span></td>
                        {{-- Действия по заявке. --}}
                        <td class="actions">
                            {{-- Открывает детальную страницу заявки. --}}
                            <a class="small-link" href="{{ route('requests.show', $item) }}">Открыть</a>
                            {{-- Кнопка кадровика показывается только при нужном праве и статусе. --}}
                            @if ($currentUser->canApproveHr() && $item->status === 'pending_hr')
                                <form method="POST" action="{{ route('requests.hr-approve', $item) }}">
                                    {{-- CSRF-защита. --}}
                                    @csrf
                                    {{-- HTML не поддерживает PATCH напрямую, поэтому Laravel использует скрытое поле. --}}
                                    @method('PATCH')
                                    <button class="small-button" type="submit">Кадры OK</button>
                                </form>
                            @endif
                            {{-- Кнопка директора показывается только при нужном праве и статусе. --}}
                            @if ($currentUser->canApproveDirector() && $item->status === 'pending_director')
                                <form method="POST" action="{{ route('requests.director-approve', $item) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="small-button" type="submit">Утвердить</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    {{-- Если заявок нет, показываем пустое состояние. --}}
                    <tr>
                        <td colspan="7" class="empty">Заявок пока нет.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    {{-- Ссылки пагинации Laravel. --}}
    <div class="pagination">{{ $requests->links() }}</div>
@endsection
