<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class UserController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id != ? ORDER BY created_at DESC");
        $stmt->execute([\App\Core\Session::get('user_id')]);
        $users = $stmt->fetchAll();

        require __DIR__ . '/../templates/users-list.php';
    }

    public function create() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /admin/users");
            die();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $db = Database::getInstance();
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_ARGON2ID);
            $role = $_POST['role'] ?? 'user';
            $status = $_POST['status'] ?? 'active';

            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password, $role, $status]);

            \App\Core\Session::setFlash('success', 'User created successfully.');
            header("Location: /admin/users");
            die();
        }

        require __DIR__ . '/../templates/user-form.php';
    }

    public function edit($id) {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin' && \App\Core\Session::get('user_role') !== 'admin') {
            header("Location: /admin/users");
            die();
        }

        $db = Database::getInstance();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $username = $_POST['username'];
            $email = $_POST['email'];
            $role = $_POST['role'] ?? 'user';
            $status = $_POST['status'] ?? 'active';
            $coins = $_POST['coin_balance'] ?? 0;

            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_ARGON2ID);
                $stmt = $db->prepare("UPDATE users SET username=?, email=?, password_hash=?, role=?, status=?, coin_balance=? WHERE id=?");
                $stmt->execute([$username, $email, $password, $role, $status, $coins, $id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username=?, email=?, role=?, status=?, coin_balance=? WHERE id=?");
                $stmt->execute([$username, $email, $role, $status, $coins, $id]);
            }

            \App\Core\Session::setFlash('success', 'User updated successfully.');
            header("Location: /admin/users");
            die();
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        require __DIR__ . '/../templates/user-form.php';
    }

    public function delete($id) {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /admin/users");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        \App\Core\Session::setFlash('success', 'User deleted successfully.');
        header("Location: /admin/users");
        die();
    }

    public function suspend($id) {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin' && \App\Core\Session::get('user_role') !== 'admin') {
            header("Location: /admin/users");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE users SET status = IF(status = 'active', 'blocked', 'active') WHERE id = ?");
        $stmt->execute([$id]);

        \App\Core\Session::setFlash('success', 'User status toggled successfully.');
        header("Location: /admin/users");
        die();
    }

    public function loginAs($id) {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /admin/users");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch();

        if ($targetUser) {
            \App\Core\Session::set('_admin_impersonating', \App\Core\Session::get('user_id'));
            \App\Core\Auth::login($targetUser['id'], $targetUser['username'], $targetUser['email'], $targetUser['role'], (float) $targetUser['coin_balance']);
            header("Location: /");
            die();
        }

        \App\Core\Session::setFlash('error', 'User not found.');
        header("Location: /admin/users");
        die();
    }
}
