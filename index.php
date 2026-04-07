<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Entró al index<br>";
flush();
session_start();

require_once 'config/Database.php';
/*
require_once 'Helpers/csrf.php';
//verificarCSRF();

$db = null;

try {
    $database = new Database();
    $db = $database->connect();
} catch (Exception $e) {
    echo "Sistema en configuración 🚧";
    exit;
}
// ✅ Lógica de Enrutamiento (URLs Amigables y Query Strings)
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $uri);

// Valores por defecto
$controller = $_GET['controller'] ?? 'auth';
$action     = $_GET['action']     ?? 'login';

// Si la URL limpia tiene partes, sobrescribimos (Compatibilidad)
if (!isset($_GET['controller']) && !empty($parts[0])) {
    $controller = $parts[0];
    if (isset($parts[1])) {
        $action = $parts[1];
    }
}

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
*/