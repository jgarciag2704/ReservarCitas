<?php
require_once BASE_PATH . 'Models/Cita.php';
require_once BASE_PATH . 'Models/Horario.php';
require_once BASE_PATH . 'Models/Servicio.php';

class CitaService {
    private $db;
    private $citaModel;
    private $horarioModel;

    public function __construct($db) {
        $this->db = $db;
        $this->citaModel   = new Cita($db);
        $this->horarioModel = new Horario($db);
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
            return ['status' => false, 'message' => '⚠️ El nombre solo debe contener letras.'];
        }

        // Validación de Teléfono (solo números)
        if (!preg_match("/^\d+$/", $data['telefono'])) {
            return ['status' => false, 'message' => '⚠️ El teléfono solo debe contener números.'];
        }

        $empleadoId = !empty($data['empleado_id']) ? (int)$data['empleado_id'] : null;
        $fecha      = trim($data['fecha']);
        $hora       = trim($data['hora']);

        try {
            $this->db->beginTransaction();

            // 1. Validar pertenencia del empleado
            if ($empleadoId !== null && !$this->empleadoPerteneceACliente($empleadoId, $cliente_id)) {
                $this->db->rollBack();
                return ['status' => false, 'message' => '⚠️ Empleado no válido para este negocio.'];
            }

            // 2. Limpiar bloqueos expirados para tener visión real
            $this->limpiarBloqueosExpirados();

            // 3. Si no se eligió empleado (Cualquiera), buscar el disponible con menos carga
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
                        return ['status' => false, 'message' => '⚠️ No hay especialistas disponibles para este horario.'];
                    }

                    // Balance de carga: contar citas del día para los disponibles
                    $conteos = $this->citaModel->getConteoCitasDiaPorEmpleado($cliente_id, $fecha, $disponibles);
                    asort($conteos); // Ordenar de menos a más citas
                    $empleadoId = key($conteos); // El ID del primero (menos cargado)
                }
            }

            // 4. Verificación Atómica de Disponibilidad Final
            if (!$this->estaLibre($cliente_id, $fecha, $hora, $empleadoId, true)) {
                $this->db->rollBack();
                return ['status' => false, 'message' => '⚠️ El horario ya no está disponible. Por favor, elige otro.'];
            }

            // 4. Crear la Cita
            $ok = $this->citaModel->crear([
                'cliente_id'  => $cliente_id,
                'servicio_id' => !empty($data['servicio_id']) ? (int)$data['servicio_id'] : null,
                'empleado_id' => $empleadoId,
                'nombre'      => trim($data['nombre']),
                'telefono'    => trim($data['telefono']),
                'fecha'       => $fecha,
                'hora'        => $hora,
            ]);

            if ($ok) {
                // 5. Liberar el bloqueo temporal si existía
                $this->liberarBloqueo($cliente_id, $fecha, $hora, $empleadoId);
                $this->db->commit();

                $fechaLegible = $this->formatearFechaEspanol($fecha);
                $horaLegible  = $this->formatearHoraEspanol($hora);

                return [
                    'status' => true, 
                    'message' => "✅ ¡Cita confirmada! Te esperamos el **{$fechaLegible}** a las **{$horaLegible}**."
                ];
            }

            $this->db->rollBack();
            return ['status' => false, 'message' => '⚠️ Error al procesar la reserva. Inténtalo de nuevo.'];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    // =========================================================================
    // BLOQUEOS TEMPORALES (SOFT LOCKS)
    // =========================================================================

    public function bloquearHorarioTemporal(int $cliente_id, string $fecha, string $hora, ?int $empleadoId = null): bool {
        $this->limpiarBloqueosExpirados();

        // No bloquear si ya hay una cita o un bloqueo activo
        if (!$this->estaLibre($cliente_id, $fecha, $hora, $empleadoId)) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO bloqueos_citas (cliente_id, empleado_id, fecha, hora, expira_en)
                VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))
            ");
            return $stmt->execute([$cliente_id, $empleadoId, $fecha, $hora]);
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
        $sql = "SELECT COUNT(*) FROM bloqueos_citas 
                WHERE cliente_id = ? AND fecha = ? AND hora = ? AND expira_en > NOW()";
        $params = [$cliente_id, $fecha, $hora];

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
        $this->limpiarBloqueosExpirados();
        $stmt = $this->db->prepare("
            SELECT DISTINCT TIME_FORMAT(hora, '%H:%i') FROM bloqueos_citas
            WHERE cliente_id = ? AND fecha = ? AND expira_en > NOW()
        ");
        $stmt->execute([$cliente_id, $fecha]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Retorna IDs de empleados con bloqueo temporal activo en una hora.
     */
    public function getEmpleadosBloqueadosEnHora(int $cliente_id, string $fecha, string $hora): array {
        $this->limpiarBloqueosExpirados();
        $stmt = $this->db->prepare("
            SELECT empleado_id FROM bloqueos_citas
            WHERE cliente_id = ? AND fecha = ? AND hora = ? AND expira_en > NOW()
              AND empleado_id IS NOT NULL
        ");
        $stmt->execute([$cliente_id, $fecha, $hora]);
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

    private function limpiarBloqueosExpirados(): void {
        $this->db->prepare("DELETE FROM bloqueos_citas WHERE expira_en <= NOW()")->execute();
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
