<?php
require_once BASE_PATH . 'Models/BaseModel.php';

class Horario extends BaseModel {

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
            'SELECT h.*, u.nombre AS empleado_nombre 
             FROM horarios h
             LEFT JOIN usuarios u ON u.id = h.empleado_id
             WHERE h.cliente_id = ? 
             ORDER BY FIELD(h.dia_semana, "Lunes","Martes","Miércoles","Jueves","Viernes","Sábado","Domingo"),
                      h.empleado_id IS NOT NULL DESC'
        );
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los horarios de un día. 
     * Si se especifica empleado_id, intenta buscar el horario de ESE empleado.
     * Si no tiene horario propio, devuelve el general del negocio.
     */
    public function getByDia(int $cliente_id, string $dia, ?int $empleado_id = null): array {
        if ($empleado_id) {
            // Intentamos buscar si el empleado tiene horario ESE día
            $stmt = $this->db->prepare(
                'SELECT * FROM horarios WHERE cliente_id = ? AND dia_semana = ? AND empleado_id = ?'
            );
            $stmt->execute([$cliente_id, $dia, $empleado_id]);
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si tiene horario propio, lo devolvemos
            if (!empty($res)) return $res;
        }

        // Si no se pide empleado o el empleado no tiene horario propio, usamos el general
        $stmt = $this->db->prepare(
            'SELECT * FROM horarios WHERE cliente_id = ? AND dia_semana = ? AND empleado_id IS NULL'
        );
        $stmt->execute([$cliente_id, $dia]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica ownership del horario (multitenant).
     */
    public function perteneceACliente(int $id, int $cliente_id): bool {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM horarios WHERE id = ? AND cliente_id = ?'
        );
        $stmt->execute([$id, $cliente_id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    public function crear(array $data): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO horarios (cliente_id, empleado_id, dia_semana, hora_inicio, hora_fin)
             VALUES (?, ?, ?, ?, ?)'
        );
        return $stmt->execute([
            $data['cliente_id'],
            $data['empleado_id'] ?? null,
            $data['dia'],
            $data['inicio'],
            $data['fin'],
        ]);
    }

    public function eliminar(int $id, int $cliente_id): bool {
        $stmt = $this->db->prepare(
            'DELETE FROM horarios WHERE id = ? AND cliente_id = ?'
        );
        return $stmt->execute([$id, $cliente_id]);
    }

    public function actualizar(int $id, int $cliente_id, array $data): bool {
        $stmt = $this->db->prepare(
            'UPDATE horarios 
             SET empleado_id = ?, dia_semana = ?, hora_inicio = ?, hora_fin = ?
             WHERE id = ? AND cliente_id = ?'
        );
        return $stmt->execute([
            $data['empleado_id'] ?? null,
            $data['dia'],
            $data['inicio'],
            $data['fin'],
            $id,
            $cliente_id
        ]);
    }
}