<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Support\Auth;

final class AuthController extends Controller
{
    public function showLogin(Request $request): string
    {
        if (Auth::check()) {
            return $this->redirect('/');
        }
        return $this->view('auth/login', ['title' => 'Sign in'], 'layouts/auth');
    }

    public function login(Request $request): string
    {
        $this->requireCsrf($request);

        $username = (string) $request->input('username', '');
        $password = (string) $request->input('password', '');

        if (Auth::attempt($username, $password)) {
            Session::flash('success', 'Welcome back, ' . $username . '.');
            return $this->redirect('/');
        }

        Session::flash('error', 'Invalid username or password.');
        return $this->redirect('/login');
    }

    public function logout(Request $request): string
    {
        $this->requireCsrf($request);
        Auth::logout();
        Session::flash('success', 'You have been signed out.');
        return $this->redirect('/login');
    }
}
