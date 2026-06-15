@extends('layouts.app', ['title' => 'Новая заявка'])

{{-- Страница создания отпуска или больничного. --}}
@section('content')
    {{-- Заголовок страницы. --}}
    <header class="page-header">
        <div>
            <p class="eyebrow">Новое приложение</p>
            <h1>Подать заявку</h1>
        </div>
    </header>

    {{-- Форма создания заявки. data-day-calculator нужен JavaScript для подсчета дней. --}}
    <form
        class="panel request-form"
        method="POST"
        action="{{ route('requests.store') }}"
        data-day-calculator
        data-holidays='@json($holidays)'
    >
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

        {{-- Баланс ежегодного отпуска: норма 28 дней, первый отпуск не короче 14 дней. --}}
        <div class="vacation-balance" data-vacation-balance data-balance='@json($vacationBalance)' @if ($type !== 'vacation') hidden @endif>
            <div class="section-title">
                <span>Баланс отпуска за {{ $vacationBalance['year'] }} год</span>
                <strong>{{ $vacationBalance['remaining'] }} / {{ $vacationBalance['limit'] }}</strong>
            </div>
            <div class="balance-bar">
                <div class="balance-bar-fill" style="width: {{ min(100, (int) round($vacationBalance['used'] / $vacationBalance['limit'] * 100)) }}%"></div>
            </div>
            <p class="balance-note">
                Использовано {{ $vacationBalance['used'] }} из {{ $vacationBalance['limit'] }} календарных дней.
                @if ($vacationBalance['is_first'])
                    Первый отпуск в этом году должен быть не менее {{ $vacationBalance['min_first_days'] }} дней подряд.
                @else
                    Остаток можно делить на любое количество частей.
                @endif
            </p>
            {{-- Сюда JavaScript подставляет предупреждение, если выбранные дни нарушают правило. --}}
            <p class="balance-warning" data-balance-warning hidden></p>
        </div>

        {{-- Предупреждение появляется, если выбранные даты пересекаются с уже занятыми днями. --}}
        <div class="overlap-warning" data-overlap-warning hidden>
            Выбранные даты пересекаются с уже существующим отпуском или больничным.
        </div>

        {{-- Большой календарь: дни месяца, праздники, занятые периоды и выбранный диапазон. --}}
        <section class="vacation-calendar" data-vacation-calendar data-holidays='@json($holidays)' data-busy='@json($busyRequests)'>
            <div class="vc-head">
                <div>
                    <span class="vc-head-label">Календарь</span>
                    <strong class="vc-month-label" data-vc-month-label>&nbsp;</strong>
                </div>
                <div class="vc-nav">
                    <button type="button" class="vc-nav-btn" data-vc-prev aria-label="Предыдущий месяц">&#8249;</button>
                    <button type="button" class="vc-nav-btn vc-nav-today" data-vc-today>Сегодня</button>
                    <button type="button" class="vc-nav-btn" data-vc-next aria-label="Следующий месяц">&#8250;</button>
                </div>
            </div>
            <div class="vc-weekdays">
                <span>Пн</span><span>Вт</span><span>Ср</span><span>Чт</span><span>Пт</span><span>Сб</span><span>Вс</span>
            </div>
            {{-- Сюда JavaScript вставляет ячейки дней текущего месяца. --}}
            <div class="vc-grid" data-vc-grid></div>
            <div class="vc-legend">
                <span class="vc-legend-item"><i class="vc-swatch is-today"></i>Сегодня</span>
                <span class="vc-legend-item"><i class="vc-swatch is-weekend"></i>Выходной</span>
                <span class="vc-legend-item"><i class="vc-swatch is-holiday"></i>Праздник</span>
                <span class="vc-legend-item"><i class="vc-swatch is-selected"></i>Выбранный период</span>
                <span class="vc-legend-item"><i class="vc-swatch is-busy-pending"></i>На проверке</span>
                <span class="vc-legend-item"><i class="vc-swatch is-busy-approved"></i>Утверждено</span>
            </div>
        </section>

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
