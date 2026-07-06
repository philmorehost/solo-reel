<?php

namespace App\Controllers\Api;

use App\Core\Database;

abstract class BaseApiController {

    protected function respondJson($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        die();
    }

    /** Site base URL, e.g. https://soloshort.pmhserver.name.ng (no trailing slash). */
    protected function baseUrl(): string {
        $env = getenv('BASE_URL') ?: getenv('APP_URL');
        if ($env) {
            return rtrim($env, '/');
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $host = $_SERVER['HTTP_HOST'] ?? 'soloshort.pmhserver.name.ng';
        return ($https ? 'https' : 'http') . '://' . $host;
    }

    /** Convert a stored relative path (/assets/uploads/x.jpg) into a full URL apps can load. */
    protected function absoluteUrl(?string $path): ?string {
        if ($path === null || trim($path) === '') {
            return null;
        }
        $path = trim($path);
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return $this->baseUrl() . '/' . ltrim($path, '/');
    }

    protected function generateJWT(array $payload, string $secret): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /** Decode + verify the Bearer JWT. Returns the payload array or null. */
    protected function tokenPayload(): ?array {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (!preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
            return null;
        }
        $parts = explode('.', $matches[1]);
        if (count($parts) !== 3) {
            return null;
        }
        $secret = getenv('JWT_SECRET') ?: 'default_secret_key_change_in_production';
        $signature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $secret, true);
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        if (!hash_equals($expectedSignature, $parts[2])) {
            return null;
        }
        $payload = json_decode(base64_decode($parts[1]), true);
        if (!isset($payload['user_id']) || ($payload['exp'] ?? 0) <= time()) {
            return null;
        }
        return $payload;
    }

    /** User id from token, or null when unauthenticated (guest-friendly endpoints). */
    protected function optionalUserId(): ?int {
        $payload = $this->tokenPayload();
        return $payload ? (int)$payload['user_id'] : null;
    }

    /** User id from token; responds 401 when missing/invalid. */
    protected function requireUserId(): int {
        $payload = $this->tokenPayload();
        if (!$payload) {
            $this->respondJson(['status' => false, 'error' => 'Unauthorized or invalid token'], 401);
        }
        return (int)$payload['user_id'];
    }

    /** Admin id from token (role admin/super_admin) or active admin web session; 401/403 otherwise. */
    protected function requireAdmin(): int {
        $payload = $this->tokenPayload();
        if ($payload) {
            if (in_array($payload['role'] ?? '', ['admin', 'super_admin'], true)) {
                return (int)$payload['user_id'];
            }
            $this->respondJson(['status' => false, 'error' => 'Admin access required'], 403);
        }
        // Fall back to the admin panel's PHP session (same-origin admin UI calls).
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!empty($_SESSION['user_id']) && in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'], true)) {
            return (int)$_SESSION['user_id'];
        }
        $this->respondJson(['status' => false, 'error' => 'Unauthorized or invalid token'], 401);
    }
}
