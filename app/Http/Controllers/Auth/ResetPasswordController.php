<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;

// Segundo controlador que exige Auth::routes() (stub oficial de laravel/ui).
class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    protected $redirectTo = '/home';
}
