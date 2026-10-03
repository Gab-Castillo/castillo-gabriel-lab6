<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/BaseApiController.php';

class AuthController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
        $this->call->model('UserModel');
    }

    /** POST /api/auth/register */
    public function register()
    {
        $this->api->require_method('POST');
        $this->api->rate_limit('register_' . ($_SERVER['REMOTE_ADDR'] ?? 'x'), 10, 60);

        $in       = $this->json_input();
        $username = trim((string) ($in['username'] ?? ''));
        $email    = strtolower(trim((string) ($in['email'] ?? '')));
        $password = (string) ($in['password'] ?? '');

        $errors = [];
        if (strlen($username) < 3 || strlen($username) > 100) {
            $errors['username'] = 'Use 3 to 100 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Use at least 8 characters.';
        }
        if ($errors) {
            $this->fail_validation($errors);
        }

        if ($this->UserModel->find_by('email', $email)) {
            $this->api->respond_error('That email is already registered.', 409);
        }
        if ($this->UserModel->find_by('username', $username)) {
            $this->api->respond_error('That username is already taken.', 409);
        }

        $id = $this->UserModel->insert([
            'username'  => $username,
            'email'     => $email,
            'password'  => password_hash($password, PASSWORD_DEFAULT),
            'role'      => 'user',
            'is_active' => 1,
        ]);

        $this->ok(['id' => (int) $id, 'username' => $username, 'email' => $email], 'Account created', 201);
    }

    /** POST /api/auth/login */
    public function login()
    {
        $this->api->require_method('POST');
        $this->api->rate_limit('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'x'), 10, 60);

        $in       = $this->json_input();
        // Accepts an email or a username in "login" (the old "email" field still works).
        $login    = trim((string) ($in['login'] ?? $in['email'] ?? ''));
        $password = (string) ($in['password'] ?? '');

        if ($login === '' || $password === '') {
            $this->fail_validation(['login' => 'Enter your email or username and your password.']);
        }

        $user = strpos($login, '@') !== false
            ? $this->UserModel->find_by('email', strtolower($login))
            : $this->UserModel->find_by('username', $login);

        if (!$user || !(int) $user['is_active'] || !password_verify($password, $user['password'])) {
            $this->api->respond_error('Email, username or password is incorrect.', 401);
        }

        $tokens = $this->api->issue_tokens([
            'id'   => (int) $user['id'],
            'role' => $user['role'],
        ]);

        $this->ok([
            'user'   => ['id' => (int) $user['id'], 'username' => $user['username'], 'email' => $user['email']],
            'tokens' => $tokens,
        ], 'Logged in');
    }

    /** POST /api/auth/refresh  (the library responds with the new token pair) */
    public function refresh()
    {
        $this->api->require_method('POST');
        $in = $this->json_input();
        $token = (string) ($in['refresh_token'] ?? '');
        if ($token === '') {
            $this->api->respond_error('refresh_token is required.', 400);
        }
        $this->api->refresh_access_token($token);
    }

    /** POST /api/auth/logout */
    public function logout()
    {
        $this->api->require_method('POST');
        $in = $this->json_input();
        $token = (string) ($in['refresh_token'] ?? '');
        if ($token !== '') {
            $this->api->revoke_refresh_token($token);
        }
        $this->ok(null, 'Logged out');
    }
}
