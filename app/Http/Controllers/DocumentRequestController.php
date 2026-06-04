<?php

// Пространство имен контроллеров.
namespace App\Http\Controllers;

// Модель кадровой заявки.
use App\Models\DocumentRequest;
// Модель уведомления: через нее создаем сообщения для кадровика, директора и сотрудника.
use App\Models\ErpNotification;
// Модель пользователя нужна для поиска людей с нужными правами.
use App\Models\User;
// CarbonPeriod нужен, чтобы пройти по каждому дню между двумя датами.
use Carbon\CarbonPeriod;
// RedirectResponse означает ответ-перенаправление.
use Illuminate\Http\RedirectResponse;
// Request хранит данные формы, фильтры из URL и текущего пользователя.
use Illuminate\Http\Request;
// View означает Blade-страницу.
use Illuminate\View\View;

// Контроллер отвечает за заявки на отпуск и больничный.
class DocumentRequestController extends Controller
{
    // Показывает журнал заявок.
    public function index(Request $request): View
    {
        // Берем текущего авторизованного пользователя.
        $user = $request->user();
        // Готовим запрос к заявкам и сразу подгружаем связанные данные.
        $query = DocumentRequest::with(['user', 'hrApprover', 'directorApprover'])->latest();

        // Если пользователь не имеет права видеть все заявки, показываем только его заявки.
        if (! $user->canViewAllRequests()) {
            $query->where('user_id', $user->id);
        }

        // Если в фильтре выбран тип, ограничиваем список этим типом.
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        // Если в фильтре выбран статус, ограничиваем список этим статусом.
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Возвращаем страницу журнала заявок.
        return view('requests.index', [
            // currentUser нужен Blade-странице для проверки прав и отображения имени.
            'currentUser' => $user,
            // paginate(12) делит список на страницы по 12 заявок.
            'requests' => $query->paginate(12)->withQueryString(),
            // stats показывает карточки "всего", "у кадровика", "у директора", "утверждено".
            'stats' => $this->statsFor($user),
            // Берем 5 последних уведомлений для блока "Уведомления" на главной странице.
            'notifications' => $user->notifications()->latest()->limit(5)->get(),
        ]);
    }

    // Показывает одну конкретную заявку.
    public function show(Request $request, DocumentRequest $documentRequest): View
    {
        // Проверяем, имеет ли текущий пользователь право смотреть эту заявку.
        abort_unless($request->user()->canViewRequest($documentRequest), 403);

        // Возвращаем страницу заявки с комментариями и согласованиями.
        return view('requests.show', [
            // Передаем текущего пользователя.
            'currentUser' => $request->user(),
            // Загружаем заявку вместе с работником, согласующими и комментариями.
            'requestItem' => $documentRequest->load([
                'user',
                'hrApprover',
                'directorApprover',
                'comments.user',
                'comments.replies.user',
                'histories.user',
            ]),
        ]);
    }

    // Показывает форму создания заявки.
    public function create(Request $request): View
    {
        // Создавать заявки может только пользователь с нужным разрешением.
        abort_unless($request->user()->canCreateRequests(), 403);

        // Возвращаем страницу формы.
        return view('requests.create', [
            // Передаем текущего пользователя.
            'currentUser' => $request->user(),
            // Если тип в URL не передан, по умолчанию выбираем отпуск.
            'type' => $request->query('type', 'vacation'),
            // Занятые даты нужны форме, чтобы заранее предупредить о пересечении отпуска и больничного.
            'busyRequests' => $this->busyRequestsFor($request->user()),
            // prefill заполняет форму, когда сотрудник нажимает "Повторить заявку".
            'prefill' => [
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
                'comment' => $request->query('comment'),
            ],
        ]);
    }

    // Сохраняет новую заявку.
    public function store(Request $request): RedirectResponse
    {
        // Проверяем право создавать заявки.
        abort_unless($request->user()->canCreateRequests(), 403);

        // Валидируем данные формы.
        $data = $request->validate([
            // Тип обязателен: отпуск или больничный.
            'type' => ['required', 'in:vacation,sick_leave'],
            // Дата начала обязательна.
            'start_date' => ['required', 'date'],
            // Дата конца обязательна и не может быть раньше начала.
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            // Комментарий необязателен, максимум 1000 символов.
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // Проверяем, нет ли у этого сотрудника другой активной заявки на эти же дни.
        $hasOverlappingRequest = DocumentRequest::where('user_id', $request->user()->id)
            // Отклоненные заявки не блокируют новый отпуск или больничный.
            ->where('status', '!=', 'rejected')
            // Пересечение дат есть, если старая дата начала <= новой даты конца
            // и старая дата конца >= новой даты начала.
            ->whereDate('start_date', '<=', $data['end_date'])
            ->whereDate('end_date', '>=', $data['start_date'])
            ->exists();

        // Если пересечение найдено, возвращаем пользователя назад с понятной ошибкой.
        if ($hasOverlappingRequest) {
            return back()
                ->withErrors(['start_date' => 'На выбранные даты у сотрудника уже есть отпуск или больничный.'])
                ->withInput();
        }

        // Считаем количество календарных и рабочих дней.
        [$calendarDays, $workingDays] = $this->calculateDays($data['start_date'], $data['end_date']);

        // Создаем заявку в базе данных.
        $documentRequest = DocumentRequest::create([
            // ID текущего пользователя становится автором заявки.
            'user_id' => $request->user()->id,
            // Тип заявки.
            'type' => $data['type'],
            // Дата начала.
            'start_date' => $data['start_date'],
            // Дата конца.
            'end_date' => $data['end_date'],
            // Календарные дни.
            'calendar_days' => $calendarDays,
            // Рабочие дни.
            'working_days' => $workingDays,
            // Новая заявка сначала идет кадровику.
            'status' => 'pending_hr',
            // Старое поле комментария оставлено для совместимости.
            'comment' => $data['comment'] ?? null,
        ]);

        // Если пользователь написал комментарий при создании заявки.
        if (! empty($data['comment'])) {
            // Создаем этот текст как первый комментарий в переписке.
            $documentRequest->comments()->create([
                // Автор комментария - текущий пользователь.
                'user_id' => $request->user()->id,
                // Текст комментария.
                'body' => $data['comment'],
            ]);
        }

        // Фиксируем в истории, что заявка была создана.
        $this->addHistory($documentRequest, $request->user(), 'created', 'Заявка создана', 'Документ отправлен на проверку кадровику.');
        // Уведомляем всех, у кого есть право approve_hr, что появилась новая заявка.
        $this->notifyUsersWithPermission('approve_hr', $documentRequest, 'Новая заявка ожидает кадровика', $documentRequest->user->name.' отправил(а) '.$documentRequest->typeLabel().'.');

        // После создания открываем страницу этой заявки.
        return redirect()->route('requests.show', $documentRequest)->with('success', 'Заявка создана и отправлена кадровику.');
    }

    // Открывает форму создания с данными из отклоненной заявки.
    public function repeat(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        // Повторять можно только ту заявку, которую пользователь имеет право видеть.
        abort_unless($request->user()->canViewRequest($documentRequest), 403);
        // Повтор доступен только для отклоненных заявок.
        abort_unless($documentRequest->status === 'rejected', 403);
        // Даже при повторе пользователь должен иметь право создавать заявки.
        abort_unless($request->user()->canCreateRequests(), 403);

        // Передаем старые данные через URL, а create() подставит их в форму.
        return redirect()->route('requests.create', [
            // Тип заявки: отпуск или больничный.
            'type' => $documentRequest->type,
            // Дата начала в формате, который понимает HTML-поле date.
            'start_date' => $documentRequest->start_date->format('Y-m-d'),
            // Дата конца в формате, который понимает HTML-поле date.
            'end_date' => $documentRequest->end_date->format('Y-m-d'),
            // Комментарий показывает, от какой заявки был сделан повтор.
            'comment' => 'Повтор заявки #'.$documentRequest->id,
        ]);
    }

    // Согласование заявки кадровиком.
    public function approveHr(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        // Проверяем право согласовывать как кадровик.
        abort_unless($request->user()->canApproveHr(), 403);

        // Обновляем заявку.
        $documentRequest->update([
            // После кадровика заявка идет директору.
            'status' => 'pending_director',
            // Запоминаем, кто согласовал.
            'hr_approved_by' => $request->user()->id,
            // Запоминаем время согласования.
            'hr_approved_at' => now(),
        ]);

        // Добавляем событие в историю заявки.
        $this->addHistory($documentRequest, $request->user(), 'hr_approved', 'Согласовано кадровиком', 'Заявка передана директору.');
        // После кадровика уведомляем всех директоров.
        $this->notifyUsersWithPermission('approve_director', $documentRequest, 'Заявка ожидает директора', $documentRequest->user->name.' ожидает утверждения директора.');

        // Возвращаемся на предыдущую страницу.
        return back()->with('success', 'Кадровая проверка выполнена.');
    }

    // Утверждение заявки директором.
    public function approveDirector(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        // Проверяем право утверждать как директор.
        abort_unless($request->user()->canApproveDirector(), 403);

        // Обновляем заявку.
        $documentRequest->update([
            // Финальный статус.
            'status' => 'approved',
            // Запоминаем директора.
            'director_approved_by' => $request->user()->id,
            // Запоминаем время утверждения.
            'director_approved_at' => now(),
        ]);

        // Добавляем финальное событие в историю.
        $this->addHistory($documentRequest, $request->user(), 'director_approved', 'Утверждено директором', 'Заявка полностью утверждена.');
        // Сотрудник получает уведомление, что его заявку утвердили.
        $this->notifyUser($documentRequest->user, $documentRequest, 'Заявка утверждена', $documentRequest->typeLabel().' за период '.$documentRequest->start_date->format('d.m.Y').' - '.$documentRequest->end_date->format('d.m.Y').' утвержден(а).');

        // Возвращаемся на предыдущую страницу.
        return back()->with('success', 'Заявка утверждена директором.');
    }

    // Отклонение заявки.
    public function reject(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        // Отклонять могут те, кто имеет право кадровика или директора.
        abort_unless(
            $request->user()->canApproveHr() || $request->user()->canApproveDirector(),
            403
        );

        // Меняем статус заявки на "отклонено".
        $documentRequest->update(['status' => 'rejected']);

        // Сохраняем в истории, кто отклонил заявку.
        $this->addHistory($documentRequest, $request->user(), 'rejected', 'Заявка отклонена', 'Документ можно повторить после исправления.');
        // Сотрудник увидит уведомление и сможет быстро повторить заявку.
        $this->notifyUser($documentRequest->user, $documentRequest, 'Заявка отклонена', 'Вы можете открыть заявку и нажать "Повторить заявку".');

        // Возвращаемся назад.
        return back()->with('success', 'Заявка отклонена.');
    }

    // Считает календарные и рабочие дни между двумя датами.
    private function calculateDays(string $startDate, string $endDate): array
    {
        // Создаем период дат от startDate до endDate включительно.
        $period = CarbonPeriod::create($startDate, $endDate);
        // Счетчик всех дней.
        $calendarDays = 0;
        // Счетчик дней без субботы и воскресенья.
        $workingDays = 0;

        // Проходим по каждому дню периода.
        foreach ($period as $date) {
            // Каждый день считается календарным.
            $calendarDays++;

            // Если день не выходной, считаем его рабочим.
            if (! $date->isWeekend()) {
                $workingDays++;
            }
        }

        // Возвращаем два значения массивом.
        return [$calendarDays, $workingDays];
    }

    // Считает статистику для карточек сверху в журнале.
    private function statsFor($user): array
    {
        // Создаем базовый запрос к заявкам.
        $query = DocumentRequest::query();

        // Если пользователь не видит все заявки, считаем только его заявки.
        if (! $user->canViewAllRequests()) {
            $query->where('user_id', $user->id);
        }

        // Возвращаем готовые числа для интерфейса.
        return [
            // Всего заявок.
            'total' => (clone $query)->count(),
            // Заявки, которые ждут кадровика.
            'pending_hr' => (clone $query)->where('status', 'pending_hr')->count(),
            // Заявки, которые ждут директора.
            'pending_director' => (clone $query)->where('status', 'pending_director')->count(),
            // Утвержденные заявки.
            'approved' => (clone $query)->where('status', 'approved')->count(),
        ];
    }

    // Готовит массив занятых дат для календаря и предупреждения на форме.
    private function busyRequestsFor(User $user): array
    {
        // Ищем все неотклоненные заявки этого сотрудника.
        return DocumentRequest::where('user_id', $user->id)
            // Отклоненные заявки не занимают даты, потому что они уже не действуют.
            ->where('status', '!=', 'rejected')
            // Сортируем по дате начала, чтобы список был понятнее.
            ->orderBy('start_date')
            // Берем только нужные поля, без лишних данных.
            ->get(['type', 'start_date', 'end_date', 'status'])
            // Превращаем модели Laravel в простой массив для JavaScript.
            ->map(fn (DocumentRequest $request) => [
                // Технический тип заявки.
                'type' => $request->type,
                // Русское название типа.
                'label' => $request->typeLabel(),
                // Дата начала в формате Y-m-d.
                'start_date' => $request->start_date->format('Y-m-d'),
                // Дата конца в формате Y-m-d.
                'end_date' => $request->end_date->format('Y-m-d'),
                // Русское название статуса.
                'status' => $request->statusLabel(),
            ])
            // Возвращаем обычный PHP-массив.
            ->all();
    }

    // Сохраняет одну запись в историю заявки.
    private function addHistory(DocumentRequest $documentRequest, ?User $user, string $action, string $title, ?string $body = null): void
    {
        // Связь histories() сама подставит document_request_id.
        $documentRequest->histories()->create([
            // user_id может быть null, если событие сделано системой.
            'user_id' => $user?->id,
            // action - технический код события: created, rejected и так далее.
            'action' => $action,
            // title - короткий заголовок для интерфейса.
            'title' => $title,
            // body - дополнительное описание события.
            'body' => $body,
        ]);
    }

    // Создает уведомление для одного конкретного пользователя.
    private function notifyUser(User $user, DocumentRequest $documentRequest, string $title, string $body): void
    {
        // Создаем строку в таблице erp_notifications.
        ErpNotification::create([
            // Кому показать уведомление.
            'user_id' => $user->id,
            // К какой заявке относится уведомление.
            'document_request_id' => $documentRequest->id,
            // Короткий заголовок.
            'title' => $title,
            // Основной текст уведомления.
            'body' => $body,
        ]);
    }

    // Находит всех одобренных пользователей с нужным правом и отправляет им уведомления.
    private function notifyUsersWithPermission(string $permission, DocumentRequest $documentRequest, string $title, string $body): void
    {
        // Берем только одобренных и не отклоненных пользователей.
        User::query()
            ->where('is_approved', true)
            ->where('is_rejected', false)
            ->get()
            // Проверяем право через метод User::hasPermission().
            ->filter(fn (User $user) => $user->hasPermission($permission))
            // Каждому найденному пользователю создаем свое уведомление.
            ->each(fn (User $user) => $this->notifyUser($user, $documentRequest, $title, $body));
    }
}
