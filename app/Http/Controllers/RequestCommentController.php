<?php

// Пространство имен контроллеров.
namespace App\Http\Controllers;

// Модель заявки.
use App\Models\DocumentRequest;
// Модель комментария.
use App\Models\RequestComment;
// Ответ-перенаправление.
use Illuminate\Http\RedirectResponse;
// Данные запроса.
use Illuminate\Http\Request;

// Контроллер отвечает за комментарии к заявкам.
class RequestCommentController extends Controller
{
    // Сохраняет новый комментарий или ответ на комментарий.
    public function store(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        // Пользователь должен иметь право видеть эту заявку.
        abort_unless($request->user()->canViewRequest($documentRequest), 403);
        // Пользователь должен иметь право комментировать.
        abort_unless($request->user()->canComment(), 403);

        // Проверяем данные формы комментария.
        $data = $request->validate([
            // Текст обязателен, максимум 2000 символов.
            'body' => ['required', 'string', 'max:2000'],
            // parent_id нужен только для ответа на существующий комментарий.
            'parent_id' => ['nullable', 'exists:request_comments,id'],
        ]);

        // Если parent_id есть, значит пользователь отвечает на комментарий.
        if (! empty($data['parent_id'])) {
            // Ищем родительский комментарий только внутри этой же заявки.
            $parent = RequestComment::where('document_request_id', $documentRequest->id)
                // Отвечать разрешаем только на верхний комментарий, а не на ответ второго уровня.
                ->whereNull('parent_id')
                // Если комментарий не найден, Laravel вернет ошибку 404.
                ->findOrFail($data['parent_id']);
        }

        // Создаем комментарий через связь заявки.
        $documentRequest->comments()->create([
            // Автор комментария.
            'user_id' => $request->user()->id,
            // Если это ответ, сохраняем ID родительского комментария.
            'parent_id' => $parent->id ?? null,
            // Текст комментария.
            'body' => $data['body'],
        ]);

        // Возвращаемся назад к заявке.
        return back()->with('success', 'Комментарий добавлен.');
    }
}
