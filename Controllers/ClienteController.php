<?php
require_once BASE_PATH . 'Models/Cliente.php';
require_once BASE_PATH . 'Models/Servicio.php';
require_once BASE_PATH . 'Models/Horario.php';
require_once BASE_PATH . 'Models/Cita.php';

class ClienteController {

    private $db;
    private $clienteModel;
    private $servicioModel;
    private $horarioModel;
    private $citaModel;

    public function __construct($db) {
        $this->db            = $db;
        $this->clienteModel  = new Cliente($db);
        $this->servicioModel = new Servicio($db);
        $this->horarioModel  = new Horario($db);
        $this->citaModel     = new Cita($db);
    }

    // =========================================================================
    // PÁGINA PÚBLICA: servicios del negocio + formulario de reserva
    // URL: index.php?controller=cliente&action=index&slug=peluqueria-juan
    // =========================================================================
    public function index(): void {
        $slug = trim($_GET['slug'] ?? '');

        if (!$slug) {
            http_response_code(400);
            die('❌ No se especificó el negocio. Usa: ?controller=cliente&action=index&slug=tu-negocio');
        }

        $cliente = $this->clienteModel->getBySlug($slug);

        if (!$cliente) {
            http_response_code(404);
            die('❌ Negocio no encontrado.');
        }

        $servicios = $this->servicioModel->getByCliente($cliente['id']);
        $success   = $_SESSION['booking_success'] ?? null;
        $error     = $_SESSION['booking_error']   ?? null;
        $whatsapp_text = $_SESSION['booking_whatsapp'] ?? null;
        unset($_SESSION['booking_success'], $_SESSION['booking_error'], $_SESSION['booking_whatsapp']);

        require BASE_PATH . 'Views/Cliente/reservar.php';
    }

    // =========================================================================
    // PROCESAR RESERVA
    // POST: index.php?controller=cliente&action=agendar
    // =========================================================================
    public function agendar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=cliente&action=index&slug=' . ($_POST['slug'] ?? ''));
            exit;
        }

        $slug = trim($_POST['slug'] ?? '');

        require_once BASE_PATH . 'Services/CitaService.php';
        $citaService = new CitaService($this->db);

        $cliente = $this->clienteModel->getBySlug($slug);
        if (!$cliente) {
            http_response_code(403);
            die('Negocio inválido o no encontrado.');
        }

        $res = $citaService->procesarReserva((int)$cliente['id'], $_POST);

        if ($res['status']) {
            $_SESSION['booking_success'] = $res['message'];

            $sName  = trim($_POST['servicio_nombre'] ?? 'un servicio');
            $fecha  = trim($_POST['fecha'] ?? '');
            $hora   = trim($_POST['hora']  ?? '');
            $fFecha = $fecha ? date('d/m/Y', strtotime($fecha)) : '';
            $_SESSION['booking_whatsapp'] = "¡Hola! Acabo de agendar una cita para *{$sName}* el día {$fFecha} a las {$hora}.";

        } else {
            $_SESSION['booking_error'] = $res['message'];
        }

        header("Location: index.php?controller=cliente&action=index&slug=$slug");
        exit;
    }

    // =========================================================================
    // API JSON: horas disponibles para una fecha + servicio
    // Opción B: un slot está disponible si AL MENOS UN empleado asignado está libre
    // GET: ?controller=cliente&action=horasDisponibles&cliente_id=X&servicio_id=Y&fecha=YYYY-MM-DD
    // =========================================================================
    public function horasDisponibles(): void {
        header('Content-Type: application/json');

        $cliente_id  = (int)($_GET['cliente_id']  ?? 0);
        $servicio_id = (int)($_GET['servicio_id'] ?? 0);
        $fecha       = trim($_GET['fecha']         ?? '');

        if (!$cliente_id || !$fecha) {
            echo json_encode([]);
            exit;
        }

        // Día de la semana en español
        $diasEs    = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        $diaSemana = $diasEs[(int)date('w', strtotime($fecha))];

        $horarios = $this->horarioModel->getByDia($cliente_id, $diaSemana);
        if (empty($horarios)) {
            echo json_encode([]);
            exit;
        }

        // Empleados asignados al servicio
        $empleadosAsignados = [];
        if ($servicio_id > 0) {
            $empleadosAsignados = $this->servicioModel->getEmpleadosPorServicio($servicio_id, $cliente_id);
        }
        $empleadoIds = array_column($empleadosAsignados, 'id');

        require_once BASE_PATH . 'Services/CitaService.php';
        $citaService = new CitaService($this->db);

        $disponibles = [];

        if (empty($empleadoIds)) {
            // Caso A: Sin empleados asignados → Usar Horario General del Negocio
            $horariosGral = $this->horarioModel->getByDia($cliente_id, $diaSemana, null);
            $citasOcupadas     = $this->citaModel->getHorasOcupadas($cliente_id, $fecha);
            $bloqueadasLocales = $citaService->getHorasBloqueadas($cliente_id, $fecha);

            foreach ($horariosGral as $h) {
                $inicio = strtotime($h['hora_inicio']);
                $fin    = strtotime($h['hora_fin']);
                for ($t = $inicio; $t < $fin; $t += 1800) {
                    $hora = date('H:i', $t);
                    if (!in_array($hora, $citasOcupadas) && !in_array($hora, $bloqueadasLocales)) {
                        $disponibles[] = $hora;
                    }
                }
            }
        } else {
            // Caso B: Con empleados → El slot es válido si AL MENOS 1 empleado está en SU horario y LIBRE
            
            // 1. Pre-cargar todos los horarios de los empleados asignados para evitar queries en el loop
            $horariosPorEmpleado = [];
            foreach ($empleadoIds as $empId) {
                $horariosPorEmpleado[$empId] = $this->horarioModel->getByDia($cliente_id, $diaSemana, (int)$empId);
            }

            // 2. Pre-cargar ocupaciones (citas y bloqueos) para optimizar
            // Podríamos hacerlo por slot, pero al menos ya ahorramos los horarios.
            
            for ($t = strtotime('00:00'); $t <= strtotime('23:30'); $t += 1800) {
                $hora = date('H:i', $t);
                $horaHms = date('H:i:s', $t);

                foreach ($empleadoIds as $empId) {
                    $hEmp = $horariosPorEmpleado[$empId] ?? [];
                    
                    $estaEnHorario = false;
                    foreach ($hEmp as $rango) {
                        if ($horaHms >= $rango['hora_inicio'] && $horaHms < $rango['hora_fin']) {
                            $estaEnHorario = true;
                            break;
                        }
                    }

                    if ($estaEnHorario) {
                        $ocupados   = $this->citaModel->getEmpleadosOcupadosEnHora($cliente_id, $fecha, $hora);
                        $bloqueados = $citaService->getEmpleadosBloqueadosEnHora($cliente_id, $fecha, $hora);
                        
                        if (!in_array($empId, $ocupados) && !in_array($empId, $bloqueados)) {
                            $disponibles[] = $hora;
                            break;
                        }
                    }
                }
            }
        }

        // Eliminar duplicados y ordenar
        $disponibles = array_values(array_unique($disponibles));
        sort($disponibles);

        echo json_encode($disponibles);
        exit;
    }

    // =========================================================================
    // API JSON: empleados disponibles para un servicio en fecha+hora específica
    // Opción B: llamado DESPUÉS de que el cliente elige la hora
    // GET: ?controller=cliente&action=empleadosDisponibles&cliente_id=X&servicio_id=Y&fecha=Z&hora=W
    // =========================================================================
    public function empleadosDisponibles(): void {
        header('Content-Type: application/json');

        $cliente_id  = (int)($_GET['cliente_id']  ?? 0);
        $servicio_id = (int)($_GET['servicio_id'] ?? 0);
        $fecha       = trim($_GET['fecha']         ?? '');
        $hora        = trim($_GET['hora']          ?? '');

        if (!$cliente_id || !$servicio_id || !$fecha || !$hora) {
            echo json_encode(['empleados' => [], 'auto_assign' => false]);
            exit;
        }

        $empleados = $this->servicioModel->getEmpleadosPorServicio($servicio_id, $cliente_id);

        if (empty($empleados)) {
            // Sin empleados asignados al servicio → no hay que elegir
            echo json_encode(['empleados' => [], 'auto_assign' => false, 'sin_asignacion' => true]);
            exit;
        }

        $empleadoIds = array_column($empleados, 'id');

        require_once BASE_PATH . 'Services/CitaService.php';
        $citaService = new CitaService($this->db);

        $ocupados   = array_map('intval', $this->citaModel->getEmpleadosOcupadosEnHora($cliente_id, $fecha, $hora));
        $bloqueados = array_map('intval', $citaService->getEmpleadosBloqueadosEnHora($cliente_id, $fecha, $hora));
        $noDisponibles = array_unique(array_merge($ocupados, $bloqueados));

        $disponibles = array_values(array_filter($empleados, fn($e) => !in_array((int)$e['id'], $noDisponibles)));

        // Solo auto-asignar si el servicio TIENE únicamente 1 empleado en total.
        // Si tiene más de uno, activamos el picker para que el usuario elija o use "Cualquiera",
        // incluso si en ese horario solo uno está libre (esto da más claridad al usuario).
        $autoAssign = (count($empleados) === 1);

        echo json_encode([
            'empleados'   => $disponibles,
            'auto_assign' => $autoAssign,
        ]);
        exit;
    }

    // =========================================================================
    // API JSON: Bloquea temporalmente un horario
    // POST: index.php?controller=cliente&action=bloquearHora
    // =========================================================================
    public function bloquearHora(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false]);
            exit;
        }

        $input      = json_decode(file_get_contents('php://input'), true);
        $cliente_id = (int)($input['cliente_id'] ?? 0);
        $fecha      = trim($input['fecha']        ?? '');
        $hora       = trim($input['hora']         ?? '');
        $empleado_id = isset($input['empleado_id']) ? (int)$input['empleado_id'] : null;

        if (!$cliente_id || !$fecha || !$hora) {
            echo json_encode(['success' => false, 'error' => 'Datos faltantes']);
            exit;
        }

        require_once 'Services/CitaService.php';
        $citaService = new CitaService($this->db);

        $ok = $citaService->bloquearHorarioTemporal($cliente_id, $fecha, $hora, $empleado_id);

        echo json_encode(['success' => $ok]);
        exit;
    }
}