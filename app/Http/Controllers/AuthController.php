<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, RateLimiter};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller
{
    public function create() { return view('auth.login'); }
    public function store(Request $request)
    {
        $data = $request->validate(['username'=>['required','string','max:50'],'password'=>['required','string']]);
        $key = 'login:'.Str::lower($data['username']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['username'=>'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }
        if (!Auth::attempt($data)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['username'=>'These credentials do not match an active staff account.']);
        }
        RateLimiter::clear($key); $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }
    public function destroy(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
