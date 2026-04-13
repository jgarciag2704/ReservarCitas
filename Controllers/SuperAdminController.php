<?php
require_once BASE_PATH . 'Models/Cliente.php';
require_once BASE_PATH . 'Models/Usuario.php';

class SuperAdminController {
    private $db;
    private $clienteModel;
    private $usuarioModel;

    public function __construct($db) {
        $this->db = $db;
        $this->clienteModel = new Cliente($db);
        $this->usuarioModel = new Usuario($db);

        $this->checkSuperAdmin();
    }

    private function checkSuperAdmin() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'superadmin') {
            header("Location: /login");
            exit;
        }

        if (!empty($_SESSION['user']['force_password_change'])) {
            header('Location: index.php?controller=auth&action=cambiarPassword');
            exit;
        }
    }

    // 📌 LISTAR CLIENTES
    public function index() {
        $clientes = $this->clienteModel->getAll();
        require BASE_PATH . 'Views/SuperAdmin/clientes.php';
    }

    // 📌 CREAR CLIENTE + ADMIN
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $nombre = trim($_POST['nombre']);
            $slug = trim($_POST['slug']);
            $color = trim($_POST['color']);
            $telefono = trim($_POST['telefono'] ?? '');
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);

            // VALIDACIONES
            if (!$nombre || !$slug || !$email || !$password) {
                $_SESSION['error'] = "Todos los campos son obligatorios";
                header("Location: /index.php?controller=superadmin&action=index");
                exit;
            }

            if ($this->clienteModel->slugExists($slug)) {
                $_SESSION['error'] = "El slug ya existe";
                header("Location: /index.php?controller=superadmin&action=index");
                exit;
            }

            if ($this->usuarioModel->getByEmail($email)) {
                $_SESSION['warning'] = "El email ya está registrado.";
                header("Location: /index.php?controller=superadmin&action=index");
                exit;
            }

            $logoPath = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $filename = $slug . '-' . time() . '.' . $ext;
                    $dest = 'Assets/logos/' . $filename;
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                        $logoPath = $dest;
                    }
                }
            }

           try {

    $this->clienteModel->create($nombre, $slug, $color, $telefono, $logoPath);

    $cliente_id = $this->db->lastInsertId();

    if (!$cliente_id) {
        throw new Exception("No se obtuvo cliente_id");
    }

    $adminCreado = $this->usuarioModel->createAdmin($cliente_id, $email, $password);
    if (!$adminCreado) {
        throw new Exception("Hubo un error al crear el usuario administrador.");
    }

    $_SESSION['success'] = "Cliente creado correctamente";

} catch (Exception $e) {

    if ($this->db->inTransaction()) {
        $this->db->rollBack();
    }

    $_SESSION['error'] = "Error: " . $e->getMessage();
}

            header("Location: /index.php?controller=superadmin&action=index");
        }
    }

    // 📌 EDITAR
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $id = $_POST['id'];
            $nombre = $_POST['nombre'];
            $slug = $_POST['slug'];
            $color = $_POST['color'];
            $telefono = $_POST['telefono'] ?? '';

            $logoPath = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $filename = $slug . '-' . time() . '.' . $ext;
                    $dest = 'Assets/logos/' . $filename;
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                        $logoPath = $dest;
                    }
                }
            }

            $this->clienteModel->update($id, $nombre, $slug, $color, $telefono, $logoPath);

            $_SESSION['success'] = "Cliente actualizado";
            header("Location: /index.php?controller=superadmin&action=index");
        }
    }

    // 📌 ELIMINAR
    public function delete() {
        if (isset($_GET['id'])) {
            $this->clienteModel->delete($_GET['id']);
            $_SESSION['success'] = "Cliente eliminado";
        }

        header("Location: /index.php?controller=superadmin&action=index");
    }

    // 📌 RESTABLECER CONTRASEÑA DEL ADMINISTRADOR DEL CLIENTE
    public function resetPassword() {
        $clienteId = (int)($_GET['cliente_id'] ?? 0);

        if (!$clienteId) {
            $_SESSION['error'] = "Cliente inválido.";
            header("Location: /index.php?controller=superadmin&action=index");
            exit;
        }

        $adminUsuario = $this->usuarioModel->getByClienteAndRole($clienteId, 'admin');
        if (!$adminUsuario) {
            $_SESSION['error'] = "No se encontró el administrador de este cliente.";
            header("Location: /index.php?controller=superadmin&action=index");
            exit;
        }

        $this->usuarioModel->updatePassword((int)$adminUsuario['id'], 'Temporal1', true);
        $_SESSION['success'] = "Contraseña temporal establecida. El administrador deberá cambiarla en el próximo inicio de sesión.";
        header("Location: /index.php?controller=superadmin&action=index");
        exit;
    }
}
