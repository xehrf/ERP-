@extends('layouts.app', ['title' => 'Новая заявка'])

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Новое приложение</p>
            <h1>Заявка на отпуск или больничный</h1>
        </div>
    </header>

    <form class="panel request-form" method="POST" action="{{ route('requests.store') }}" data-day-calculator>
        @csrf
        <div class="form-grid">
            <label>
                Тип документа
                <select name="type" required>
                    <option value="vacation" @selected(old('type', $type) === 'vacation')>Отпуск</option>
                    <option value="sick_leave" @selected(old('type', $type) === 'sick_leave')>Больничный</option>
                </select>
            </label>
            <label>
                Дата начала
                <input type="date" name="start_date" value="{{ old('start_date') }}" required>
            </label>
            <label>
                Дата конца
                <input type="date" name="end_date" value="{{ old('end_date') }}" required>
            </label>
        </div>

        <div class="day-preview">
            <div><span>Календарных дней</span><strong data-calendar-days>0</strong></div>
            <div><span>Рабочих дней</span><strong data-working-days>0</strong></div>
        </div>

        <label>
            Комментарий
            <textarea name="comment" rows="4" placeholder="Например: ежегодный отпуск, лист нетрудоспособности">{{ old('comment') }}</textarea>
        </label>

        <div class="form-actions">
            <a class="secondary-button" href="{{ route('requests.index') }}">Назад</a>
            <button class="primary-button" type="submit">Отправить</button>
        </div>
    </form>
@endsection
