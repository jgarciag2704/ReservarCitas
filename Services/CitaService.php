<?php
require_once BASE_PATH . 'Models/Cita.php';
require_once BASE_PATH . 'Models/Horario.php';
require_once BASE_PATH . 'Models/Servicio.php';
require_once BASE_PATH . 'Models/Cliente.php';

class CitaService {
    private $db;
    private $citaModel;
    private $horarioModel;
    private $clienteModel;

    public function __construct($db) {
        $this->db = $db;
        $this->citaModel   = new Cita($db);
        $this->horarioModel = new Horario($db);
        $this->clienteModel = new Cliente($db);
    }

    /**
     * Procesa la creación de una cita y devuelve [status, message].
     * Utiliza transacciones para garantizar la atomicidad y evitar dobles reservas.
     */
    public function procesarReserva(int $cliente_id, array $data): array {
        if (empty($data['nombre']) || empty($data['telefono']) || empty($data['fecha']) || empty($data['hora'])) {
            return ['status' => false, 'message' => 'Todos los campos son obligatorios.'];
        }

        // Validación de Nombre (solo letras y espacios)
        if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $data['nombre'])) {
            return ['status' => false, 'message' => 'El nombre solo debe contener letras.'];
        }

        // Validación de Teléfono (LADA + 10 números)
        // Permite un "+" opcional seguido de 11 a 14 dígitos totales (LADA + número)
        if (!preg_match("/^\+?\d{11,14}$/", $data['telefono'])) {
            return ['status' => false, 'message' => 'El teléfono es inválido. Recuerda que debe tener la LADA y los 10 dígitos.'];
        }

        $empleadoId = !empty($data['empleado_id']) ? (int)$data['empleado_id'] : null;
        $fecha      = trim($data['fecha']);
        $hora       = trim($data['hora']);
        $personas   = isset($data['personas']) ? (int)$data['personas'] : 1;

        $cliente = $this->clienteModel->find($cliente_id);
        $tipo_reserva = $cliente['tipo_reserva'] ?? 'individual';
        $cantidad_mesas = (int)($cliente['cantidad_mesas'] ?? 1);
        $sillas_por_mesa = (int)($cliente['sillas_por_mesa'] ?? 4);
        $mesas_requeridas = (int)ceil($personas / ($sillas_por_mesa ?: 1));

        try {
            $this->db->beginTransaction();

            $this->limpiarBloqueosExpirados();

            if ($tipo_reserva === 'capacidad') {
                // Validación para capacidad (Restaurante/Mesas)
                $porcentaje = isset($data['es_admin_walkin']) ? 100 : ($cliente['porcentaje_online'] / 100);
                $max_mesas_online = (int)($cantidad_mesas * $porcentaje);
                
                $mesas_ocupadas_actuales = $this->citaModel->getMesasOcupadasEnHora($cliente_id, $fecha, $hora);
                
                if (($mesas_ocupadas_actuales + $mesas_requeridas) > $max_mesas_online) {
                    $this->db->rollBack();
                    return ['status' => false, 'message' => 'Lo sentimos, no hay suficientes lugares o mesas disponibles para esta hora.'];
                }

                // Asignar null si el empleado no es obligatorio
                $empleadoId = null;

            } else {
                // Lógica de validación individual
                if ($empleadoId !== null && !$this->empleadoPerteneceACliente($empleadoId, $cliente_id)) {
                    $this->db->rollBack();
                    return ['status' => false, 'message' => 'Empleado no válido para este negocio.'];
                }

                if ($empleadoId === null && !empty($data['servicio_id'])) {
                    $servicioModel = new Servicio($this->db);
                    $empleadosAsignados = $servicioModel->getEmpleadosPorServicio((int)$data['servicio_id'], $cliente_id);
                    
                    if (!empty($empleadosAsignados)) {
                        $disponibles = [];
                        foreach ($empleadosAsignados as $emp) {
                            if ($this->estaLibre($cliente_id, $fecha, $hora, (int)$emp['id'], true)) {
                                $disponibles[] = (int)$emp['id'];
                            }
                        }

                        if (empty($disponibles)) {
                            $this->db->rollBack();
                            return ['status' => false, 'message' => 'No hay especialistas disponibles para este horario.'];
                        }

                        $conteos = $this->citaModel->getConteoCitasDiaPorEmpleado($cliente_id, $fecha, $disponibles);
                        asort($conteos); 
                        $empleadoId = key($conteos); 
                    }
                }

                if (!$this->estaLibre($cliente_id, $fecha, $hora, $empleadoId, true)) {
                    $this->db->rollBack();
                    return ['status' => false, 'message' => 'El horario ya no está disponible. Por favor, elige otro.'];
                }
            }

            // Crear la Cita
            $estadoInicial = isset($data['es_admin_walkin']) ? 'en_curso' : 'pendiente';
            $ok = $this->citaModel->crear([
                'cliente_id'        => $cliente_id,
                'servicio_id'       => !empty($data['servicio_id']) ? (int)$data['servicio_id'] : null,
                'empleado_id'       => $empleadoId,
                'nombre'            => trim($data['nombre']),
                'telefono'          => trim($data['telefono']),
                'fecha'             => $fecha,
                'hora'              => $hora,
                'cantidad_personas' => $personas,
                'mesas_ocupadas'    => $tipo_reserva === 'capacidad' ? $mesas_requeridas : 1,
                'estado'            => $estadoInicial
            ]);

            if ($ok) {
                // 5. Liberar el bloqueo temporal si existía
                $this->liberarBloqueo($cliente_id, $fecha, $hora, $empleadoId);
                $this->db->commit();

                $fechaLegible = $this->formatearFechaEspanol($fecha);
                $horaLegible  = $this->formatearHoraEspanol($hora);

                return [
                    'status' => true, 
                    'message' => "¡Cita confirmada! Te esperamos el <b>{$fechaLegible}</b> a las <b>{$horaLegible}</b>."
                ];
            }

            $this->db->rollBack();
            return ['status' => false, 'message' => 'Error al procesar la reserva. Inténtalo de nuevo.'];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    // =========================================================================
    // BLOQUEOS TEMPORALES (SOFT LOCKS)
    // =========================================================================

    public function bloquearHorarioTemporal(int $cliente_id, string $fecha, string $hora, ?int $empleadoId = null): bool {
        $ahora = date('Y-m-d H:i:s');
        $this->limpiarBloqueosExpirados($ahora);

        // No bloquear si ya hay una cita o un bloqueo activo
        if (!$this->estaLibre($cliente_id, $fecha, $hora, $empleadoId)) {
            return false;
        }

        try {
            // Calcular expiración en PHP (5 minutos a partir de ahora)
            $expiracion = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            $stmt = $this->db->prepare("
                INSERT INTO bloqueos_citas (cliente_id, empleado_id, fecha, hora, expira_en)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$cliente_id, $empleadoId, $fecha, $hora, $expiracion]);
        } catch (PDOException $e) {
            // Error 23000 = Duplicate key (otro bloqueo entró justo antes)
            if ($e->getCode() == 23000) return false;
            throw $e;
        }
    }

    /**
     * Verifica si un slot está libre (sin Citas activas Y sin Bloqueos activos).
     * @param bool $ignorarMio (Opcional) Si estamos confirmando la cita, el bloqueo actual no debería impedirnos.
     */
    private function estaLibre(int $clienteId, string $fecha, string $hora, ?int $empleadoId, bool $ignorarBloqueoMio = false): bool {
        // 1. Verificar Citas
        $ocupados = $this->citaModel->getEmpleadosOcupadosEnHora($clienteId, $fecha, $hora);
        if ($empleadoId !== null) {
            if (in_array((string)$empleadoId, array_map('strval', $ocupados))) return false;
        } else {
            // Si el servicio no tiene empleado, cualquier cita en ese slot lo bloquea (lógica anterior)
            if (!empty($ocupados)) {
                // Si hay citas globales o de cualquier empleado, consideramos ocupado para servicios sin asignación específica
                // EDIT: Depende de la lógica de negocio, pero usualmente si no hay empleado, chequeamos getHorasOcupadas.
                $citasGral = $this->citaModel->getHorasOcupadas($clienteId, $fecha);
                if (in_array(date('H:i', strtotime($hora)), $citasGral)) return false;
            }
        }

        // 2. Verificar Bloqueos
        if (!$ignorarBloqueoMio) {
            if ($this->estaBloqueado($clienteId, $fecha, $hora, $empleadoId)) return false;
        }

        return true;
    }

    /**
     * Verificar si una hora está bajo bloqueo temporal.
     */
    private function estaBloqueado(int $cliente_id, string $fecha, string $hora, ?int $empleadoId = null): bool {
        $ahora = date('Y-m-d H:i:s');
        $sql = "SELECT COUNT(*) FROM bloqueos_citas 
                WHERE cliente_id = ? AND fecha = ? AND hora = ? AND expira_en > ?";
        $params = [$cliente_id, $fecha, $hora, $ahora];

        if ($empleadoId !== null) {
            $sql .= " AND empleado_id = ?";
            $params[] = $empleadoId;
        } else {
            $sql .= " AND empleado_id IS NULL";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function getHorasBloqueadas(int $cliente_id, string $fecha): array {
        $ahora = date('Y-m-d H:i:s');
        $this->limpiarBloqueosExpirados($ahora);
        $stmt = $this->db->prepare("
            SELECT DISTINCT TIME_FORMAT(hora, '%H:%i') FROM bloqueos_citas
            WHERE cliente_id = ? AND fecha = ? AND expira_en > ?
        ");
        $stmt->execute([$cliente_id, $fecha, $ahora]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Retorna IDs de empleados con bloqueo temporal activo en una hora.
     */
    public function getEmpleadosBloqueadosEnHora(int $cliente_id, string $fecha, string $hora): array {
        $ahora = date('Y-m-d H:i:s');
        $this->limpiarBloqueosExpirados($ahora);
        $stmt = $this->db->prepare("
            SELECT empleado_id FROM bloqueos_citas
            WHERE cliente_id = ? AND fecha = ? AND hora = ? AND expira_en > ?
              AND empleado_id IS NOT NULL
        ");
        $stmt->execute([$cliente_id, $fecha, $hora, $ahora]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function liberarBloqueo(int $cliente_id, string $fecha, string $hora, ?int $empleadoId = null): void {
        $sql = "DELETE FROM bloqueos_citas WHERE cliente_id = ? AND fecha = ? AND hora = ?";
        $params = [$cliente_id, $fecha, $hora];

        if ($empleadoId !== null) {
            $sql .= " AND empleado_id = ?";
            $params[] = $empleadoId;
        } else {
            $sql .= " AND empleado_id IS NULL";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    private function limpiarBloqueosExpirados(?string $ahora = null): void {
        $ahora = $ahora ?? date('Y-m-d H:i:s');
        $this->db->prepare("DELETE FROM bloqueos_citas WHERE expira_en <= ?")->execute([$ahora]);
    }

    /**
     * Revisa citas pendientes que ya superaron su tiempo de gracia y las marca como no_show
     */
    public function ejecutarAutoNoShow(int $clienteId): void {
        $cliente = $this->clienteModel->find($clienteId);
        $tiempo_gracia = (int)($cliente['tiempo_gracia'] ?? 15);
        $ahora = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            UPDATE citas 
            SET estado = 'no_llego'
            WHERE cliente_id = ? AND estado = 'pendiente'
              AND STR_TO_DATE(CONCAT(fecha, ' ', hora), '%Y-%m-%d %H:%i:%s') < DATE_SUB(?, INTERVAL ? MINUTE)
        ");
        $stmt->execute([$clienteId, $ahora, $tiempo_gracia]);

        // --- RUTINA DE REPARACIÓN ---
        // Si detectamos citas de hoy marcadas como 'no_llego' pero cuya hora es FUTURA respecto al PHP, las devolvemos a 'pendiente'.
        $hoy = date('Y-m-d');
        $hora_actual = date('H:i:s');
        $stmtFix = $this->db->prepare("
            UPDATE citas 
            SET estado = 'pendiente'
            WHERE cliente_id = ? AND fecha = ? AND hora > ? AND estado = 'no_llego'
        ");
        $stmtFix->execute([$clienteId, $hoy, $hora_actual]);
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    private function empleadoPerteneceACliente(int $empleadoId, int $clienteId): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM usuarios
            WHERE id = ? AND cliente_id = ? AND rol = 'empleado' AND activo = 1
        ");
        $stmt->execute([$empleadoId, $clienteId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function formatearFechaEspanol(string $fecha): string {
        $timestamp = strtotime($fecha);
        $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        
        $diaSemana = $dias[date('w', $timestamp)];
        $diaNum    = date('j', $timestamp);
        $mesNum    = (int)date('n', $timestamp);
        $anio      = date('Y', $timestamp);

        return "{$diaSemana}, {$diaNum} de {$meses[$mesNum]} de {$anio}";
    }

    private function formatearHoraEspanol(string $hora): string {
        $timestamp = strtotime($hora);
        $h = (int)date('G', $timestamp);
        $m = date('i', $timestamp);
        
        $h12  = ($h > 12) ? $h - 12 : ($h == 0 ? 12 : $h);
        
        $periodo = '';
        if ($h >= 6 && $h < 12) $periodo = " de la mañana";
        else if ($h >= 12 && $h < 19) $periodo = " de la tarde";
        else if ($h >= 19 || $h < 6) $periodo = " de la noche";

        return "{$h12}:{$m}{$periodo}";
    }
}
