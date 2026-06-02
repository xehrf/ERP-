<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentRequestController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $this->currentUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $query = DocumentRequest::with(['user', 'hrApprover', 'directorApprover'])->latest();

        if ($user->role === 'employee') {
            $query->where('user_id', $user->id);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('requests.index', [
            'currentUser' => $user,
            'requests' => $query->paginate(12)->withQueryString(),
            'stats' => $this->statsFor($user),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $this->currentUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return view('requests.create', [
            'currentUser' => $user,
            'type' => $request->query('type', 'vacation'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'type' => ['required', 'in:vacation,sick_leave'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        [$calendarDays, $workingDays] = $this->calculateDays($data['start_date'], $data['end_date']);

        DocumentRequest::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'calendar_days' => $calendarDays,
            'working_days' => $workingDays,
            'status' => 'pending_hr',
            'comment' => $data['comment'] ?? null,
        ]);

        return redirect()->route('requests.index')->with('success', 'Заявка создана и отправлена кадровику.');
    }

    public function approveHr(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $user = $this->currentUser($request);
        abort_unless($user && in_array($user->role, ['hr', 'admin'], true), 403);

        $documentRequest->update([
            'status' => 'pending_director',
            'hr_approved_by' => $user->id,
            'hr_approved_at' => now(),
        ]);

        return back()->with('success', 'Кадровая проверка выполнена.');
    }

    public function approveDirector(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $user = $this->currentUser($request);
        abort_unless($user && in_array($user->role, ['director', 'admin'], true), 403);

        $documentRequest->update([
            'status' => 'approved',
            'director_approved_by' => $user->id,
            'director_approved_at' => now(),
        ]);

        return back()->with('success', 'Заявка утверждена директором.');
    }

    public function reject(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $user = $this->currentUser($request);
        abort_unless($user && in_array($user->role, ['hr', 'director', 'admin'], true), 403);

        $documentRequest->update(['status' => 'rejected']);

        return back()->with('success', 'Заявка отклонена.');
    }

    private function currentUser(Request $request): ?User
    {
        $id = $request->session()->get('user_id');

        return $id ? User::find($id) : null;
    }

    private function calculateDays(string $startDate, string $endDate): array
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $calendarDays = 0;
        $workingDays = 0;

        foreach ($period as $date) {
            $calendarDays++;

            if (! $date->isWeekend()) {
                $workingDays++;
            }
        }

        return [$calendarDays, $workingDays];
    }

    private function statsFor(User $user): array
    {
        $query = DocumentRequest::query();

        if ($user->role === 'employee') {
            $query->where('user_id', $user->id);
        }

        return [
            'total' => (clone $query)->count(),
            'pending_hr' => (clone $query)->where('status', 'pending_hr')->count(),
            'pending_director' => (clone $query)->where('status', 'pending_director')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
        ];
    }
}
