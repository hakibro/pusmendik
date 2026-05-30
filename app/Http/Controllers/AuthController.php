<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = DB::connection('exam')
            ->table('users')
            ->select('users.*', 'roles.name as role')
            ->join('model_has_roles', function ($join) {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', 'like', '%User');
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('users.email', $credentials['email'])
            ->where('roles.name', 'data')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withInput(['email' => $credentials['email']])->with('error', 'Email, password, atau role tidak sesuai.');
        }

        $request->session()->regenerate();
        $request->session()->put('data_user', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return redirect()->intended(route('students.index'))->with('success', 'Login berhasil.');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('data_user');
        $request->session()->regenerateToken();

        return redirect()->route('dashboard')->with('success', 'Anda sudah logout.');
    }
}
