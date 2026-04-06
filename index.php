<?php
session_start();

require_once 'config/Database.php';

require_once 'Helpers/csrf.php';
verificarCSRF();

// ✅ Crear instancia y conexión
$database = new Database();
$db = $database->connect();

$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'login';

switch ($controller) {

    case 'cliente':
        require_once 'Controllers/ClienteController.php';
        $ctrl = new ClienteController($db);
        break;

    case 'auth':
        require_once 'Controllers/AuthController.php';
        $ctrl = new AuthController($db);
        break;

    case 'admin':
        require_once 'Controllers/AdminController.php';
        $ctrl = new AdminController($db);
        break;

    case 'superadmin':
        require_once 'Controllers/SuperAdminController.php';
        $ctrl = new SuperAdminController($db);
        break;

    case 'employee':
        require_once 'Controllers/EmployeeController.php';
        $ctrl = new EmployeeController($db);
        break;

    default:
        die("Ruta no válida");
}

if (!method_exists($ctrl, $action)) {
    die("Acción no válida");
}

$ctrl->$action();