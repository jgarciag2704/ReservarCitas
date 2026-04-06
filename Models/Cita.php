<?php
require_once 'Models/BaseModel.php';

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
                'INSERT INTO citas (cliente_id, servicio_id, empleado_id, nombre_cliente, telefono, fecha, hora)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            return $stmt->execute([
                $data['cliente_id'],
                $data['servicio_id'],
                $data['empleado_id'] ?? null,
                $data['nombre'],
                $data['telefono'],
                $data['fecha'],
                $data['hora'],
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return false;
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
            "SELECT TIME_FORMAT(hora, '%H:%i') AS hora
             FROM citas
             WHERE cliente_id = ? AND fecha = ?
               AND estado NOT IN ('cancelada')"
        );
        $stmt->execute([$cliente_id, $fecha]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Devuelve los IDs de empleados que tienen cita a una hora concreta.
     * Usada para calcular disponibilidad por empleado (Opción B).
     */
    public function getEmpleadosOcupadosEnHora(int $clienteId, string $fecha, string $hora): array {
        $stmt = $this->db->prepare(
            "SELECT empleado_id
             FROM citas
             WHERE cliente_id = ? AND fecha = ?
               AND TIME_FORMAT(hora, '%H:%i') = ?
               AND estado NOT IN ('cancelada')
               AND empleado_id IS NOT NULL"
        );
        $stmt->execute([$clienteId, $fecha, $hora]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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