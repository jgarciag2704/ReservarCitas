<?php
class Cliente {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT * FROM clientes ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($nombre, $slug, $color, $telefono = null, $logo = null, $tipo_reserva = 'individual', $cantidad_mesas = 1, $sillas_por_mesa = 4, $porcentaje_online = 100, $tiempo_gracia = 15) {
        $stmt = $this->db->prepare("
            INSERT INTO clientes (nombre, slug, color, telefono, logo, tipo_reserva, cantidad_mesas, sillas_por_mesa, porcentaje_online, tiempo_gracia)
            VALUES (:nombre, :slug, :color, :telefono, :logo, :tipo_reserva, :cantidad_mesas, :sillas_por_mesa, :porcentaje_online, :tiempo_gracia)
        ");

        return $stmt->execute([
            ':nombre' => $nombre,
            ':slug' => $slug,
            ':color' => $color,
            ':telefono' => $telefono,
            ':logo' => $logo,
            ':tipo_reserva' => $tipo_reserva,
            ':cantidad_mesas' => $cantidad_mesas,
            ':sillas_por_mesa' => $sillas_por_mesa,
            ':porcentaje_online' => $porcentaje_online,
            ':tiempo_gracia' => $tiempo_gracia
        ]);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $nombre, $slug, $color, $telefono = null, $logo = null, $tipo_reserva = 'individual', $cantidad_mesas = 1, $sillas_por_mesa = 4, $porcentaje_online = 100, $tiempo_gracia = 15) {
        // Build the query dynamically depending on whether a logo is provided
        if ($logo !== null) {
            $sql = "UPDATE clientes SET nombre = :nombre, slug = :slug, color = :color, telefono = :telefono, logo = :logo, tipo_reserva = :tipo_reserva, cantidad_mesas = :cantidad_mesas, sillas_por_mesa = :sillas_por_mesa, porcentaje_online = :porcentaje_online, tiempo_gracia = :tiempo_gracia WHERE id = :id";
            $params = [
                ':nombre' => $nombre,
                ':slug' => $slug,
                ':color' => $color,
                ':telefono' => $telefono,
                ':logo' => $logo,
                ':tipo_reserva' => $tipo_reserva,
                ':cantidad_mesas' => $cantidad_mesas,
                ':sillas_por_mesa' => $sillas_por_mesa,
                ':porcentaje_online' => $porcentaje_online,
                ':tiempo_gracia' => $tiempo_gracia,
                ':id' => $id
            ];
        } else {
            $sql = "UPDATE clientes SET nombre = :nombre, slug = :slug, color = :color, telefono = :telefono, tipo_reserva = :tipo_reserva, cantidad_mesas = :cantidad_mesas, sillas_por_mesa = :sillas_por_mesa, porcentaje_online = :porcentaje_online, tiempo_gracia = :tiempo_gracia WHERE id = :id";
            $params = [
                ':nombre' => $nombre,
                ':slug' => $slug,
                ':color' => $color,
                ':telefono' => $telefono,
                ':tipo_reserva' => $tipo_reserva,
                ':cantidad_mesas' => $cantidad_mesas,
                ':sillas_por_mesa' => $sillas_por_mesa,
                ':porcentaje_online' => $porcentaje_online,
                ':tiempo_gracia' => $tiempo_gracia,
                ':id' => $id
            ];
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM clientes WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function slugExists($slug) {
        $stmt = $this->db->prepare("SELECT id FROM clientes WHERE slug = :slug");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch();
    }

    public function getBySlug(string $slug): array|false {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}