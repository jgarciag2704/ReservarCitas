<?php
require_once 'Models/Servicio.php';
require_once 'Models/Horario.php';
require_once 'Models/Cita.php';
require_once 'Models/Cliente.php';

class AdminController {

    private $db;
    private $clienteId;
    private $servicioModel;
    private $horarioModel;
    private $citaModel;
    private $negocioActual;

    public function __construct($db) {
        $this->db = $db;

        // ── Protección de acceso ──────────────────────────────────────────────
        $this->checkAdmin();

        // ── cliente_id del admin autenticado (multitenant) ───────────────────
        $this->clienteId = $_SESSION['user']['cliente_id'];

        // ── Modelos (reciben PDO para no abrir segunda conexión) ──────────────
        $this->servicioModel = new Servicio($db);
        $this->horarioModel  = new Horario($db);
        $this->citaModel     = new Cita($db);

        // ── Obtener datos del negocio (incluyendo el logo) ──────────────
        $clienteModel = new Cliente($db);
        $this->negocioActual = $clienteModel->find($this->clienteId);
    }

    // =========================================================================
    // MIDDLEWARE: validar sesión y rol admin
    // =========================================================================
    private function checkAdmin(): void {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $rolesPermitidos = ['admin', 'empleado'];
        if (!in_array($_SESSION['user']['rol'], $rolesPermitidos)) {
            http_response_code(403);
            die('⛔ Acceso denegado.');
        }
    }

    // Verifica que SOLO el admin (dueño) pueda acceder. Empleados = 403.
    private function checkSoloAdmin(): void {
        if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
            http_response_code(403);
            die('⛔ Acceso denegado: esta sección es exclusiva del administrador.');
        }
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================
    public function index(): void {
        $totalServicios = count($this->servicioModel->getByCliente($this->clienteId));
        $totalHorarios  = count($this->horarioModel->getByCliente($this->clienteId));
        $totalCitas     = count($this->citaModel->getByCliente($this->clienteId));
        $citasHoy       = $this->citaModel->getByFecha($this->clienteId, date('Y-m-d'));

        // Analíticas
        $statsSemana    = $this->citaModel->getStatsCitasSemana($this->clienteId);
        $statsServicios = $this->citaModel->getStatsServicios($this->clienteId);

        $negocioActual = $this->negocioActual;

        require 'Views/Admin/dashboard.php';
    }

    // =========================================================================
    // SERVICIOS
    // =========================================================================
    public function servicios(): void {
        $this->checkSoloAdmin();
        $servicios = $this->servicioModel->getByCliente($this->clienteId);

        // Pasar empleados del negocio para el modal de asignación
        require_once 'Models/Empleado.php';
        $empleadoModel = new Empleado($this->db);
        $todosEmpleados = $empleadoModel->getByCliente($this->clienteId);

        // Cargar asignaciones actuales por servicio
        $asignaciones = [];
        foreach ($servicios as $s) {
            $asignados = $this->servicioModel->getEmpleadosPorServicio((int)$s['id'], $this->clienteId);
            $asignaciones[$s['id']] = array_column($asignados, 'id');
        }

        require 'Views/Admin/servicios.php';
    }

    public function storeServicio(): void {
        $this->checkSoloAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=admin&action=servicios');
            exit;
        }

        $nombre    = trim($_POST['nombre'] ?? '');
        $duracion  = (int)($_POST['duracion'] ?? 0);
        $precio    = (float)($_POST['precio'] ?? 0);

        if (!$nombre || $duracion <= 0) {
            $_SESSION['error'] = 'Nombre y duración son obligatorios.';
            header('Location: index.php?controller=admin&action=servicios');
            exit;
        }

        $this->servicioModel->crear($this->clienteId, $nombre, $duracion, $precio);

        $_SESSION['success'] = 'Servicio creado correctamente.';
        header('Location: index.php?controller=admin&action=servicios');
    }

    public function updateServicio(): void {
        $this->checkSoloAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=admin&action=servicios');
            exit;
        }

        $id       = (int)($_POST['id'] ?? 0);
        $nombre   = trim($_POST['nombre'] ?? '');
        $duracion = (int)($_POST['duracion'] ?? 0);
        $precio   = (float)($_POST['precio'] ?? 0);

        // Verificar que el servicio pertenece a este cliente (multitenant)
        if (!$this->servicioModel->perteneceACliente($id, $this->clienteId)) {
            $_SESSION['error'] = 'Acción no permitida.';
            header('Location: index.php?controller=admin&action=servicios');
            exit;
        }

        $this->servicioModel->actualizar($id, $this->clienteId, $nombre, $duracion, $precio);

        $_SESSION['success'] = 'Servicio actualizado.';
        header('Location: index.php?controller=admin&action=servicios');
    }

    public function deleteServicio(): void {
        $this->checkSoloAdmin();
        $id = (int)($_GET['id'] ?? 0);

        if (!$this->servicioModel->perteneceACliente($id, $this->clienteId)) {
            $_SESSION['error'] = 'Acción no permitida.';
            header('Location: index.php?controller=admin&action=servicios');
            exit;
        }

        $this->servicioModel->eliminar($id, $this->clienteId);

        $_SESSION['success'] = 'Servicio eliminado.';
        header('Location: index.php?controller=admin&action=servicios');
    }

    // =========================================================================
    // HORARIOS
    // =========================================================================
    public function horarios(): void {
        $this->checkSoloAdmin();
        $horarios = $this->horarioModel->getByCliente($this->clienteId);

        // También necesitamos los empleados para el selector del modal
        require_once 'Models/Empleado.php';
        $empleadoModel = new Empleado($this->db);
        $empleados = $empleadoModel->getByCliente($this->clienteId);

        require 'Views/Admin/horarios.php';
    }

    public function storeHorario(): void {
        $this->checkSoloAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=admin&action=horarios');
            exit;
        }

        $dias       = $_POST['dias'] ?? [];
        $inicio     = trim($_POST['inicio'] ?? '');
        $fin        = trim($_POST['fin'] ?? '');
        $empleadoId = !empty($_POST['empleado_id']) ? (int)$_POST['empleado_id'] : null;

        if (empty($dias) || !$inicio || !$fin) {
            $_SESSION['error'] = 'Debes seleccionar al menos un día y definir las horas.';
            header('Location: index.php?controller=admin&action=horarios');
            exit;
        }

        // Convertir a array si solo vino uno (por si acaso cambias el input a single en el futuro)
        if (!is_array($dias)) {
            $dias = [$dias];
        }

        foreach ($dias as $dia) {
            $this->horarioModel->crear([
                'cliente_id'  => $this->clienteId,
                'empleado_id' => $empleadoId,
                'dia'         => trim($dia),
                'inicio'      => $inicio,
                'fin'         => $fin,
            ]);
        }

        $_SESSION['success'] = 'Horarios guardados correctamente.';
        header('Location: index.php?controller=admin&action=horarios');
    }

    public function updateHorario(): void {
        $this->checkSoloAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=admin&action=horarios');
            exit;
        }

        $id         = (int)($_POST['id'] ?? 0);
        $dia        = trim($_POST['dia'] ?? '');
        $inicio     = trim($_POST['inicio'] ?? '');
        $fin        = trim($_POST['fin'] ?? '');
        $empleadoId = !empty($_POST['empleado_id']) ? (int)$_POST['empleado_id'] : null;

        if (!$id || !$dia || !$inicio || !$fin) {
            $_SESSION['error'] = 'Todos los campos son obligatorios.';
            header('Location: index.php?controller=admin&action=horarios');
            exit;
        }

        if (!$this->horarioModel->perteneceACliente($id, $this->clienteId)) {
            $_SESSION['error'] = 'Acción no permitida.';
            header('Location: index.php?controller=admin&action=horarios');
            exit;
        }

        $this->horarioModel->actualizar($id, $this->clienteId, [
            'empleado_id' => $empleadoId,
            'dia'         => $dia,
            'inicio'      => $inicio,
            'fin'         => $fin,
        ]);

        $_SESSION['success'] = 'Horario actualizado correctamente.';
        header('Location: index.php?controller=admin&action=horarios');
    }

    public function deleteHorario(): void {
        $this->checkSoloAdmin();
        $id = (int)($_GET['id'] ?? 0);

        if (!$this->horarioModel->perteneceACliente($id, $this->clienteId)) {
            $_SESSION['error'] = 'Acción no permitida.';
            header('Location: index.php?controller=admin&action=horarios');
            exit;
        }

        $this->horarioModel->eliminar($id, $this->clienteId);

        $_SESSION['success'] = 'Horario eliminado.';
        header('Location: index.php?controller=admin&action=horarios');
    }

    // =========================================================================
    // CITAS
    // =========================================================================
    public function citas(): void {
        $citas     = $this->citaModel->getByCliente($this->clienteId);
        $servicios = $this->servicioModel->getByCliente($this->clienteId);
        require 'Views/Admin/citas.php';
    }

    public function updateCita(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=admin&action=citas');
            exit;
        }

        $id     = (int)($_POST['id'] ?? 0);
        $estado = trim($_POST['estado'] ?? '');

        $estadosValidos = ['pendiente', 'confirmada', 'cancelada', 'completada'];
        if (!in_array($estado, $estadosValidos)) {
            $_SESSION['error'] = 'Estado inválido.';
            header('Location: index.php?controller=admin&action=citas');
            exit;
        }

        // Verificar ownership antes de actualizar (multitenant)
        if (!$this->citaModel->perteneceACliente($id, $this->clienteId)) {
            $_SESSION['error'] = 'Acción no permitida.';
            header('Location: index.php?controller=admin&action=citas');
            exit;
        }

        $this->citaModel->actualizarEstado($id, $this->clienteId, $estado);

        $_SESSION['success'] = 'Cita actualizada.';
        header('Location: index.php?controller=admin&action=citas');
    }

    public function deleteCita(): void {
        $id = (int)($_GET['id'] ?? 0);

        if (!$this->citaModel->perteneceACliente($id, $this->clienteId)) {
            $_SESSION['error'] = 'Acción no permitida.';
            header('Location: index.php?controller=admin&action=citas');
            exit;
        }

        $this->citaModel->eliminar($id, $this->clienteId);

        $_SESSION['success'] = 'Cita eliminada.';
        header('Location: index.php?controller=admin&action=citas');
    }

    // =========================================================================
    // ACCIÓN RÁPIDA: ACTUALIZAR ESTADO (PARA AJAX O REDIRECCIÓN DIRECTA)
    // =========================================================================
    public function updateStatus(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=admin&action=citas');
            exit;
        }

        $id     = (int)($_POST['id'] ?? 0);
        $estado = trim($_POST['estado'] ?? '');

        $estadosValidos = ['pendiente', 'confirmada', 'cancelada', 'completada', 'no_llego'];
        if (!in_array($estado, $estadosValidos)) {
            $_SESSION['error'] = 'Estado inválido.';
            header('Location: index.php?controller=admin&action=citas');
            exit;
        }

        if (!$this->citaModel->perteneceACliente($id, $this->clienteId)) {
            $_SESSION['error'] = 'Acción no permitida.';
            header('Location: index.php?controller=admin&action=citas');
            exit;
        }

        $this->citaModel->actualizarEstado($id, $this->clienteId, $estado);

        $_SESSION['success'] = 'Cita actualizada correctamente.';

        // Si es una petición AJAX, devolvemos JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true]);
            exit;
        }

        header('Location: index.php?controller=admin&action=citas');
        exit;
    }

    // =========================================================================
    // ASIGNAR EMPLEADOS A SERVICIO
    // =========================================================================
    public function asignarEmpleados(): void {
        $this->checkSoloAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=admin&action=servicios');
            exit;
        }

        $servicioId  = (int)($_POST['servicio_id'] ?? 0);
        $empleadoIds = $_POST['empleado_ids'] ?? [];

        if (!$servicioId) {
            $_SESSION['error'] = 'Servicio inválido.';
            header('Location: index.php?controller=admin&action=servicios');
            exit;
        }

        // Verificar ownership
        if (!$this->servicioModel->perteneceACliente($servicioId, $this->clienteId)) {
            $_SESSION['error'] = 'Acción no permitida.';
            header('Location: index.php?controller=admin&action=servicios');
            exit;
        }

        $this->servicioModel->asignarEmpleados($servicioId, $this->clienteId, array_map('intval', $empleadoIds));

        $_SESSION['success'] = 'Personal asignado correctamente.';
        header('Location: index.php?controller=admin&action=servicios');
        exit;
    }

    // =========================================================================
    // CALENDARIO
    // =========================================================================
    public function calendario(): void {
        require_once 'Models/Empleado.php';
        $empleadoModel  = new Empleado($this->db);
        $empleadosFiltro = $empleadoModel->getByCliente($this->clienteId);
        require 'Views/Admin/calendario.php';
    }

    public function apiEventos(): void {
        header('Content-Type: application/json');
        $start      = $_GET['start']       ?? '';
        $end        = $_GET['end']         ?? '';
        $filtroEmp  = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : null;

        $citas = $this->citaModel->getCitasCalendario($this->clienteId, $start, $end, $filtroEmp);

        $eventos = [];
        foreach ($citas as $c) {
            $colors = [
                'pendiente'  => '#EAB308',
                'confirmada' => $this->negocioActual['color'] ?? '#6366F1',
                'cancelada'  => '#EF4444',
                'completada' => '#22C55E',
                'no_llego'   => '#94A3B8',
            ];

            $titulo = $c['title'];
            if (!empty($c['empleado_nombre'])) {
                $titulo .= ' • ' . $c['empleado_nombre'];
            }

            $eventos[] = [
                'id'    => $c['id'],
                'title' => $titulo . ' (' . ($c['servicio'] ?? 'Sin servicio') . ')',
                'start' => $c['start_date'] . 'T' . $c['start_time'],
                'color' => $colors[$c['estado']] ?? '#6366F1',
                'extendedProps' => [
                    'estado'   => $c['estado'],
                    'servicio' => $c['servicio'],
                    'empleado' => $c['empleado_nombre'] ?? null,
                ]
            ];
        }
        echo json_encode($eventos);
        exit;
    }

    // =========================================================================
    // AJUSTES DE PERFIL / NEGOCIO
    // =========================================================================
    public function ajustes(): void {
        $this->checkSoloAdmin();
        $negocio = $this->negocioActual;
        require 'Views/Admin/ajustes.php';
    }

    public function updateAjustes(): void {
        $this->checkSoloAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=admin&action=ajustes');
            exit;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $slug   = trim($_POST['slug']   ?? '');
        $color  = trim($_POST['color']  ?? '#6366F1');
        
        // Validación básica
        if (!$nombre || !$slug) {
            $_SESSION['error'] = 'Nombre y URL son obligatorios.';
            header('Location: index.php?controller=admin&action=ajustes');
            exit;
        }

        $clienteModel = new Cliente($this->db);
        
        // Verificar si el slug ya existe en OTRO cliente
        $existente = $clienteModel->getBySlug($slug);
        if ($existente && (int)$existente['id'] !== (int)$this->clienteId) {
            $_SESSION['error'] = 'La URL (slug) ya está en uso por otro negocio.';
            header('Location: index.php?controller=admin&action=ajustes');
            exit;
        }

        // Manejo de Logo (si hay)
        $logoPath = $this->negocioActual['logo'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $newName = 'logo_' . $this->clienteId . '_' . time() . '.' . $ext;
            $uploadDir = 'public/uploads/logos/';
            
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $newName)) {
                $logoPath = $uploadDir . $newName;
            }
        }

        // Actualizar en DB
        // Usaremos el modelo Cliente
        $stmt = $this->db->prepare("
            UPDATE clientes 
            SET nombre = ?, slug = ?, color = ?, logo = ? 
            WHERE id = ?
        ");
        $stmt->execute([$nombre, $slug, $color, $logoPath, $this->clienteId]);

        $_SESSION['success'] = 'Configuración guardada correctamente.';
        header('Location: index.php?controller=admin&action=ajustes');
        exit;
    }

    // =========================================================================
    // RECORDATORIOS
    // =========================================================================
    public function recordatorios(): void {
        $manana = date('Y-m-d', strtotime('+1 day'));
        $citas  = $this->citaModel->getByFecha($this->clienteId, $manana);
        
        // Filtrar solo confirmadas o pendientes (no canceladas)
        $citas = array_filter($citas, function($c) {
            return in_array($c['estado'], ['pendiente', 'confirmada']);
        });

        require 'Views/Admin/recordatorios.php';
    }
}
