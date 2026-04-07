<?php
require_once BASE_PATH . 'Models/BaseModel.php';

class Empleado extends BaseModel {

    public function __construct($db = null) {
        if ($db !== null) {
            $this->db = $db;
        } else {
            parent::__construct();
        }
    }

    // =========================================================================
    // CONSULTAS
    // =========================================================================

    /**
     * Lista todos los empleados de un cliente (negocio).
     * Excluye al admin principal (rol = 'admin') para no listarse a sí mismo.
     */
    public function getByCliente(int $clienteId): array {
        $stmt = $this->db->prepare("
            SELECT id, nombre, email, telefono, rol, activo
            FROM usuarios
            WHERE cliente_id = :cid
              AND rol = 'empleado'
            ORDER BY nombre ASC
        ");
        $stmt->execute([':cid' => $clienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un empleado verificando que pertenezca al cliente (multitenant).
     */
    public function find(int $id, int $clienteId): array|false {
        $stmt = $this->db->prepare("
            SELECT id, nombre, email, telefono, rol, activo
            FROM usuarios
            WHERE id = :id AND cliente_id = :cid AND rol = 'empleado'
        ");
        $stmt->execute([':id' => $id, ':cid' => $clienteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // ESCRITURA
    // =========================================================================

    /**
     * Crea un nuevo empleado para el cliente.
     * Retorna true en éxito, false si el email ya existe.
     */
    public function crear(array $data): bool {
        if ($this->emailExiste($data['email'])) {
            return false;
        }

        $hash = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO usuarios (cliente_id, nombre, email, telefono, password, rol, activo)
            VALUES (:cliente_id, :nombre, :email, :telefono, :password, 'empleado', 1)
        ");

        return $stmt->execute([
            ':cliente_id' => $data['cliente_id'],
            ':nombre'     => $data['nombre'],
            ':email'      => $data['email'],
            ':telefono'   => $data['telefono'] ?? null,
            ':password'   => $hash,
        ]);
    }

    /**
     * Actualiza datos de un empleado. Si password viene vacío, no lo modifica.
     */
    public function actualizar(int $id, int $clienteId, array $data): bool {
        // Verificar ownership
        if (!$this->find($id, $clienteId)) {
            return false;
        }

        // Verificar que el email no esté en uso por OTRO usuario
        if ($this->emailExiste($data['email'], $id)) {
            return false;
        }

        // ¿Actualizar también la contraseña?
        if (!empty($data['password'])) {
            $stmt = $this->db->prepare("
                UPDATE usuarios
                SET nombre = :nombre, email = :email, telefono = :telefono, password = :password
                WHERE id = :id AND cliente_id = :cid AND rol = 'empleado'
            ");
            return $stmt->execute([
                ':nombre'    => $data['nombre'],
                ':email'     => $data['email'],
                ':telefono'  => $data['telefono'] ?? null,
                ':password'  => password_hash($data['password'], PASSWORD_DEFAULT),
                ':id'        => $id,
                ':cid'       => $clienteId,
            ]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE usuarios
                SET nombre = :nombre, email = :email, telefono = :telefono
                WHERE id = :id AND cliente_id = :cid AND rol = 'empleado'
            ");
            return $stmt->execute([
                ':nombre'   => $data['nombre'],
                ':email'    => $data['email'],
                ':telefono' => $data['telefono'] ?? null,
                ':id'       => $id,
                ':cid'      => $clienteId,
            ]);
        }
    }

    /**
     * Alterna el estado activo/inactivo de un empleado.
     */
    public function toggleActivo(int $id, int $clienteId): bool {
        $stmt = $this->db->prepare("
            UPDATE usuarios
            SET activo = 1 - activo
            WHERE id = :id AND cliente_id = :cid AND rol = 'empleado'
        ");
        return $stmt->execute([':id' => $id, ':cid' => $clienteId]);
    }

    /**
     * Elimina permanentemente a un empleado.
     */
    public function eliminar(int $id, int $clienteId): bool {
        $stmt = $this->db->prepare("
            DELETE FROM usuarios
            WHERE id = :id AND cliente_id = :cid AND rol = 'empleado'
        ");
        return $stmt->execute([':id' => $id, ':cid' => $clienteId]);
    }

    // =========================================================================
    // VALIDACIONES
    // =========================================================================

    /**
     * Verifica si un email ya existe en la tabla usuarios.
     * Si $excludeId se proporciona, excluye ese registro (util en edición).
     */
    public function emailExiste(string $email, ?int $excludeId = null): bool {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM usuarios WHERE email = :email AND id != :id
            ");
            $stmt->execute([':email' => $email, ':id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM usuarios WHERE email = :email
            ");
            $stmt->execute([':email' => $email]);
        }
        return (int)$stmt->fetchColumn() > 0;
    }
}
