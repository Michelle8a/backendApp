<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

// Auth::routes() registra /password/reset y /password/email contra este
// controlador (stub oficial de laravel/ui). El panel web lo necesita para
// que "Olvidé mi contraseña" no devuelva 500.
class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;
}
