<?php
require_once BASE_PATH . 'Models/BaseModel.php';

class Cita extends BaseModel {

    public function __construct($db = null) {
        if ($db !== null) {
            $this->db = $db;
        } else {
            parent::__construct();
        }
    }

    // ── Lectura ───────────────────────────────────────────────────────────────

    public function getByCliente(int $cliente_id): array {
        $stmt = $this->db->prepare(
            'SELECT c.*, s.nombre AS servicio_nombre,
                    u.nombre AS empleado_nombre
             FROM citas c
             LEFT JOIN servicios s ON s.id = c.servicio_id
             LEFT JOIN usuarios  u ON u.id = c.empleado_id
             WHERE c.cliente_id = ?
             ORDER BY c.fecha DESC, c.hora DESC'
        );
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Citas de un día específico (para el dashboard).
     */
    public function getByFecha(int $cliente_id, string $fecha): array {
        $stmt = $this->db->prepare(
            'SELECT c.*, s.nombre AS servicio_nombre
             FROM citas c
             LEFT JOIN servicios s ON s.id = c.servicio_id
             WHERE c.cliente_id = ? AND c.fecha = ?
             ORDER BY c.hora ASC'
        );
        $stmt->execute([$cliente_id, $fecha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica ownership de la cita (multitenant).
     */
    public function perteneceACliente(int $id, int $cliente_id): bool {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM citas WHERE id = ? AND cliente_id = ?'
        );
        $stmt->execute([$id, $cliente_id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    public function crear(array $data): bool {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO citas (cliente_id, servicio_id, empleado_id, nombre_cliente, telefono, fecha, hora, cantidad_personas, mesas_ocupadas, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            return $stmt->execute([
                $data['cliente_id'],
                $data['servicio_id'],
                $data['empleado_id'] ?? null,
                $data['nombre'],
                $data['telefono'],
                $data['fecha'],
                $data['hora'],
                $data['cantidad_personas'] ?? 1,
                $data['mesas_ocupadas'] ?? 1,
                $data['estado'] ?? 'pendiente'
            ]);
        } catch (PDOException $e) {
            // Log the error so the user can easily debug if it returns false
            error_log("DB_ERROR_CITA: " . $e->getMessage());
            if ($e->getCode() == 23000) {
                // Throw an exception so we can catch it in the service and see exactly what failed
                throw new Exception("Error de SQL: " . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Cambia el estado de una cita (solo si pertenece al cliente).
     */
    public function actualizarEstado(int $id, int $cliente_id, string $estado): bool {
        $stmt = $this->db->prepare(
            'UPDATE citas SET estado = :estado
             WHERE id = :id AND cliente_id = :cliente_id'
        );
        return $stmt->execute([
            ':estado'     => $estado,
            ':id'         => $id,
            ':cliente_id' => $cliente_id,
        ]);
    }

    public function eliminar(int $id, int $cliente_id): bool {
        $stmt = $this->db->prepare(
            'DELETE FROM citas WHERE id = ? AND cliente_id = ?'
        );
        return $stmt->execute([$id, $cliente_id]);
    }

    /**
     * Devuelve las horas ya ocupadas (formato H:i) en una fecha para un negocio.
     * Usada por el API de disponibilidad del módulo público.
     */
    public function getHorasOcupadas(int $cliente_id, string $fecha): array {
        $stmt = $this->db->prepare(
            "SELECT TIME_FORMAT(c.hora, '%H:%i') AS hora, COALESCE(s.duracion, 30) AS duracion
             FROM citas c
             LEFT JOIN servicios s ON c.servicio_id = s.id
             WHERE c.cliente_id = ? AND c.fecha = ?
               AND c.estado NOT IN ('cancelada')"
        );
        $stmt->execute([$cliente_id, $fecha]);
        $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ocupadas = [];
        foreach ($citas as $c) {
            $inicio = strtotime($c['hora']);
            $duracion = (int)$c['duracion'];
            // Las citas cubren un rango basado en su duración en fragmentos de 15 minutos (mínimo común).
            for ($t = 0; $t < $duracion; $t += 15) {
                $ocupadas[] = date('H:i', $inicio + ($t * 60));
            }
        }
        return array_unique($ocupadas);
    }

    /**
     * Devuelve los IDs de empleados que tienen cita a una hora concreta.
     * Usada para calcular disponibilidad por empleado (Opción B).
     */
    public function getEmpleadosOcupadosEnHora(int $clienteId, string $fecha, string $hora): array {
        $stmt = $this->db->prepare(
            "SELECT c.empleado_id
             FROM citas c
             LEFT JOIN servicios s ON c.servicio_id = s.id
             WHERE c.cliente_id = ? AND c.fecha = ?
               AND c.estado NOT IN ('cancelada')
               AND c.empleado_id IS NOT NULL
               AND TIME(STR_TO_DATE(?, '%H:%i')) >= c.hora
               AND TIME(STR_TO_DATE(?, '%H:%i')) < ADDTIME(c.hora, SEC_TO_TIME(COALESCE(s.duracion, 30) * 60))"
        );
        $horaFormato = date('H:i', strtotime($hora));
        $stmt->execute([$clienteId, $fecha, $horaFormato, $horaFormato]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Calcula la suma de mesas ocupadas en una hora específica
     */
    public function getMesasOcupadasEnHora(int $clienteId, string $fecha, string $hora): int {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(c.mesas_ocupadas), 0)
             FROM citas c
             LEFT JOIN servicios s ON c.servicio_id = s.id
             WHERE c.cliente_id = ? AND c.fecha = ?
               AND c.estado NOT IN ('cancelada', 'finalizada', 'no_llego')
               AND TIME(STR_TO_DATE(?, '%H:%i')) >= c.hora
               AND TIME(STR_TO_DATE(?, '%H:%i')) < ADDTIME(c.hora, SEC_TO_TIME(COALESCE(s.duracion, 30) * 60))"
        );
        $horaFormato = date('H:i', strtotime($hora));
        $stmt->execute([$clienteId, $fecha, $horaFormato, $horaFormato]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Cuenta cuántas citas tiene cada empleado en una fecha específica.
     * Útil para la rotación/balance de carga al elegir "Cualquiera".
     */
    public function getConteoCitasDiaPorEmpleado(int $clienteId, string $fecha, array $empleadoIds): array {
        if (empty($empleadoIds)) return [];

        $placeholders = implode(',', array_fill(0, count($empleadoIds), '?'));
        $sql = "SELECT empleado_id, COUNT(*) as total 
                FROM citas 
                WHERE cliente_id = ? AND fecha = ? 
                  AND empleado_id IN ($placeholders)
                  AND estado NOT IN ('cancelada')
                GROUP BY empleado_id";

        $params = array_merge([$clienteId, $fecha], $empleadoIds);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [id => total, id => total]
        
        // Asegurar que todos los empleados tengan una entrada (aunque sea 0)
        foreach ($empleadoIds as $id) {
            if (!isset($counts[$id])) $counts[$id] = 0;
        }
        
        return $counts;
    }

    /**
     * Estadísticas para el Dashboard: Citas por día (últimos 7 días)
     */
    public function getStatsCitasSemana(int $cliente_id): array {
        $stmt = $this->db->prepare("
            SELECT fecha, COUNT(*) as total 
            FROM citas 
            WHERE cliente_id = ? AND fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY fecha 
            ORDER BY fecha ASC
        ");
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Estadísticas para el Dashboard: Servicios más solicitados
     */
    public function getStatsServicios(int $cliente_id): array {
        $stmt = $this->db->prepare("
            SELECT s.nombre, COUNT(c.id) as total
            FROM citas c
            JOIN servicios s ON s.id = c.servicio_id
            WHERE c.cliente_id = ?
            GROUP BY s.id
            ORDER BY total DESC
            LIMIT 5
        ");
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todas las citas para el calendario en un rango de fechas
     */
    public function getCitasCalendario(int $cliente_id, string $start, string $end, ?int $empleadoId = null): array {
        $sql = "
            SELECT c.id, c.nombre_cliente as title, c.fecha as start_date, c.hora as start_time,
                   c.estado, s.nombre as servicio, u.nombre as empleado_nombre
            FROM citas c
            LEFT JOIN servicios s ON s.id = c.servicio_id
            LEFT JOIN usuarios  u ON u.id = c.empleado_id
            WHERE c.cliente_id = ? AND c.fecha BETWEEN ? AND ?
        ";
        $params = [$cliente_id, $start, $end];

        if ($empleadoId !== null) {
            $sql    .= ' AND c.empleado_id = ?';
            $params[] = $empleadoId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}