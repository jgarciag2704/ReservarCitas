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
            die('No se especificó el negocio. Usa: ?controller=cliente&action=index&slug=tu-negocio');
        }

        $cliente = $this->clienteModel->getBySlug($slug);

        if (!$cliente) {
            http_response_code(404);
            die('Negocio no encontrado.');
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

        // Soporte para peticiones AJAX (JSON)
        if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-Type: application/json');
            echo json_encode($res);
            exit;
        }

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
        // Desactivar visualización de errores para que no ensucien el JSON
        error_reporting(0);
        ini_set('display_errors', 0);
        
        // Iniciar captura de buffer y limpiar cualquier residuo
        ob_start();
        if (ob_get_length()) ob_clean();

        header('Content-Type: application/json');

        try {
            $cliente_id  = (int)($_GET['cliente_id']  ?? 0);
            $servicio_id = (int)($_GET['servicio_id'] ?? 0);
            $fecha       = trim($_GET['fecha']         ?? '');

            if (!$cliente_id || !$fecha) {
                echo json_encode([]);
                exit;
            }

            // Día de la semana en español
            $diasEs    = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
            $timestamp = strtotime($fecha);
            if (!$timestamp) { echo json_encode([]); exit; }
            $diaSemana = $diasEs[(int)date('w', $timestamp)];

            $cliente = $this->clienteModel->find($cliente_id);
            if (!$cliente) {
                echo json_encode(['error' => 'Negocio no encontrado']);
                exit;
            }

            $tipo_reserva   = $cliente['tipo_reserva']   ?? 'individual';
            $cantidad_mesas = (int)($cliente['cantidad_mesas'] ?? 1);
            $sillas_por_mesa = (int)($cliente['sillas_por_mesa'] ?? 4);
            $max_mesas_online = (int)($cantidad_mesas * (($cliente['porcentaje_online'] ?? 100) / 100));

            // Empleados asignados al servicio
            $empleadosAsignados = [];
            if ($servicio_id > 0) {
                $empleadosAsignados = $this->servicioModel->getEmpleadosPorServicio($servicio_id, $cliente_id);
            }
            $empleadoIds = array_column($empleadosAsignados, 'id');

            // --- OPTIMIZACIÓN: Pre-cargar todas las citas y bloqueos del día ---
            $citasDia = $this->db->prepare("
                SELECT c.hora, c.empleado_id, c.mesas_ocupadas, COALESCE(s.duracion, 30) as duracion
                FROM citas c
                LEFT JOIN servicios s ON s.id = c.servicio_id
                WHERE c.cliente_id = ? AND c.fecha = ? AND c.estado NOT IN ('cancelada', 'no_llego', 'finalizada')
            ");
            $citasDia->execute([$cliente_id, $fecha]);
            $allCitas = $citasDia->fetchAll(PDO::FETCH_ASSOC);

            $ahora = date('Y-m-d H:i:s');
            $bloqueosDia = $this->db->prepare("
                SELECT hora, empleado_id FROM bloqueos_citas
                WHERE cliente_id = ? AND fecha = ? AND expira_en > ?
            ");
            $bloqueosDia->execute([$cliente_id, $fecha, $ahora]);
            $allBloqueos = $bloqueosDia->fetchAll(PDO::FETCH_ASSOC);

            $disponibles = [];
            $horaFiltroHoy = ($fecha === date('Y-m-d')) ? date('H:i') : null;

            if ($tipo_reserva === 'capacidad') {
                // Lógica de Restaurante (Capacidad)
                $horariosGral = $this->horarioModel->getByDia($cliente_id, $diaSemana, null);
                if (empty($horariosGral)) { echo json_encode([]); exit; }

                $personas = isset($_GET['personas']) ? (int)$_GET['personas'] : 1;
                $mesas_requeridas = (int)ceil($personas / ($sillas_por_mesa ?: 1));

                foreach ($horariosGral as $h) {
                    $inicio = strtotime($h['hora_inicio']);
                    $fin    = strtotime($h['hora_fin']);
                    for ($t = $inicio; $t < $fin; $t += 1800) {
                        $hora = date('H:i', $t);
                        if ($horaFiltroHoy !== null && $hora <= $horaFiltroHoy) continue;
                        
                        // Calcular ocupación en memoria
                        $ocupadas = 0;
                        foreach ($allCitas as $c) {
                            $c_inicio = strtotime($c['hora']);
                            $c_fin = $c_inicio + ($c['duracion'] * 60);
                            if ($t >= $c_inicio && $t < $c_fin) {
                                $ocupadas += (int)$c['mesas_ocupadas'];
                            }
                        }
                        
                        if (($ocupadas + $mesas_requeridas) <= $max_mesas_online) {
                            $disponibles[] = $hora;
                        }
                    }
                }
            } else {
                // Modo Individual (Por especialista o general)
                $horariosPorEmpleado = [];
                $usarGeneral = empty($empleadoIds);
                
                if (!$usarGeneral) {
                    foreach ($empleadoIds as $empId) {
                        $horariosPorEmpleado[$empId] = $this->horarioModel->getByDia($cliente_id, $diaSemana, (int)$empId);
                    }
                } else {
                    $horariosPorEmpleado[0] = $this->horarioModel->getByDia($cliente_id, $diaSemana, null);
                }

                for ($t = strtotime('00:00'); $t <= strtotime('23:30'); $t += 1800) {
                    $hora = date('H:i', $t);
                    if ($horaFiltroHoy !== null && $hora <= $horaFiltroHoy) continue;
                    
                    $disponibleEnSlot = false;
                    foreach ($horariosPorEmpleado as $empId => $franjas) {
                        foreach ($franjas as $f) {
                            $h_inicio = strtotime($f['hora_inicio']);
                            $h_fin = strtotime($f['hora_fin']);
                            
                            if ($t >= $h_inicio && $t < $h_fin) {
                                $ocupado = false;
                                foreach ($allCitas as $c) {
                                    if ($usarGeneral || (int)$c['empleado_id'] === (int)$empId) {
                                        $c_inicio = strtotime($c['hora']);
                                        $c_fin = $c_inicio + ($c['duracion'] * 60);
                                        if ($t >= $c_inicio && $t < $c_fin) {
                                            $ocupado = true; break;
                                        }
                                    }
                                }
                                if ($ocupado) continue;

                                foreach ($allBloqueos as $b) {
                                    if ($usarGeneral || (int)$b['empleado_id'] === (int)$empId) {
                                        if (date('H:i', strtotime($b['hora'])) === $hora) {
                                            $ocupado = true; break;
                                        }
                                    }
                                }
                                
                                if (!$ocupado) {
                                    $disponibleEnSlot = true;
                                    break 2;
                                }
                            }
                        }
                    }
                    if ($disponibleEnSlot) $disponibles[] = $hora;
                }
            }

            // Eliminar duplicados y ordenar
            $disponibles = array_values(array_unique($disponibles));
            sort($disponibles);

            echo json_encode($disponibles);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error'   => true,
                'message' => $e->getMessage(),
                'file'    => basename($e->getFile()),
                'line'    => $e->getLine()
            ]);
            exit;
        }
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