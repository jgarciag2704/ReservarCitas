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
    <title>Empleados – Panel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* Animación de entrada para modales */
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95) translateY(-8px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-content { animation: fadeInScale 0.2s ease-out; }

        /* Badge animado */
        @keyframes pulse-soft {
            0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
            50%       { box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
        }
        .badge-active { animation: pulse-soft 2.5s infinite; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="flex flex-col md:flex-row h-screen overflow-hidden">

    <?php require 'Views/Admin/_sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-10 bg-slate-50">

        <!-- ── Encabezado ──────────────────────────────────────────────────── -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 mt-4 md:mt-0">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Empleados</h1>
                <p class="text-gray-500 mt-1 font-medium italic">Gestiona el equipo de trabajo de tu negocio</p>
            </div>
            <button id="btnAgregarEmpleado"
                    onclick="openModalCrear()"
                    class="w-full md:w-auto bg-brand text-white px-6 py-3 rounded-2xl font-bold transition-all hover:scale-[1.02] shadow-xl shadow-brand/20 flex items-center justify-center gap-2">
                <span class="text-xl">+</span> Agregar empleado
            </button>
        </div>


        <!-- ── Tarjeta de estadísticas ──────────────────────────────────────── -->
        <?php
            $totalEmpleados  = count($empleados);
            $activos         = count(array_filter($empleados, fn($e) => $e['activo']));
            $inactivos       = $totalEmpleados - $activos;
        ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 shadow flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-2xl">👥</div>
                <div>
                    <p class="text-2xl font-bold text-gray-900"><?= $totalEmpleados ?></p>
                    <p class="text-xs text-gray-500 font-medium">Total empleados</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-2xl">✅</div>
                <div>
                    <p class="text-2xl font-bold text-green-600"><?= $activos ?></p>
                    <p class="text-xs text-gray-500 font-medium">Activos</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow flex items-center gap-4">
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-2xl">🚫</div>
                <div>
                    <p class="text-2xl font-bold text-red-500"><?= $inactivos ?></p>
                    <p class="text-xs text-gray-500 font-medium">Inactivos</p>
                </div>
            </div>
        </div>

        <!-- ── Tabla de empleados ────────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl shadow overflow-hidden">
            <?php if (empty($empleados)): ?>
                <div class="text-center py-20 text-gray-400">
                    <p class="text-5xl mb-4">👤</p>
                    <p class="text-lg font-medium">Aún no tienes empleados registrados</p>
                    <p class="text-sm mt-1">Haz clic en <strong>Agregar empleado</strong> para comenzar.</p>
                </div>
            <?php else: ?>
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
                            <th class="px-6 py-4">Empleado</th>
                            <th class="px-6 py-4">Contacto</th>
                            <th class="px-6 py-4">Servicios asignados</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $emp): ?>
                            <tr class="border-t border-gray-100 hover:bg-gray-50 transition-colors group">
                                <!-- Nombre + avatar inicial -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-brand flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                            <?= strtoupper(substr($emp['nombre'] ?? '?', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($emp['nombre']) ?></p>
                                            <p class="text-xs text-gray-400"><?= htmlspecialchars($emp['email']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <!-- Teléfono -->
                                <td class="px-6 py-4 text-gray-600">
                                    <?= $emp['telefono'] ? htmlspecialchars($emp['telefono']) : '<span class="text-gray-300 italic">Sin teléfono</span>' ?>
                                </td>
                                <!-- Servicios asignados -->
                                <td class="px-6 py-4">
                                    <?php
                                        $asignadosEmp = $serviciosAsignados[$emp['id']] ?? [];
                                        $hasServices  = false;
                                        foreach ($todosServicios as $sv) {
                                            if (in_array($sv['id'], $asignadosEmp)) { $hasServices = true; break; }
                                        }
                                    ?>
                                    <?php if ($hasServices): ?>
                                        <div class="flex flex-wrap gap-1">
                                        <?php foreach ($todosServicios as $sv): ?>
                                            <?php if (in_array($sv['id'], $asignadosEmp)): ?>
                                                <span class="inline-block bg-indigo-50 text-indigo-700 text-xs font-semibold px-2 py-0.5 rounded-full border border-indigo-100">
                                                    <?= htmlspecialchars($sv['nombre']) ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400 italic">Sin servicios asignados</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Badge estado -->
                                <td class="px-6 py-4">
                                    <?php if ($emp['activo']): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 badge-active">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                                            Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span>
                                            Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <!-- Acciones -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <!-- Servicios -->
                                        <button onclick='openModalServicios(<?= (int)$emp["id"] ?>, "<?= htmlspecialchars(addslashes($emp["nombre"])) ?>")'
                                                class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                                title="Asignar servicios">
                                            🛠 Servicios
                                        </button>
                                        <!-- Editar -->
                                        <button onclick='openModalEditar(<?= htmlspecialchars(json_encode($emp), ENT_QUOTES) ?>)'
                                                class="bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                                title="Editar empleado">
                                            ✏️ Editar
                                        </button>
                                        <!-- Toggle activo/inactivo -->
                                        <a href="index.php?controller=employee&action=toggleActivo&id=<?= (int)$emp['id'] ?>"
                                           class="<?= $emp['activo'] ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-500 hover:bg-green-600' ?> text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                           title="<?= $emp['activo'] ? 'Desactivar' : 'Activar' ?>">
                                            <?= $emp['activo'] ? '🚫 Desactivar' : '▶️ Activar' ?>
                                        </a>
                                        <!-- Eliminar -->
                                        <a href="index.php?controller=employee&action=delete&id=<?= (int)$emp['id'] ?>"
                                           onclick="return confirmDelete(this.href, '¿Eliminar empleado?', '¿Seguro que quieres eliminar permanentemente a <?= htmlspecialchars(addslashes($emp['nombre'])) ?>?')"
                                           class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                           title="Eliminar empleado">
                                            🗑️ Eliminar
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

<!-- ── MODAL CREAR EMPLEADO ──────────────────────────────────────────────── -->
<div id="modalCrear" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md modal-content">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">👤 Nuevo Empleado</h3>
            <button onclick="closeModalCrear()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <form method="POST" action="index.php?controller=employee&action=store" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nombre completo <span class="text-red-500">*</span></label>
                <input type="text" name="nombre" id="crear_nombre" required placeholder="Ej. María García"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-soft focus:border-brand outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Correo electrónico <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="crear_email" required placeholder="maria@ejemplo.com"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-soft focus:border-brand outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Teléfono</label>
                <input type="tel" name="telefono" id="crear_telefono" placeholder="Ej. 555-1234"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-soft focus:border-brand outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Contraseña <span class="text-red-500">*</span></label>
                <input type="password" name="password" id="crear_password" required minlength="6" placeholder="Mínimo 6 caracteres"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-soft focus:border-brand outline-none transition-all">
                <p class="text-xs text-gray-400 mt-1">El empleado usará este correo y contraseña para iniciar sesión.</p>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModalCrear()"
                        class="px-5 py-2.5 rounded-xl bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-brand text-white text-sm font-semibold hover:brightness-110 transition-all shadow-md">
                    Crear empleado
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL EDITAR EMPLEADO ─────────────────────────────────────────────── -->
<div id="modalEditar" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md modal-content">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">✏️ Editar Empleado</h3>
            <button onclick="closeModalEditar()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <form method="POST" action="index.php?controller=employee&action=update" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="edit_id">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nombre completo <span class="text-red-500">*</span></label>
                <input type="text" name="nombre" id="edit_nombre" required
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-soft focus:border-brand outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Correo electrónico <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="edit_email" required
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-soft focus:border-brand outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Teléfono</label>
                <input type="tel" name="telefono" id="edit_telefono"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-soft focus:border-brand outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nueva contraseña <span class="text-gray-400 font-normal">(dejar vacío para no cambiar)</span></label>
                <input type="password" name="password" id="edit_password" minlength="6" placeholder="••••••"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-soft focus:border-brand outline-none transition-all">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModalEditar()"
                        class="px-5 py-2.5 rounded-xl bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-green-600 text-white text-sm font-semibold hover:bg-green-700 transition-colors shadow-md">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Modal Crear ──────────────────────────────────────────────────────────
function openModalCrear() {
    document.getElementById('modalCrear').classList.remove('hidden');
}
function closeModalCrear() {
    document.getElementById('modalCrear').classList.add('hidden');
    document.getElementById('modalCrear').querySelector('form').reset();
}

// ── Modal Editar ─────────────────────────────────────────────────────────
function openModalEditar(data) {
    document.getElementById('edit_id').value       = data.id;
    document.getElementById('edit_nombre').value   = data.nombre;
    document.getElementById('edit_email').value    = data.email;
    document.getElementById('edit_telefono').value = data.telefono ?? '';
    document.getElementById('edit_password').value = '';
    document.getElementById('modalEditar').classList.remove('hidden');
}
function closeModalEditar() {
    document.getElementById('modalEditar').classList.add('hidden');
}

// ── Modal Servicios ───────────────────────────────────────────────────────
const todosServicios     = <?= json_encode(array_values($todosServicios ?? [])) ?>;
const serviciosAsignados = <?= json_encode($serviciosAsignados ?? []) ?>;

function openModalServicios(empId, empNombre) {
    document.getElementById('serv_empleado_id').value = empId;
    document.getElementById('serv_titulo').textContent = empNombre;

    const container = document.getElementById('serv_contenedor');
    container.innerHTML = '';

    if (!todosServicios.length) {
        container.innerHTML = '<p class="text-gray-400 text-sm text-center py-6">No hay servicios configurados.<br><a href="index.php?controller=admin&action=servicios" class="text-indigo-500 font-semibold">Crear servicios →</a></p>';
    } else {
        const asignados = (serviciosAsignados[empId] || []).map(Number);
        todosServicios.forEach(srv => {
            const checked = asignados.includes(Number(srv.id)) ? 'checked' : '';
            container.innerHTML += `
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 cursor-pointer transition-colors">
                    <input type="checkbox" name="servicio_ids[]" value="${srv.id}" ${checked}
                           class="w-4 h-4 rounded accent-indigo-600">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">${srv.nombre}</p>
                        <p class="text-xs text-slate-400">${srv.duracion} min · $${parseFloat(srv.precio).toFixed(2)}</p>
                    </div>
                </label>
            `;
        });
    }

    document.getElementById('modalServicios').classList.remove('hidden');
}
function closeModalServicios() {
    document.getElementById('modalServicios').classList.add('hidden');
}

// ── Cerrar modales al clic fuera ──────────────────────────────────────────
['modalCrear','modalEditar','modalServicios'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});

// ── Abrir modal si hubo error en el formulario ────────────────────────────
<?php if ($error): ?>
    openModalCrear();
<?php endif; ?>
</script>

</body>
</html>

<!-- ── MODAL ASIGNAR SERVICIOS AL EMPLEADO ──────────────────────────────── -->
<div id="modalServicios" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md modal-content">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">🛠 Servicios que ofrece</h3>
                <p id="serv_titulo" class="text-sm text-gray-500 mt-0.5"></p>
            </div>
            <button onclick="closeModalServicios()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <form method="POST" action="index.php?controller=employee&action=asignarServicios">
            <?= csrf_field() ?>
            <input type="hidden" name="empleado_id" id="serv_empleado_id">
            <div id="serv_contenedor" class="px-6 py-4 space-y-2 max-h-72 overflow-y-auto">
                <!-- Servicios generados por JS -->
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" onclick="closeModalServicios()"
                        class="px-5 py-2.5 rounded-xl bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-md">
                    Guardar servicios
                </button>
            </div>
        </form>
    </div>
</div>
