<?php

namespace App\Controllers;

use CodeIgniter\Database\BaseConnection;

class Auth extends BaseController
{
    public function index()
    {
        if (session('user')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login', ['title' => 'Masuk ke Mynet']);
    }

    public function login()
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $user = db_connect()->table('users')
            ->where('username', $username)
            ->where('active', 1)
            ->get()->getRowArray();

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Nama pengguna atau kata sandi tidak cocok.');
        }

        session()->regenerate(true);
        db_connect()->table('users')->where('id', $user['id'])->update([
            'last_login_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        session()->set('user', [
            'id' => (int) $user['id'],
            'branch_id' => $user['branch_id'] ? (int) $user['branch_id'] : null,
            'customer_id' => $user['customer_id'] ? (int) $user['customer_id'] : null,
            'name' => $user['full_name'],
            'username' => $user['username'],
            'role' => $user['role'],
            'force_password_change' => (bool) $user['force_password_change'],
            'branch_name' => $user['branch_id'] ? (db_connect()->table('branches')->select('name')->where('id', $user['branch_id'])->get()->getRow('name') ?? null) : null,
        ]);

        return redirect()->to(! empty($user['force_password_change']) ? '/auth/password' : '/dashboard');
    }

    public function changePassword()
    {
        return view('auth/password', ['title' => 'Ubah kata sandi']);
    }

    public function savePassword()
    {
        $password = (string) $this->request->getPost('password');
        $confirmation = (string) $this->request->getPost('password_confirm');
        if (strlen($password) < 12 || $password !== $confirmation) {
            return redirect()->back()->with('error', 'Gunakan kata sandi minimal 12 karakter dan ulangi dengan benar.');
        }

        db_connect()->table('users')->where('id', session('user')['id'])->update([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'force_password_change' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $user = session('user');
        $user['force_password_change'] = false;
        session()->set('user', $user);

        return redirect()->to('/dashboard')->with('success', 'Kata sandi berhasil diperbarui.');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/')->with('success', 'Anda sudah keluar dari sistem.');
    }
}
