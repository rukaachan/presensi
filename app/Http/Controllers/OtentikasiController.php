<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class OtentikasiController extends Controller
{
    private const REDIRECT_MAP = [
        6 => 'tata-usaha/dashboard',
        5 => 'guru-bk/dashboard',
        4 => 'guru-piket/dashboard',
        3 => 'pengurus-kelas/dashboard',
        2 => 'wali-kelas/dashboard',
        1 => 'siswa/dashboard',
    ];

    public function index()
    {
        if (! Auth::check()) {
            return view('auth.login');
        }

        $user = Auth::user();
        if (isset(self::REDIRECT_MAP[$user->id_role])) {
            return redirect(self::REDIRECT_MAP[$user->id_role]);
        }
    }

    public function authenticated(Request $request)
    {
        $validatedData = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ], [
            'username.required' => 'Username harus diisi',
            'password.required' => 'Password harus diisi',
        ]);

        $throttleKey = strtolower($validatedData['username']).'|'.$request->ip();
        $maxAttempts = 5;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            smilify('error', "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.");

            return redirect()->back()->withInput($request->only('username'));
        }

        $credentials = [
            'username' => $validatedData['username'],
            'password' => $validatedData['password'],
        ];

        if (Auth::attempt($credentials)) {
            RateLimiter::clear($throttleKey);

            $user = Auth::user();
            $request->session()->regenerate();
            if (isset(self::REDIRECT_MAP[$user->id_role])) {
                smilify('success', 'Berhasil Login');

                return redirect(self::REDIRECT_MAP[$user->id_role]);
            }
        }

        RateLimiter::hit($throttleKey, $decaySeconds);

        $request->session()->regenerate();
        smilify('error', 'Gagal Login');

        return redirect()->back()->withInput($request->only('username'));
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->regenerate();

        return redirect('/');
    }
}
