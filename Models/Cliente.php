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

    public function create($nombre, $slug, $color, $telefono = null, $logo = null) {
        $stmt = $this->db->prepare("
            INSERT INTO clientes (nombre, slug, color, telefono, logo)
            VALUES (:nombre, :slug, :color, :telefono, :logo)
        ");

        return $stmt->execute([
            ':nombre' => $nombre,
            ':slug' => $slug,
            ':color' => $color,
            ':telefono' => $telefono,
            ':logo' => $logo
        ]);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $nombre, $slug, $color, $telefono = null, $logo = null) {
        // Build the query dynamically depending on whether a logo is provided
        if ($logo !== null) {
            $sql = "UPDATE clientes SET nombre = :nombre, slug = :slug, color = :color, telefono = :telefono, logo = :logo WHERE id = :id";
            $params = [
                ':nombre' => $nombre,
                ':slug' => $slug,
                ':color' => $color,
                ':telefono' => $telefono,
                ':logo' => $logo,
                ':id' => $id
            ];
        } else {
            $sql = "UPDATE clientes SET nombre = :nombre, slug = :slug, color = :color, telefono = :telefono WHERE id = :id";
            $params = [
                ':nombre' => $nombre,
                ':slug' => $slug,
                ':color' => $color,
                ':telefono' => $telefono,
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