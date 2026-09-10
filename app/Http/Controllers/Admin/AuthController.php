<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create() { return view('admin.auth.login'); }
    public function store(Request $request)
    {
        $credentials=$request->validate(['email'=>'required|email','password'=>'required|string']);
        if (Auth::attempt($credentials,$request->boolean('remember'))) {
            $request->session()->regenerate();
            if (! auth()->user()?->is_admin) { Auth::logout(); return back()->withErrors(['email'=>'Admin access required.']); }
            return redirect()->intended(route('admin.dashboard'));
        }
        return back()->withErrors(['email'=>'ইমেইল বা পাসওয়ার্ড সঠিক নয়।'])->onlyInput('email');
    }
    public function destroy(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('admin.login'); }
}
