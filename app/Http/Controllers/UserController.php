<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->paginate(10);
        return view('users.index', compact('users'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'correo' => ['required', 'correo', 'max:255', Rule::unique('users')->ignore($user->id)],
            'rol'   => ['required', Rule::in(['admin', 'user'])],
        ]);

        // Evita que el admin se quite su propio rol y se quede sin acceso
        if ($user->id === Auth::id() && $data['rol'] !== 'admin') {
            return back()->with('error', 'No puedes quitarte tu propio rol de administrador.');
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }
}