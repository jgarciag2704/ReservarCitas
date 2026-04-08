<?php
require_once BASE_PATH . 'Models/Empleado.php';
require_once BASE_PATH . 'Models/Cliente.php';

class EmployeeController {

    private $db;
    private $clienteId;
    private $empleadoModel;
    private $negocioActual;

    public function __construct($db) {
        $this->db = $db;

        // ── Protección: solo el rol 'admin' puede gestionar empleados ─────────
        $this->checkSoloAdmin();

        // ── cliente_id del admin autenticado (multitenant) ────────────────────
        $this->clienteId = (int)$_SESSION['user']['cliente_id'];

        // ── Modelos ───────────────────────────────────────────────────────────
        $this->empleadoModel = new Empleado($db);

        $clienteModel = new Cliente($db);
        $this->negocioActual = $clienteModel->find($this->clienteId);
    }

    // =========================================================================
    // GUARD: solo admin puede gestionar empleados
    // =========================================================================
    private function checkSoloAdmin(): void {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        if ($_SESSION['user']['rol'] !== 'admin') {
            http_response_code(403);
            die('⛔ Acceso denegado: se requiere rol admin para gestionar empleados.');
        }
    }

    // =========================================================================
    // INDEX — Listar empleados
    // =========================================================================
    public function index(): void {
        $empleados     = $this->empleadoModel->getByCliente($this->clienteId);
        $negocioActual = $this->negocioActual;

        // Cargar todos los servicios del negocio (para el modal de asignación)
        require_once BASE_PATH . 'Models/Servicio.php';
        $servicioModel  = new Servicio($this->db);
        $todosServicios = $servicioModel->getByCliente($this->clienteId);

        // Asignaciones actuales por empleado: [empleado_id => [servicio_id, ...]]
        $serviciosAsignados = [];
        foreach ($empleados as $emp) {
            $asignados = $servicioModel->getServiciosPorEmpleado((int)$emp['id'], $this->clienteId);
            $serviciosAsignados[$emp['id']] = array_column($asignados, 'id');
        }

        require BASE_PATH . 'Views/Admin/empleados.php';
    }

    // =========================================================================
    // ASIGNAR SERVICIOS A EMPLEADO
    // =========================================================================
    public function asignarServicios(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=employee&action=index');
            exit;
        }

        $empleadoId  = (int)($_POST['empleado_id'] ?? 0);
        $servicioIds = $_POST['servicio_ids'] ?? [];

        if (!$empleadoId) {
            $_SESSION['error'] = 'Empleado inválido.';
            header('Location: index.php?controller=employee&action=index');
            exit;
        }

        // Verificar que el empleado pertenece al cliente
        $emp = $this->empleadoModel->find($empleadoId, $this->clienteId);
        if (!$emp) {
            $_SESSION['error'] = 'Acción no permitida.';
            header('Location: index.php?controller=employee&action=index');
            exit;
        }

        require_once 'Models/Servicio.php';
        $servicioModel = new Servicio($this->db);
        $servicioModel->asignarServiciosAEmpleado($empleadoId, $this->clienteId, array_map('intval', $servicioIds));

        $_SESSION['success'] = 'Servicios asignados a ' . htmlspecialchars($emp['nombre']) . '.';
        header('Location: index.php?controller=employee&action=index');
        exit;
    }

    // =========================================================================
    // STORE — Crear empleado
    // =========================================================================
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=employee&action=index');
            exit;
        }

        $nombre       = trim($_POST['nombre']       ?? '');
        $email        = trim($_POST['email']        ?? '');
        $telefono     = trim($_POST['telefono']     ?? '');
        $especialidad = trim($_POST['especialidad'] ?? '');
        $experiencia  = trim($_POST['experiencia']  ?? '');
        $google_maps  = trim($_POST['google_maps']  ?? '');
        $password     = trim($_POST['password']     ?? '');

        // Validaciones básicas
        if (!$nombre || !$email || !$password) {
            $_SESSION['error'] = 'Nombre, email y contraseña son obligatorios.';
            header('Location: index.php?controller=employee&action=index');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El email no tiene un formato válido.';
            header('Location: index.php?controller=employee&action=index');
            exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['error'] = 'La contraseña debe tener al menos 6 caracteres.';
            header('Location: index.php?controller=employee&action=index');
            exit;
        }

        $ok = $this->empleadoModel->crear([
            'cliente_id'   => $this->clienteId,
            'nombre'       => $nombre,
            'email'        => $email,
            'telefono'     => $telefono ?: null,
            'especialidad' => $especialidad ?: null,
            'experiencia'  => $experiencia ?: null,
            'google_maps'  => $google_maps ?: null,
            'password'     => $password,
        ]);

        if ($ok) {
            $_SESSION['success'] = "Empleado «{$nombre}» creado correctamente.";
        } else {
            $_SESSION['error'] = 'El email ya está registrado. Usa uno diferente.';
        }

        header('Location: index.php?controller=employee&action=index');
        exit;
    }

    // =========================================================================
    // UPDATE — Editar empleado
    // =========================================================================
    public function update(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=employee&action=index');
            exit;
        }

        $id           = (int)($_POST['id']           ?? 0);
        $nombre       = trim($_POST['nombre']       ?? '');
        $email        = trim($_POST['email']        ?? '');
        $telefono     = trim($_POST['telefono']     ?? '');
        $especialidad = trim($_POST['especialidad'] ?? '');
        $experiencia  = trim($_POST['experiencia']  ?? '');
        $google_maps  = trim($_POST['google_maps']  ?? '');
        $password     = trim($_POST['password']     ?? '');

        if (!$id || !$nombre || !$email) {
            $_SESSION['error'] = 'Nombre y email son obligatorios.';
            header('Location: index.php?controller=employee&action=index');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El email no tiene un formato válido.';
            header('Location: index.php?controller=employee&action=index');
            exit;
        }

        if (!empty($password) && strlen($password) < 6) {
            $_SESSION['error'] = 'La contraseña debe tener al menos 6 caracteres.';
            header('Location: index.php?controller=employee&action=index');
            exit;
        }

        $ok = $this->empleadoModel->actualizar($id, $this->clienteId, [
            'nombre'       => $nombre,
            'email'        => $email,
            'telefono'     => $telefono ?: null,
            'especialidad' => $especialidad ?: null,
            'experiencia'  => $experiencia ?: null,
            'google_maps'  => $google_maps ?: null,
            'password'     => $password,
        ]);

        if ($ok) {
            $_SESSION['success'] = 'Empleado actualizado correctamente.';
        } else {
            $_SESSION['error'] = 'No se pudo actualizar. El email puede estar en uso o el empleado no existe.';
        }

        header('Location: index.php?controller=employee&action=index');
        exit;
    }

    // =========================================================================
    // TOGGLE ACTIVO — Activar / desactivar empleado
    // =========================================================================
    public function toggleActivo(): void {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            $_SESSION['error'] = 'ID de empleado inválido.';
            header('Location: index.php?controller=employee&action=index');
            exit;
        }

        $this->empleadoModel->toggleActivo($id, $this->clienteId);

        $_SESSION['success'] = 'Estado del empleado actualizado.';
        header('Location: index.php?controller=employee&action=index');
        exit;
    }

    // =========================================================================
    // DELETE — Eliminar empleado
    // =========================================================================
    public function delete(): void {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            $_SESSION['error'] = 'ID de empleado inválido.';
            header('Location: index.php?controller=employee&action=index');
            exit;
        }

        $this->empleadoModel->eliminar($id, $this->clienteId);

        $_SESSION['success'] = 'Empleado eliminado correctamente.';
        header('Location: index.php?controller=employee&action=index');
        exit;
    }
}
