<?php
//var_dump(scandir(__DIR__));
//exit;
session_start();

// Configurar zona horaria por defecto para México (UTC-6)
date_default_timezone_set('America/Mexico_City');

// ✅ Definir la ruta base del proyecto (Absolute Path)
define('BASE_PATH', __DIR__ . '/');

require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'Helpers/csrf.php';
verificarCSRF();

// ✅ Crear instancia y conexión
$db = null;

try {
    $database = new Database();
    $db = $database->connect();

    // Validación adicional
    $test = $db->query("SELECT 1");
    if (!$test) {
        throw new Exception("No se pudo validar la conexión");
    }

} catch (Exception $e) {
    echo "<h2>Sistema en configuración 🚧</h2>";
    echo "<p>Base de datos no disponible aún.</p>";
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