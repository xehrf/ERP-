@extends('layouts.app', ['title' => 'Журнал заявок'])

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Кадровые документы</p>
            <h1>Журнал заявок</h1>
        </div>
        <a class="primary-button" href="{{ route('requests.create') }}">Новая заявка</a>
    </header>

    <section class="metrics">
        <div><span>Всего</span><strong>{{ $stats['total'] }}</strong></div>
        <div><span>У кадровика</span><strong>{{ $stats['pending_hr'] }}</strong></div>
        <div><span>У директора</span><strong>{{ $stats['pending_director'] }}</strong></div>
        <div><span>Утверждено</span><strong>{{ $stats['approved'] }}</strong></div>
    </section>

    <form class="filters" method="GET">
        <select name="type">
            <option value="">Все приложения</option>
            <option value="vacation" @selected(request('type') === 'vacation')>Отпуск</option>
            <option value="sick_leave" @selected(request('type') === 'sick_leave')>Больничный</option>
        </select>
        <select name="status">
            <option value="">Все статусы</option>
            <option value="pending_hr" @selected(request('status') === 'pending_hr')>На проверке кадровика</option>
            <option value="pending_director" @selected(request('status') === 'pending_director')>На подписи директора</option>
            <option value="approved" @selected(request('status') === 'approved')>Утверждено</option>
            <option value="rejected" @selected(request('status') === 'rejected')>Отклонено</option>
        </select>
        <button class="secondary-button" type="submit">Фильтр</button>
    </form>

    <section class="table-wrap">
        <table>
            <thead>
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
                @forelse ($requests as $item)
                    <tr>
                        <td><span class="type-badge {{ $item->type }}">{{ $item->typeLabel() }}</span></td>
                        <td>{{ $item->user->name }}</td>
                        <td>{{ $item->start_date->format('d.m.Y') }} - {{ $item->end_date->format('d.m.Y') }}</td>
                        <td>{{ $item->calendar_days }}</td>
                        <td>{{ $item->working_days }}</td>
                        <td><span class="status {{ $item->status }}">{{ $item->statusLabel() }}</span></td>
                        <td class="actions">
                            @if (in_array($currentUser->role, ['hr', 'admin'], true) && $item->status === 'pending_hr')
                                <form method="POST" action="{{ route('requests.hr-approve', $item) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="small-button" type="submit">Кадры OK</button>
                                </form>
                            @endif
                            @if (in_array($currentUser->role, ['director', 'admin'], true) && $item->status === 'pending_director')
                                <form method="POST" action="{{ route('requests.director-approve', $item) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="small-button" type="submit">Утвердить</button>
                                </form>
                            @endif
                            @if (in_array($currentUser->role, ['hr', 'director', 'admin'], true) && ! in_array($item->status, ['approved', 'rejected'], true))
                                <form method="POST" action="{{ route('requests.reject', $item) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="danger-button" type="submit">Отклонить</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty">Заявок пока нет.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="pagination">{{ $requests->links() }}</div>
@endsection
