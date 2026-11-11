<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function username()
    {
        return 'correo';
    }

    protected function credentials(Request $request)
    {
        return [
            'correo' => $request->input('correo'),
            'password' => $request->input('password'),
            'rol' => 'admin',
            'estado' => 'activo',
        ];
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->rol !== 'admin') {
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['permiso' => 'No tienes permisos autorizados para acceder al panel de administración.'])
                ->withInput($request->only('correo'));
        }

        return redirect()->intended(route('home'));
    }

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}