<?php
// Generar Token en sesión si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Función helper para vistas
function csrf_field() {
    $token = $_SESSION['csrf_token'];
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// Validación global (Middleware rústico)
function verificarCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token_enviado = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'], $token_enviado)) {
            http_response_code(403);
            die("Error de seguridad: Token CSRF inválido o expirado. Vuelve a recargar el formulario.");
        }
    }
}
