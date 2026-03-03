<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $password = config('app.admin_password');
        if (empty($password)) {
            return redirect()->back()->with('error', 'Admin access is not configured (set APP_ADMIN_PASSWORD in .env).');
        }
        if ($request->input('password') !== $password) {
            return redirect()->back()->withInput()->with('error', 'Invalid password.');
        }
        session(['admin_logged_in' => true]);
        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_logged_in');
        return redirect()->route('admin.login');
    }
}
