<?php
require_once 'Models/Usuario.php';
class AuthController {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ── Mostrar formulario de login ────────────────────────────────────────────
    public function login(): void {
        // Si ya hay sesión activa, redirigir al panel correspondiente
        if (isset($_SESSION['user'])) {
            $this->redirigirSegunRol($_SESSION['user']['rol']);
        }
        require 'Views/Auth/Login.php';
    }

    // ── Procesar credenciales ──────────────────────────────────────────────────
    public function autenticar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        // ==========================================
        // RATE LIMITING DE LOGIN (Prevención Fuerza Bruta)
        // ==========================================
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['lockout_time'] = 0;
        }

        // Si está bloqueado, verificamos si ya pasó el tiempo (ej. 5 minutos)
        if ($_SESSION['login_attempts'] >= 5) {
            if (time() - $_SESSION['lockout_time'] < 300) {
                $_SESSION['error_login'] = 'Demasiados intentos fallidos. Por favor espera 5 minutos.';
                header('Location: index.php?controller=auth&action=login');
                exit;
            } else {
                // Resetear si ya pasaron los 5 min
                $_SESSION['login_attempts'] = 0;
            }
        }

        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$email || !$password) {
            $_SESSION['error_login'] = 'Ingresa tu correo y contraseña.';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $usuarioModel = new Usuario($this->db);
        $user         = $usuarioModel->getByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            
            // Mitigar Session Fixation
            session_regenerate_id(true);

            // Resetear Rate Limit
            $_SESSION['login_attempts'] = 0;
            $_SESSION['lockout_time'] = 0;

            // ── Guardar sesión con datos esenciales (multitenant) ─────────────
            $_SESSION['user'] = [
                'id'         => $user['id'],
                'nombre'     => $user['nombre']     ?? $user['email'],
                'email'      => $user['email'],
                'rol'        => $user['rol'],
                'cliente_id' => $user['cliente_id'],
            ];

            $this->redirigirSegunRol($user['rol']);

        } else {
            // Incrementar Rate Limit
            $_SESSION['login_attempts']++;
            $_SESSION['lockout_time'] = time();

            $_SESSION['error_login'] = 'Correo o contraseña incorrectos.';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
    }

    // ── Cerrar sesión ─────────────────────────────────────────────────────────
    public function logout(): void {
        $_SESSION = [];
        session_destroy();
        header('Location: index.php?controller=auth&action=login');
        exit;
    }

    // ── Helper: redirigir según rol ───────────────────────────────────────────
    private function redirigirSegunRol(string $rol): void {
        match ($rol) {
            'superadmin' => header('Location: index.php?controller=superadmin&action=index'),
            'admin'      => header('Location: index.php?controller=admin&action=index'),
            'empleado'   => header('Location: index.php?controller=admin&action=citas'),
            default      => header('Location: index.php?controller=auth&action=login'),
        };
        exit;
    }
}