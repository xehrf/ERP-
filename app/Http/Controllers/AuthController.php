<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(): View
    {
        return view('auth.login', [
            'users' => User::orderBy('role')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $request->session()->put('user_id', $data['user_id']);

        return redirect()->route('requests.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('user_id');

        return redirect()->route('login');
    }
}
