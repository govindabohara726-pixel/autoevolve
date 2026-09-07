<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->is_admin) return redirect()->route('admin.dashboard');
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email'=>['required','email'],'password'=>['required','string']]);
        if (!Auth::attempt($credentials, $request->boolean('remember'))) return back()->withErrors(['email'=>'Invalid credentials.'])->onlyInput('email');
        $request->session()->regenerate();
        if (!$request->user()->is_admin) {
            Auth::logout();
            return back()->withErrors(['email'=>'This account is not an administrator.']);
        }
        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
