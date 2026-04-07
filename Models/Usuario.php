<?php
require_once BASE_PATH . 'Models/BaseModel.php';

class Usuario extends BaseModel {

    public function __construct($db = null) {
        if ($db !== null) {
            $this->db = $db;
        } else {
            parent::__construct();
        }
    }

    // ── Consultas ─────────────────────────────────────────────────────────────

    /** @deprecated Usar getByEmail */
    public function login(string $email): array|false {
        return $this->getByEmail($email);
    }

    public function getByEmail(string $email): array|false {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    public function createAdmin(int $cliente_id, string $email, string $password): bool {
        $existing = $this->getByEmail($email);
        if ($existing) {
            return false;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (cliente_id, email, password, rol)
             VALUES (:cliente_id, :email, :password, "admin")'
        );

        return $stmt->execute([
            ':cliente_id' => $cliente_id,
            ':email'      => $email,
            ':password'   => $hash,
        ]);
    }
}