<?php
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horarios – Panel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .day-label.active {
            background-color: var(--brand-color-soft) !important;
            border-color: var(--brand-color) !important;
            color: var(--brand-color) !important;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="flex flex-col md:flex-row h-screen overflow-hidden">

    <?php require 'Views/Admin/_sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-10 bg-slate-50">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 mt-4 md:mt-0">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Horarios</h1>
                <p class="text-gray-500 mt-1 font-medium italic">Configura los días y horas de atención</p>
            </div>
            <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                <div class="relative flex-1 md:flex-none">
                    <input type="text" id="filterEmpleado" onkeyup="filterTable()" 
                           placeholder="Filtrar por empleado..." 
                           class="w-full border rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-brand md:w-64 shadow-sm">
                </div>
                <button onclick="openModal('modalCrear')"
                        class="bg-brand text-white px-6 py-3 rounded-2xl font-bold transition-all hover:scale-[1.02] active:scale-[0.98] shadow-xl shadow-brand/20 flex items-center justify-center gap-2">
                    <span class="text-xl">+</span>
                    <span>Agregar horario</span>
                </button>
            </div>
        </div>


        <!-- Tabla -->
        <div class="bg-white rounded-2xl shadow overflow-hidden">
            <?php if (empty($horarios)): ?>
                <div class="text-center py-16 text-gray-400">
                    <p class="text-4xl mb-3">🕐</p>
                    <p>No hay horarios configurados aún.</p>
                </div>
            <?php else: ?>
                <table class="w-full text-sm text-left" id="tablaHorarios">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600">
                            <th class="p-4">Tipo / Empleado</th>
                            <th class="p-4">Día</th>
                            <th class="p-4">Hora inicio</th>
                            <th class="p-4">Hora fin</th>
                            <th class="p-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horarios as $h): ?>
                            <tr class="border-t hover:bg-gray-50 transition-colors horario-row">
                                <td class="p-4 empleado-col">
                                    <?php if ($h['empleado_id']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            👤 <?= htmlspecialchars($h['empleado_nombre']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            🏢 Horario General
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 font-medium"><?= htmlspecialchars($h['dia_semana']) ?></td>
                                <td class="p-4"><?= htmlspecialchars($h['hora_inicio']) ?></td>
                                <td class="p-4"><?= htmlspecialchars($h['hora_fin']) ?></td>
                                <td class="p-4 text-right space-x-2">
                                    <button onclick='openEditModal(<?= json_encode($h) ?>)'
                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                                        Editar
                                    </button>
                                    <a href="index.php?controller=admin&action=deleteHorario&id=<?= (int)$h['id'] ?>"
                                       onclick="return confirmDelete(this.href, '¿Eliminar horario?', '¿Seguro que quieres eliminar el horario de <?= htmlspecialchars(addslashes($h['dia_semana'])) ?>?')"
                                       class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                                        Eliminar
                                    </a>
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
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-xl font-semibold">Nuevo Horario</h3>
            <button onclick="closeModal('modalCrear')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="POST" action="index.php?controller=admin&action=storeHorario" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Día de la semana (Selecciona uno o más)</label>
                <div class="grid grid-cols-4 gap-2 mb-3">
                    <?php foreach ($dias as $d): ?>
                        <label class="day-label flex flex-col items-center justify-center p-2 border rounded-xl cursor-pointer hover:bg-gray-50 transition-all border-gray-200">
                            <input type="checkbox" name="dias[]" value="<?= $d ?>" class="hidden day-checkbox" onchange="this.parentElement.classList.toggle('active', this.checked)">
                            <span class="text-xs font-medium"><?= $d ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <!-- Botones rápidos -->
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="selectDays('semana')" 
                            class="text-[10px] uppercase tracking-wider font-bold px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors">
                        Lunes a Viernes
                    </button>
                    <button type="button" onclick="selectDays('finde')" 
                            class="text-[10px] uppercase tracking-wider font-bold px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors">
                        Sábado y Domingo
                    </button>
                    <button type="button" onclick="selectDays('todos')" 
                            class="text-[10px] uppercase tracking-wider font-bold px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors">
                        Todos
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Asignar a</label>
                <select name="empleado_id"
                           class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-brand outline-none">
                    <option value="">🏢 Horario General (Todo el negocio)</option>
                    <?php foreach ($empleados as $emp): ?>
                        <option value="<?= $emp['id'] ?>">👤 <?= htmlspecialchars($emp['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora inicio</label>
                    <input type="time" name="inicio" required
                           class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-400 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora fin</label>
                    <input type="time" name="fin" required
                           class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-400 outline-none">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('modalCrear')"
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

<!-- ── MODAL EDITAR ────────────────────────────────────────────────────── -->
<div id="modalEditar" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-xl font-semibold">Editar Horario</h3>
            <button onclick="closeModal('modalEditar')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="POST" action="index.php?controller=admin&action=updateHorario" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="edit_id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Día de la semana</label>
                <select name="dia" id="edit_dia" required
                           class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-brand outline-none">
                    <?php foreach ($dias as $d): ?>
                        <option value="<?= $d ?>"><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Asignar a</label>
                <select name="empleado_id" id="edit_empleado_id"
                           class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-brand outline-none">
                    <option value="">🏢 Horario General</option>
                    <?php foreach ($empleados as $emp): ?>
                        <option value="<?= $emp['id'] ?>">👤 <?= htmlspecialchars($emp['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora inicio</label>
                    <input type="time" name="inicio" id="edit_inicio" required
                           class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-400 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora fin</label>
                    <input type="time" name="fin" id="edit_fin" required
                           class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-400 outline-none">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('modalEditar')"
                        class="px-4 py-2 rounded-lg bg-gray-200 text-sm hover:bg-gray-300 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-lg bg-brand text-white text-sm font-medium hover:brightness-110 transition-colors">
                    Actualizar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden');    }

function openEditModal(h) {
    document.getElementById('edit_id').value = h.id;
    document.getElementById('edit_dia').value = h.dia_semana;
    document.getElementById('edit_empleado_id').value = h.empleado_id || '';
    document.getElementById('edit_inicio').value = h.hora_inicio;
    document.getElementById('edit_fin').value = h.hora_fin;
    openModal('modalEditar');
}

function filterTable() {
    const input = document.getElementById('filterEmpleado');
    const filter = input.value.toLowerCase();
    const rows = document.getElementsByClassName('horario-row');

    for (let row of rows) {
        const text = row.getElementsByClassName('empleado-col')[0].innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    }
}

function selectDays(type) {
    const checkboxes = document.querySelectorAll('.day-checkbox');
    const semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
    const finde = ['Sábado', 'Domingo'];

    checkboxes.forEach(cb => {
        if (type === 'semana') {
            cb.checked = semana.includes(cb.value);
        } else if (type === 'finde') {
            cb.checked = finde.includes(cb.value);
        } else if (type === 'todos') {
            cb.checked = true;
        }
        // Disparar el evento change manualmente para actualizar el estilo
        cb.parentElement.classList.toggle('active', cb.checked);
    });
}
</script>

</body>
</html>
