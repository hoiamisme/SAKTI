<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Redirect berdasarkan role setelah login berhasil.
     */
    protected function redirectTo(): string
    {
        return Auth::user()->role === 'admin'
            ? '/admin/dashboard'
            : '/penjaga/dashboard';
    }

    /**
     * Override: setelah login gunakan window.location.replace() agar
     * halaman login tidak masuk browser history — back button dari
     * dashboard tidak akan kembali ke /login atau /logout.
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);

        $url = $this->redirectPath();

        return response()->view('auth.redirect', ['url' => $url]);
    }
}
