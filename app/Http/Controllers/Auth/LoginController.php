<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\HrisAuthService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    private const LOGIN_SOURCE_HRIS = 'hris';
    private const LOGIN_SOURCE_LOCAL = 'local';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
            'login_source' => 'nullable|in:' . self::LOGIN_SOURCE_HRIS . ',' . self::LOGIN_SOURCE_LOCAL,
        ], [
            'login_source.in' => 'Metode login tidak valid. Silakan pilih HRIS V-People atau akun V-Ops.',
        ]);
    }

    protected function credentials(Request $request)
    {
        return [
            'email' => $request->get('email'),
            'password' => $request->get('password'),
            'is_active' => true,
        ];
    }

    protected function attemptLogin(Request $request)
    {
        $loginSource = (string) $request->get('login_source');

        if ($loginSource === self::LOGIN_SOURCE_LOCAL) {
            return $this->guard()->attempt($this->credentials($request), $request->filled('remember'));
        }

        if ($loginSource === self::LOGIN_SOURCE_HRIS) {
            return $this->attemptHrisLogin($request);
        }

        if ($this->guard()->attempt($this->credentials($request), $request->filled('remember'))) {
            return true;
        }

        return $this->attemptHrisLogin($request);
    }

    private function attemptHrisLogin(Request $request): bool
    {
        $email = (string) $request->get($this->username());
        $localUser = User::where('email', $email)->first();

        if ($localUser && !data_get($localUser, 'is_active', true)) {
            return false;
        }

        $hrisAuth = app(HrisAuthService::class);
        $hrisUser = $hrisAuth->findValidUser($email, (string) $request->get('password'));

        if (!$hrisUser) {
            return false;
        }

        $localUser = $localUser ?: new User(['email' => $email]);

        if (!$localUser->exists) {
            $localUser->name = $hrisAuth->displayName($hrisUser);
            $localUser->role = User::ROLE_STAFF;
            $localUser->is_active = true;
            $localUser->password = Hash::make(Str::random(48));
        } elseif (blank($localUser->name)) {
            $localUser->name = $hrisAuth->displayName($hrisUser);
        }

        $localUser->save();
        $this->guard()->login($localUser, $request->filled('remember'));

        return true;
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        $user = User::where('email', $request->get('email'))->first();

        if ($user && !$user->is_active) {
            throw ValidationException::withMessages([
                $this->username() => ['Akun Anda sedang dinonaktifkan. Silakan hubungi admin.'],
            ]);
        }

        $loginSource = (string) $request->get('login_source');

        if ($loginSource === self::LOGIN_SOURCE_HRIS) {
            throw ValidationException::withMessages([
                $this->username() => ['Email atau password HRIS V-People tidak sesuai, atau akun HRIS belum aktif.'],
            ]);
        }

        if ($loginSource === self::LOGIN_SOURCE_LOCAL) {
            throw ValidationException::withMessages([
                $this->username() => ['Email atau password akun V-Ops tidak sesuai. Pastikan akun lokal sudah dibuat atau pilih login HRIS V-People.'],
            ]);
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }
}
