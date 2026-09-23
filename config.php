<?php
/**
 * SUITABLE - B2B Outreach & Client Tracking Platform
 * Configuration & Database Helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_PATH', __DIR__ . '/data/crm_suitable.db');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $db_dir = dirname(DB_PATH);
        if (!is_dir($db_dir)) {
            mkdir($db_dir, 0777, true);
        }
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Enable foreign keys
        $pdo->exec('PRAGMA foreign_keys = ON;');
    }
    return $pdo;
}

// Authentication Helpers
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'Usuario',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'enviador',
    ];
}

function is_admin(): bool {
    return is_logged_in() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function require_auth(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void {
    require_auth();
    if (!is_admin()) {
        http_response_code(403);
        die('Acceso restringido únicamente a Administradores.');
    }
}

// Setting Helpers
function get_setting(string $key, string $default = ''): string {
    $db = get_db();
    $stmt = $db->prepare('SELECT value FROM settings WHERE key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string)$row['value'] : $default;
}

function set_setting(string $key, string $value): void {
    $db = get_db();
    $stmt = $db->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    $stmt->execute([$key, $value]);
}

// Status labels & badges
function get_status_info(string $status): array {
    $map = [
        'nuevo' => [
            'label' => 'Nuevo Prospecto',
            'badge' => 'badge-gray',
            'color' => '#64748B',
            'step' => 1
        ],
        'correo_1_enviado' => [
            'label' => 'Correo 1 Enviado',
            'badge' => 'badge-blue',
            'color' => '#0284C7',
            'step' => 2
        ],
        'correo_2_enviado' => [
            'label' => 'Correo 2 (B2B) Enviado',
            'badge' => 'badge-teal',
            'color' => '#1E8888',
            'step' => 3
        ],
        'tallaje_agendado' => [
            'label' => 'Tallaje Agendado',
            'badge' => 'badge-purple',
            'color' => '#8B5CF6',
            'step' => 4
        ],
        'cotizacion_enviada' => [
            'label' => 'Cotización Enviada',
            'badge' => 'badge-amber',
            'color' => '#D97706',
            'step' => 5
        ],
        'ganado' => [
            'label' => 'Venta Cerrada',
            'badge' => 'badge-emerald',
            'color' => '#059669',
            'step' => 6
        ],
        'perdido' => [
            'label' => 'Descartado / Pausado',
            'badge' => 'badge-rose',
            'color' => '#E11D48',
            'step' => 0
        ],
    ];
    return $map[$status] ?? [
        'label' => ucfirst($status),
        'badge' => 'badge-gray',
        'color' => '#64748B',
        'step' => 1
    ];
}

// CSRF Protection
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function json_response(array $data, int $status_code = 200): void {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
