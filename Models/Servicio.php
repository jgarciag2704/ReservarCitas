<?php
require_once BASE_PATH . 'Models/BaseModel.php';

class Servicio extends BaseModel {

    /**
     * Si se inyecta un PDO externo se usa; si no, BaseModel abre su propia conexión.
     * Esto mantiene compatibilidad con el patrón ya existente.
     */
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
            'SELECT * FROM servicios WHERE cliente_id = ? ORDER BY nombre ASC'
        );
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica que un servicio pertenezca al cliente (multitenant ownership check).
     */
    public function perteneceACliente(int $id, int $cliente_id): bool {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM servicios WHERE id = ? AND cliente_id = ?'
        );
        $stmt->execute([$id, $cliente_id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Retorna los empleados asignados a un servicio.
     * Valida que el servicio pertenezca al cliente (multitenant).
     */
    public function getEmpleadosPorServicio(int $servicioId, int $clienteId): array {
        $stmt = $this->db->prepare("
            SELECT u.id, u.nombre, u.email, u.activo, u.especialidad, u.experiencia, u.google_maps
            FROM usuarios u
            JOIN servicio_empleados se ON se.empleado_id = u.id
            JOIN servicios s ON s.id  = se.servicio_id
            WHERE se.servicio_id = :sid
              AND s.cliente_id   = :cid
              AND u.activo       = 1
            ORDER BY u.nombre ASC
        ");
        $stmt->execute([':sid' => $servicioId, ':cid' => $clienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reemplaza las asignaciones de empleados de un servicio.
     * Solo acepta empleados activos del mismo cliente.
     */
    public function asignarEmpleados(int $servicioId, int $clienteId, array $empleadoIds): bool {
        if (!$this->perteneceACliente($servicioId, $clienteId)) {
            return false;
        }

        // Borrar asignaciones actuales
        $del = $this->db->prepare('DELETE FROM servicio_empleados WHERE servicio_id = ?');
        $del->execute([$servicioId]);

        if (empty($empleadoIds)) {
            return true; // Sin asignaciones es válido
        }

        // Insertar nuevas asignaciones validando que el empleado pertenezca al cliente
        $ins = $this->db->prepare("
            INSERT IGNORE INTO servicio_empleados (servicio_id, empleado_id)
            SELECT :sid, u.id
            FROM usuarios u
            WHERE u.id = :uid
              AND u.cliente_id = :cid
              AND u.rol        = 'empleado'
              AND u.activo     = 1
        ");

        foreach ($empleadoIds as $empId) {
            $ins->execute([':sid' => $servicioId, ':uid' => (int)$empId, ':cid' => $clienteId]);
        }

        return true;
    }

    /**
     * Retorna los servicios asignados a un empleado.
     * Operación inversa a getEmpleadosPorServicio().
     */
    public function getServiciosPorEmpleado(int $empleadoId, int $clienteId): array {
        $stmt = $this->db->prepare("
            SELECT s.id, s.nombre, s.duracion, s.precio
            FROM servicio_empleados se
            JOIN servicios  s ON s.id  = se.servicio_id
            JOIN usuarios   u ON u.id  = se.empleado_id
            WHERE se.empleado_id = :eid
              AND s.cliente_id   = :cid
              AND u.activo       = 1
            ORDER BY s.nombre ASC
        ");
        $stmt->execute([':eid' => $empleadoId, ':cid' => $clienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reemplaza los servicios asignados a un empleado.
     * Operación inversa a asignarEmpleados().
     */
    public function asignarServiciosAEmpleado(int $empleadoId, int $clienteId, array $servicioIds): bool {
        // Borrar asignaciones actuales de este empleado
        $del = $this->db->prepare('DELETE FROM servicio_empleados WHERE empleado_id = ?');
        $del->execute([$empleadoId]);

        if (empty($servicioIds)) {
            return true;
        }

        // Insertar nuevas asignaciones validando que el servicio pertenezca al cliente
        $ins = $this->db->prepare("
            INSERT IGNORE INTO servicio_empleados (servicio_id, empleado_id)
            SELECT s.id, :eid
            FROM servicios s
            WHERE s.id = :sid AND s.cliente_id = :cid
        ");

        foreach ($servicioIds as $sid) {
            $ins->execute([':eid' => $empleadoId, ':sid' => (int)$sid, ':cid' => $clienteId]);
        }

        return true;
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    public function crear(int $cliente_id, string $nombre, int $duracion, float $precio): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO servicios (cliente_id, nombre, duracion, precio)
             VALUES (:cliente_id, :nombre, :duracion, :precio)'
        );
        return $stmt->execute([
            ':cliente_id' => $cliente_id,
            ':nombre'     => $nombre,
            ':duracion'   => $duracion,
            ':precio'     => $precio,
        ]);
    }

    public function actualizar(int $id, int $cliente_id, string $nombre, int $duracion, float $precio): bool {
        $stmt = $this->db->prepare(
            'UPDATE servicios
             SET nombre = :nombre, duracion = :duracion, precio = :precio
             WHERE id = :id AND cliente_id = :cliente_id'
        );
        return $stmt->execute([
            ':nombre'     => $nombre,
            ':duracion'   => $duracion,
            ':precio'     => $precio,
            ':id'         => $id,
            ':cliente_id' => $cliente_id,
        ]);
    }

    public function eliminar(int $id, int $cliente_id): bool {
        $stmt = $this->db->prepare(
            'DELETE FROM servicios WHERE id = ? AND cliente_id = ?'
        );
        return $stmt->execute([$id, $cliente_id]);
    }
}