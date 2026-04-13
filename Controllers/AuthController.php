<?php
require_once BASE_PATH . 'Models/Usuario.php';
class AuthController {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ── Mostrar formulario de login ────────────────────────────────────────────
    public function login(): void {
        // Si ya hay sesión activa, redirigir al panel correspondiente
        if (isset($_SESSION['user'])) {
            if (!empty($_SESSION['user']['force_password_change'])) {
                header('Location: index.php?controller=auth&action=cambiarPassword');
                exit;
            }
            $this->redirigirSegunRol($_SESSION['user']['rol']);
        }
        require BASE_PATH . 'Views/Auth/Login.php';
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
                'id'                    => $user['id'],
                'nombre'                => $user['nombre']     ?? $user['email'],
                'email'                 => $user['email'],
                'rol'                   => $user['rol'],
                'cliente_id'            => $user['cliente_id'],
                'force_password_change' => (int)($user['force_password_change'] ?? 0),
            ];

            if (!empty($_SESSION['user']['force_password_change'])) {
                header('Location: index.php?controller=auth&action=cambiarPassword');
                exit;
            }

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

    public function cambiarPassword(): void {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        require BASE_PATH . 'Views/Auth/ChangePassword.php';
    }

    public function guardarPassword(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=auth&action=cambiarPassword');
            exit;
        }

        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $password = trim($_POST['password'] ?? '');
        $confirm  = trim($_POST['confirm_password'] ?? '');

        if (!$password || !$confirm) {
            $_SESSION['error_login'] = 'Completa ambos campos.';
            header('Location: index.php?controller=auth&action=cambiarPassword');
            exit;
        }

        if ($password !== $confirm) {
            $_SESSION['error_login'] = 'Las contraseñas no coinciden.';
            header('Location: index.php?controller=auth&action=cambiarPassword');
            exit;
        }

        if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9])[A-Za-z0-9!@#$%^&*()_+\-\=\.,?]{8,}$/', $password)) {
            $_SESSION['error_login'] = 'La contraseña debe tener al menos 8 caracteres, contener una mayúscula y puede incluir símbolos comunes como ! @ # $ % ^ & * ( ) _ + - = . , ?.';
            header('Location: index.php?controller=auth&action=cambiarPassword');
            exit;
        }

        $usuarioModel = new Usuario($this->db);
        $usuarioModel->updatePassword((int)$_SESSION['user']['id'], $password, false);
        $_SESSION['user']['force_password_change'] = 0;

        $_SESSION['success'] = 'Contraseña actualizada correctamente.';
        $this->redirigirSegunRol($_SESSION['user']['rol']);
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