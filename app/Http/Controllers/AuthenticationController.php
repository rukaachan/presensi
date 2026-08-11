<?php

namespace App\Http\Controllers;

use App\Authorization\RoleCode;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthenticationController extends Controller
{
    private const REDIRECT_MAP = [
        'administrator' => 'administration/dashboard',
        'counseling_teacher' => 'counseling/dashboard',
        'duty_teacher' => 'duty-teacher/dashboard',
        'class_officer' => 'class-officer/dashboard',
        'homeroom_teacher' => 'homeroom/dashboard',
        'student' => 'student/dashboard',
    ];

    public function index()
    {
        if (! Auth::check()) {
            return view('auth.login');
        }

        $account = Auth::user();
        if ($account instanceof Account) {
            $role = RoleCode::forAccount($account)?->value;
            if ($role !== null) {
                return redirect(self::REDIRECT_MAP[$role]);
            }
        }

        abort(403);
    }

    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = strtolower($validated['username']).'|'.$request->ip();
        $maxAttempts = 5;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withInput($request->only('username'))
                ->with('error', __('auth.too_many_attempts', ['seconds' => $seconds]));
        }

        if (Auth::attempt([
            'username' => $validated['username'],
            'password' => $validated['password'],
        ])) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $account = Auth::user();
            $role = $account instanceof Account ? RoleCode::forAccount($account)?->value : null;
            if ($role !== null) {
                return redirect(self::REDIRECT_MAP[$role])->with('success', __('auth.login_success'));
            }
        }

        RateLimiter::hit($throttleKey, $decaySeconds);
        $request->session()->regenerate();

        return back()->withInput($request->only('username'))
            ->with('error', __('auth.login_failed'));
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->regenerate();

        return redirect('/')->with('success', __('auth.logout_success'));
    }
}
