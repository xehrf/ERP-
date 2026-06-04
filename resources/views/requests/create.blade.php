@extends('layouts.app', ['title' => 'Новая заявка'])

{{-- Страница создания отпуска или больничного. --}}
@section('content')
    {{-- Заголовок страницы. --}}
    <header class="page-header">
        <div>
            <p class="eyebrow">Новое приложение</p>
            <h1>Заявка на отпуск или больничный</h1>
        </div>
    </header>

    {{-- Форма создания заявки. data-day-calculator нужен JavaScript для подсчета дней. --}}
    <form class="panel request-form" method="POST" action="{{ route('requests.store') }}" data-day-calculator>
        {{-- CSRF-защита Laravel. --}}
        @csrf
        {{-- Сетка основных полей формы. --}}
        <div class="form-grid">
            {{-- Тип документа: отпуск или больничный. --}}
            <label>
                Тип документа
                <select name="type" required>
                    <option value="vacation" @selected(old('type', $type) === 'vacation')>Отпуск</option>
                    <option value="sick_leave" @selected(old('type', $type) === 'sick_leave')>Больничный</option>
                </select>
            </label>
            {{-- Дата начала периода. --}}
            <label>
                Дата начала
                <input type="date" name="start_date" value="{{ old('start_date', $prefill['start_date'] ?? null) }}" required>
            </label>
            {{-- Дата конца периода. --}}
            <label>
                Дата конца
                <input type="date" name="end_date" value="{{ old('end_date', $prefill['end_date'] ?? null) }}" required>
            </label>
        </div>

        {{-- Блок предварительного подсчета дней на JavaScript. --}}
        <div class="day-preview">
            {{-- Сюда JS вставляет количество календарных дней. --}}
            <div><span>Календарных дней</span><strong data-calendar-days>0</strong></div>
            {{-- Сюда JS вставляет количество рабочих дней. --}}
            <div><span>Рабочих дней</span><strong data-working-days>0</strong></div>
        </div>

        {{-- Предупреждение появляется, если выбранные даты пересекаются с уже занятыми днями. --}}
        <div class="overlap-warning" data-overlap-warning hidden>
            Выбранные даты пересекаются с уже существующим отпуском или больничным.
        </div>

        {{-- Календарь занятости сотрудника. --}}
        <section class="busy-calendar" data-busy-calendar='@json($busyRequests)'>
            <div class="section-title">
                <span>Календарь занятости</span>
                <strong>{{ count($busyRequests) }}</strong>
            </div>
            @forelse ($busyRequests as $busy)
                <div class="busy-item">
                    <span class="type-badge {{ $busy['type'] }}">{{ $busy['label'] }}</span>
                    <strong>{{ \Carbon\Carbon::parse($busy['start_date'])->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($busy['end_date'])->format('d.m.Y') }}</strong>
                    <small>{{ $busy['status'] }}</small>
                </div>
            @empty
                <p class="empty">Занятых периодов пока нет.</p>
            @endforelse
        </section>

        {{-- Комментарий к заявке. Он также сохраняется как первый комментарий в переписке. --}}
        <label>
            Комментарий
            <textarea name="comment" rows="4" placeholder="Например: ежегодный отпуск, лист нетрудоспособности">{{ old('comment', $prefill['comment'] ?? null) }}</textarea>
        </label>

        {{-- Кнопки формы. --}}
        <div class="form-actions">
            {{-- Возврат в журнал без сохранения. --}}
            <a class="secondary-button" href="{{ route('requests.index') }}">Назад</a>
            {{-- Отправка формы. --}}
            <button class="primary-button" type="submit">Отправить</button>
        </div>
    </form>
@endsection
