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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Configuración global de Toasts (Notificaciones flotantes)
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    // Función global para confirmar eliminaciones
    function confirmDelete(url, title = '¿Estás seguro?', text = 'Esta acción es permanente.') {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--brand-color)',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            background: '#fff',
            borderRadius: '1.5rem',
            customClass: {
                confirmButton: 'rounded-xl px-5 py-2.5 font-bold',
                cancelButton: 'rounded-xl px-5 py-2.5 font-bold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
        return false; // Prevenir acción por defecto
    }

    // Mostrar alertas de PHP automáticamente como SweetAlerts
    document.addEventListener('DOMContentLoaded', () => {
        <?php if ($success = ($_SESSION['success'] ?? null)): unset($_SESSION['success']); ?>
            Toast.fire({ icon: 'success', title: '<?= addslashes($success) ?>' });
        <?php endif; ?>

        <?php if ($error = ($_SESSION['error'] ?? null)): unset($_SESSION['error']); ?>
            Swal.fire({ 
                icon: 'error', 
                title: '¡Vaya!', 
                text: '<?= addslashes($error) ?>',
                confirmButtonColor: 'var(--brand-color)',
                borderRadius: '1.5rem'
            });
        <?php endif; ?>
    });
</script>
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
<!-- 📱 Mobile Top Navigation (App-style) -->
<nav class="sticky top-0 md:hidden bg-slate-900 shadow-2xl z-[100] border-b border-white/5">
    <div class="flex overflow-x-auto whitespace-nowrap custom-scrollbar scroll-smooth px-3 py-3 gap-3 items-center">
        <!-- Logo en pequeño -->
        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-brand p-0.5 shadow-lg shadow-brand/20 mr-2">
            <img src="/<?= htmlspecialchars($this->negocioActual['logo'] ?? 'favicon.png') ?>" 
                 alt="Logo" class="w-full h-full object-cover rounded-[10px] bg-white">
        </div>

        <?php foreach ($navItems as $item): ?>
            <?php
                if ($item['soloAdmin'] && !$esAdmin) continue;
                $isActive = ($controller === $item['ctrl'] && $action === $item['action']);
                $cls      = $isActive
                    ? 'bg-brand text-white shadow-xl shadow-brand/30 scale-105'
                    : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white';
            ?>
            <a href="index.php?controller=<?= $item['ctrl'] ?>&action=<?= $item['action'] ?>"
               class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all duration-300 <?= $cls ?>">
                <span class="text-base"><?= $item['icon'] ?></span>
                <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>
    </div>
</nav>

<!-- 🖥️ Desktop Sidebar -->
<aside id="main-sidebar" class="hidden md:flex flex-col w-72 bg-gray-900 text-white flex-shrink-0 relative h-screen shadow-2xl border-r border-white/5">

    <!-- Logo / nombre negocio -->
    <div class="px-6 py-8 border-b border-gray-800 flex flex-col items-center">
        <?php if (!empty($this->negocioActual['logo'])): ?>
            <div class="w-20 h-20 mb-4 p-1 rounded-2xl shadow-xl bg-brand transition-transform hover:scale-105 duration-300">
                <img src="/<?= htmlspecialchars($this->negocioActual['logo']) ?>" 
                     alt="Logo" 
                     class="w-full h-full object-cover rounded-xl bg-white">
            </div>
        <?php else: ?>
            <div class="w-16 h-16 mb-4 flex items-center justify-center bg-gray-800 rounded-2xl border-2 border-gray-700 font-black text-2xl shadow-inner text-brand">
                <?= strtoupper(substr($this->negocioActual['nombre'] ?? 'C', 0, 1)) ?>
            </div>
        <?php endif; ?>
        
        <h2 class="text-sm font-bold text-center text-white tracking-wide uppercase px-2">
            <?= htmlspecialchars($this->negocioActual['nombre'] ?? 'Panel Admin') ?>
        </h2>
        <p class="text-[10px] text-gray-500 uppercase tracking-[0.2em] mt-1 font-semibold">Panel de Control</p>
    </div>

    <!-- Navegación -->
    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto custom-scrollbar">
        <?php foreach ($navItems as $item): ?>
            <?php
                if ($item['soloAdmin'] && !$esAdmin) continue;
                $isActive = ($controller === $item['ctrl'] && $action === $item['action']);
                $cls      = $isActive
                    ? 'active-nav shadow-lg shadow-brand/20'
                    : 'text-gray-400 hover:bg-gray-800/50 hover:text-white';
            ?>
            <a href="index.php?controller=<?= $item['ctrl'] ?>&action=<?= $item['action'] ?>"
               class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all group <?= $cls ?>">
                <span class="text-lg transition-transform group-hover:scale-110"><?= $item['icon'] ?></span>
                <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Footer del sidebar -->
    <div class="px-6 py-4 border-t border-gray-800/50 bg-gray-900/50 mt-auto">
        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-widest mb-3 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            En línea: <?= htmlspecialchars($admin['nombre'] ?? 'Admin') ?>
        </p>
        <a href="index.php?controller=auth&action=logout"
           class="flex items-center justify-center gap-2 w-full bg-red-600/10 hover:bg-red-600 text-red-500 hover:text-white border border-red-600/20 text-xs font-bold px-4 py-2.5 rounded-xl transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Cerrar sesión
        </a>
    </div>
</aside>

<style>
    .custom-scrollbar::-webkit-scrollbar { height: 4px; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.05); }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--brand-color); border-radius: 10px; }
</style>
