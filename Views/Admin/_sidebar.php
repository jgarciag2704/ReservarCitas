<?php
// Views/Admin/_sidebar.php  – barra lateral compartida en todas las vistas Admin
$admin      = $_SESSION['user'];
$controller = $_GET['controller'] ?? 'admin';
$action     = $_GET['action']     ?? 'index';

$brandColor = $this->negocioActual['color'] ?? '#4f46e5';
?>
<style>
    :root {
        --brand-color: <?= $brandColor ?>;
        --brand-color-soft: <?= $brandColor ?>15; /* ~8% opacity */
    }
    .bg-brand { background-color: var(--brand-color) !important; }
    .text-brand { color: var(--brand-color) !important; }
    .border-brand { border-color: var(--brand-color) !important; }
    .bg-brand-soft { background-color: var(--brand-color-soft) !important; }
    .active-nav { background-color: var(--brand-color) !important; color: white !important; }
</style>
<?php
$esAdmin  = ($admin['rol'] === 'admin');
$navItems = [
    ['action' => 'index',         'icon' => '🏠', 'label' => 'Dashboard',     'ctrl' => 'admin',    'soloAdmin' => false],
    ['action' => 'calendario',    'icon' => '📅', 'label' => 'Agenda Visual', 'ctrl' => 'admin',    'soloAdmin' => false],
    ['action' => 'citas',         'icon' => '📋', 'label' => 'Citas',         'ctrl' => 'admin',    'soloAdmin' => false],
    ['action' => 'recordatorios', 'icon' => '🔔', 'label' => 'Recordatorios','ctrl' => 'admin',    'soloAdmin' => false],
    ['action' => 'servicios',     'icon' => '🛠️', 'label' => 'Servicios',    'ctrl' => 'admin',    'soloAdmin' => true],
    ['action' => 'horarios',      'icon' => '🕐', 'label' => 'Horarios',      'ctrl' => 'admin',    'soloAdmin' => true],
    ['action' => 'index',         'icon' => '👥', 'label' => 'Empleados',     'ctrl' => 'employee', 'soloAdmin' => true],
    ['action' => 'ajustes',       'icon' => '⚙️', 'label' => 'Ajustes',       'ctrl' => 'admin',    'soloAdmin' => true],
];
?>
<aside class="w-64 bg-gray-900 text-white flex flex-col flex-shrink-0">

    <!-- Logo / nombre negocio -->
    <div class="px-6 py-8 border-b border-gray-800 flex flex-col items-center">
        <?php if (!empty($this->negocioActual['logo'])): ?>
            <div class="w-20 h-20 mb-4 p-1 rounded-2xl shadow-xl bg-brand">
                <img src="/<?= htmlspecialchars($this->negocioActual['logo']) ?>" 
                     alt="Logo" 
                     class="w-full h-full object-cover rounded-xl bg-white">
            </div>
        <?php else: ?>
            <div class="w-16 h-16 mb-4 flex items-center justify-center bg-gray-800 rounded-2xl border-2 border-gray-700 font-black text-2xl shadow-inner text-brand">
                <?= strtoupper(substr($this->negocioActual['nombre'] ?? 'C', 0, 1)) ?>
            </div>
        <?php endif; ?>
        
        <h2 class="text-sm font-bold text-center text-white tracking-wide uppercase">
            <?= htmlspecialchars($this->negocioActual['nombre'] ?? 'Panel Admin') ?>
        </h2>
        <p class="text-[10px] text-gray-500 uppercase tracking-[0.2em] mt-1 font-semibold">Panel de Control</p>
    </div>

    <!-- Navegación -->
    <nav class="flex-1 px-4 py-6 space-y-1">
        <?php foreach ($navItems as $item): ?>
            <?php
                // Ocultar items exclusivos de admin si el usuario es empleado
                if ($item['soloAdmin'] && !$esAdmin) continue;

                $isActive = ($controller === $item['ctrl'] && $action === $item['action']);
                $cls      = $isActive
                    ? 'active-nav'
                    : 'text-gray-300 hover:bg-gray-800 hover:text-white';
            ?>
            <a href="index.php?controller=<?= $item['ctrl'] ?>&action=<?= $item['action'] ?>"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $cls ?>">
                <span><?= $item['icon'] ?></span>
                <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Footer del sidebar -->
    <div class="px-6 py-4 border-t border-gray-700">
        <p class="text-xs text-gray-400 mb-3 truncate">
            👤 <?= htmlspecialchars($admin['nombre'] ?? 'Admin') ?>
        </p>
        <a href="index.php?controller=auth&action=logout"
           class="block text-center w-full bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-lg transition-colors">
            Cerrar sesión
        </a>
    </div>
</aside>
