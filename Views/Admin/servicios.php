<?php
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios – Panel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="flex flex-col md:flex-row h-screen overflow-hidden">

    <?php require 'Views/Admin/_sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-10 bg-slate-50">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 mt-4 md:mt-0">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Servicios</h1>
                <p class="text-gray-500 mt-1 font-medium italic">Gestiona los servicios de tu negocio</p>
            </div>
            <button onclick="openModal()"
                    class="w-full md:w-auto bg-brand text-white px-6 py-3 rounded-2xl font-bold transition-all hover:scale-[1.02] active:scale-[0.98] shadow-xl shadow-brand/20 flex items-center justify-center gap-2">
                <span class="text-xl">+</span>
                <span>Nuevo servicio</span>
            </button>
        </div>


        <!-- Tabla -->
        <div class="bg-white rounded-2xl shadow overflow-hidden">
            <?php if (empty($servicios)): ?>
                <div class="text-center py-16 text-gray-400">
                    <p class="text-4xl mb-3">🛠️</p>
                    <p>No hay servicios registrados aún.</p>
                </div>
            <?php else: ?>
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600">
                            <th class="p-4">Nombre</th>
                            <th class="p-4">Duración (min)</th>
                            <th class="p-4">Precio</th>
                            <th class="p-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servicios as $s): ?>
                            <tr class="border-t hover:bg-gray-50 transition-colors">
                                <td class="p-4 font-medium"><?= htmlspecialchars($s['nombre']) ?></td>
                                <td class="p-4"><?= (int)$s['duracion'] ?> min</td>
                                <td class="p-4">$<?= number_format((float)$s['precio'], 2) ?></td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button onclick='openAsignarModal(<?= (int)$s['id'] ?>, "<?= htmlspecialchars(addslashes($s['nombre'])) ?>")'
                                                class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-2 rounded-xl text-xs font-extrabold transition-all shadow-lg shadow-indigo-100 whitespace-nowrap active:scale-95">
                                            👥 Personal
                                        </button>
                                        <button onclick='openEditModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)'
                                                class="bg-amber-400 hover:bg-amber-500 text-white px-3 py-2 rounded-xl text-xs font-extrabold transition-all shadow-lg shadow-amber-100 whitespace-nowrap active:scale-95">
                                            Editar
                                        </button>
                                        <a href="index.php?controller=admin&action=deleteServicio&id=<?= (int)$s['id'] ?>"
                                           onclick="return confirmDelete(this.href, '¿Eliminar servicio?', 'Se eliminará permanentemente: <?= htmlspecialchars(addslashes($s['nombre'])) ?>')"
                                           class="bg-rose-500 hover:bg-rose-600 text-white px-3 py-2 rounded-xl text-xs font-extrabold transition-all shadow-lg shadow-rose-100 whitespace-nowrap active:scale-95">
                                            Eliminar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- ── MODAL CREAR ─────────────────────────────────────────────────────── -->
<div id="modalCrear" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-xl font-semibold mb-5">Nuevo Servicio</h3>
        <form method="POST" action="index.php?controller=admin&action=storeServicio" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                <input type="text" name="nombre" required
                       class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-400 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duración (min)</label>
                    <input type="number" name="duracion" min="1" required
                           class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-brand outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
                    <input type="number" name="precio" min="0" step="0.01" value="0"
                           class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-brand outline-none">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal()"
                        class="px-4 py-2 rounded-lg bg-gray-200 text-sm hover:bg-gray-300 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-lg bg-brand text-white text-sm font-medium hover:brightness-110 transition-colors">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL EDITAR ─────────────────────────────────────────────────────── -->
<div id="modalEditar" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-xl font-semibold mb-5">Editar Servicio</h3>
        <form method="POST" action="index.php?controller=admin&action=updateServicio" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="edit_id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                <input type="text" name="nombre" id="edit_nombre" required
                       class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-400 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duración (min)</label>
                    <input type="number" name="duracion" id="edit_duracion" min="1" required
                           class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-brand outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
                    <input type="number" name="precio" id="edit_precio" min="0" step="0.01"
                           class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-brand outline-none">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 rounded-lg bg-gray-200 text-sm hover:bg-gray-300 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 transition-colors">
                    Actualizar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal()      { document.getElementById('modalCrear').classList.remove('hidden'); }
function closeModal()     { document.getElementById('modalCrear').classList.add('hidden');    }
function closeEditModal() { document.getElementById('modalEditar').classList.add('hidden');   }

function openEditModal(data) {
    document.getElementById('modalEditar').classList.remove('hidden');
    document.getElementById('edit_id').value       = data.id;
    document.getElementById('edit_nombre').value   = data.nombre;
    document.getElementById('edit_duracion').value = data.duracion;
    document.getElementById('edit_precio').value   = data.precio;
}

// Modal de asignación de empleados
const asignaciones = <?= json_encode($asignaciones ?? []) ?>;
const todosEmpleados = <?= json_encode($todosEmpleados ?? []) ?>;

function openAsignarModal(servicioId, servicioNombre) {
    document.getElementById('asig_servicio_id').value = servicioId;
    document.getElementById('asig_titulo').textContent = servicioNombre;

    const container = document.getElementById('asig_empleados');
    container.innerHTML = '';

    if (!todosEmpleados.length) {
        container.innerHTML = '<p class="text-slate-400 text-sm text-center py-4">No tienes empleados creados aún.<br><a href="index.php?controller=employee&action=index" class="text-indigo-500 font-semibold">Crear empleados →</a></p>';
    } else {
        const asignados = asignaciones[servicioId] || [];
        todosEmpleados.forEach(emp => {
            const checked = asignados.includes(parseInt(emp.id)) ? 'checked' : '';
            container.innerHTML += `
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 cursor-pointer transition-colors">
                    <input type="checkbox" name="empleado_ids[]" value="${emp.id}" ${checked}
                           class="w-4 h-4 rounded accent-indigo-600">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm">
                        ${emp.nombre.charAt(0).toUpperCase()}
                    </div>
                    <span class="text-sm font-medium text-slate-700">${emp.nombre}</span>
                    <span class="ml-auto text-xs text-slate-400">${emp.email}</span>
                </label>
            `;
        });
    }

    document.getElementById('modalAsignar').classList.remove('hidden');
}

function closeAsignarModal() {
    document.getElementById('modalAsignar').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('modalAsignar').addEventListener('click', function(e) {
        if (e.target === this) closeAsignarModal();
    });
});
</script>

</body>
</html>

<!-- ── MODAL ASIGNAR EMPLEADOS ──────────────────────────────────────────────── -->
<div id="modalAsignar" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">👥 Personal del servicio</h3>
                <p id="asig_titulo" class="text-sm text-gray-500 mt-0.5"></p>
            </div>
            <button onclick="closeAsignarModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <form method="POST" action="index.php?controller=admin&action=asignarEmpleados">
            <?= csrf_field() ?>
            <input type="hidden" name="servicio_id" id="asig_servicio_id">
            <div id="asig_empleados" class="px-6 py-4 space-y-2 max-h-72 overflow-y-auto">
                <!-- Empleados generados por JS -->
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" onclick="closeAsignarModal()"
                        class="px-5 py-2.5 rounded-xl bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-md">
                    Guardar asignación
                </button>
            </div>
        </form>
    </div>
</div>
