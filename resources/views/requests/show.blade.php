@extends('layouts.app', ['title' => 'Заявка #' . $requestItem->id])

{{-- Детальная страница одной заявки. --}}
@section('content')
    {{-- Заголовок страницы. --}}
    <header class="page-header">
        <div>
            <p class="eyebrow">{{ $requestItem->typeLabel() }}</p>
            <h1>Заявка #{{ $requestItem->id }}</h1>
        </div>
        <a class="secondary-button" href="{{ route('requests.index') }}">Назад</a>
    </header>

    {{-- Сетка: слева данные заявки, справа комментарии. --}}
    <section class="details-grid">
        {{-- Панель с основной информацией. --}}
        <div class="panel detail-panel">
            <h2>Данные заявки</h2>
            {{-- dl удобен для пар "название поля - значение". --}}
            <dl>
                <dt>Работник</dt>
                <dd>{{ $requestItem->user->name }}</dd>
                <dt>Период</dt>
                <dd>{{ $requestItem->start_date->format('d.m.Y') }} - {{ $requestItem->end_date->format('d.m.Y') }}</dd>
                <dt>Календарные дни</dt>
                <dd>{{ $requestItem->calendar_days }}</dd>
                <dt>Рабочие дни</dt>
                <dd>{{ $requestItem->working_days }}</dd>
                <dt>Статус</dt>
                <dd><span class="status {{ $requestItem->status }}">{{ $requestItem->statusLabel() }}</span></dd>
                <dt>Кадровик</dt>
                <dd>{{ $requestItem->hrApprover?->name ?? 'Еще не согласовано' }}</dd>
                <dt>Директор</dt>
                <dd>{{ $requestItem->directorApprover?->name ?? 'Еще не утверждено' }}</dd>
            </dl>

            {{-- Кнопки действий по заявке. --}}
            <div class="actions wide-actions">
                {{-- Согласование кадровиком. --}}
                @if ($currentUser->canApproveHr() && $requestItem->status === 'pending_hr')
                    <form method="POST" action="{{ route('requests.hr-approve', $requestItem) }}">
                        @csrf
                        @method('PATCH')
                        <button class="small-button" type="submit">Согласовать кадровиком</button>
                    </form>
                @endif
                {{-- Утверждение директором. --}}
                @if ($currentUser->canApproveDirector() && $requestItem->status === 'pending_director')
                    <form method="POST" action="{{ route('requests.director-approve', $requestItem) }}">
                        @csrf
                        @method('PATCH')
                        <button class="small-button" type="submit">Утвердить директором</button>
                    </form>
                @endif
                {{-- Отклонение заявки. --}}
                @if (($currentUser->canApproveHr() || $currentUser->canApproveDirector()) && ! in_array($requestItem->status, ['approved', 'rejected'], true))
                    <form method="POST" action="{{ route('requests.reject', $requestItem) }}">
                        @csrf
                        @method('PATCH')
                        <button class="danger-button" type="submit">Отклонить</button>
                    </form>
                @endif
                @if ($requestItem->status === 'rejected' && $currentUser->canCreateRequests() && $currentUser->id === $requestItem->user_id)
                    <a class="primary-button" href="{{ route('requests.repeat', $requestItem) }}">Повторить заявку</a>
                @endif
            </div>
        </div>

        {{-- Панель комментариев. --}}
        <div class="panel comments-panel">
            <h2>Комментарии</h2>

            {{-- Выводим основные комментарии. --}}
            @forelse ($requestItem->comments as $comment)
                <article class="comment">
                    {{-- Автор, роль и дата комментария. --}}
                    <div class="comment-head">
                        <strong>{{ $comment->user->name }}</strong>
                        <span>{{ $comment->user->roleLabel() }} · {{ $comment->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                    {{-- Текст комментария. --}}
                    <p>{{ $comment->body }}</p>

                    {{-- Выводим ответы на этот комментарий. --}}
                    @foreach ($comment->replies as $reply)
                        <article class="comment reply">
                            <div class="comment-head">
                                <strong>{{ $reply->user->name }}</strong>
                                <span>{{ $reply->user->roleLabel() }} · {{ $reply->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                            <p>{{ $reply->body }}</p>
                        </article>
                    @endforeach

                    {{-- Форма ответа показывается только пользователям с правом comment. --}}
                    @if ($currentUser->canComment())
                        <form class="reply-form" method="POST" action="{{ route('requests.comments.store', $requestItem) }}">
                            @csrf
                            {{-- parent_id показывает, на какой комментарий отвечаем. --}}
                            <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                            <textarea name="body" rows="2" placeholder="Ответить на комментарий" required></textarea>
                            <button class="secondary-button" type="submit">Ответить</button>
                        </form>
                    @endif
                </article>
            @empty
                {{-- Если комментариев нет. --}}
                <p class="empty">Комментариев пока нет.</p>
            @endforelse

            {{-- Форма нового основного комментария. --}}
            @if ($currentUser->canComment())
                <form class="comment-form" method="POST" action="{{ route('requests.comments.store', $requestItem) }}">
                    @csrf
                    <label>
                        Новый комментарий
                        <textarea name="body" rows="4" required></textarea>
                    </label>
                    <button class="primary-button" type="submit">Добавить комментарий</button>
                </form>
            @endif
        </div>

        {{-- История показывает весь путь заявки. --}}
        <div class="panel history-panel">
            <h2>История заявки</h2>
            <div class="timeline">
                @forelse ($requestItem->histories as $history)
                    <article class="timeline-item">
                        <span class="timeline-dot"></span>
                        <div>
                            <strong>{{ $history->title }}</strong>
                            <p>{{ $history->body }}</p>
                            <small>
                                {{ $history->user?->name ?? 'Система' }}
                                · {{ $history->created_at->format('d.m.Y H:i') }}
                            </small>
                        </div>
                    </article>
                @empty
                    <p class="empty">История пока не записана.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
