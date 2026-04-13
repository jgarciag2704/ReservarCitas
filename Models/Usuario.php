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

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByClienteAndRole(int $cliente_id, string $rol): array|false {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE cliente_id = ? AND rol = ? LIMIT 1');
        $stmt->execute([$cliente_id, $rol]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePassword(int $id, string $password, bool $forcePasswordChange = false): bool {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET password = :password, force_password_change = :force WHERE id = :id'
        );
        return $stmt->execute([
            ':password' => $hash,
            ':force'    => $forcePasswordChange ? 1 : 0,
            ':id'       => $id,
        ]);
    }

    public function setForcePasswordChange(int $id, bool $value): bool {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET force_password_change = :force WHERE id = :id'
        );
        return $stmt->execute([
            ':force' => $value ? 1 : 0,
            ':id'    => $id,
        ]);
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