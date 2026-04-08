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
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 transition-transform hover:scale-110 duration-300">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900"><?= $totalEmpleados ?></p>
                    <p class="text-xs text-gray-500 font-medium">Total empleados</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-green-600 transition-transform hover:scale-110 duration-300">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-green-600"><?= $activos ?></p>
                    <p class="text-xs text-gray-500 font-medium">Activos</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow flex items-center gap-4">
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-red-500 transition-transform hover:scale-110 duration-300">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
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
                    <p class="text-5xl mb-4 flex justify-center text-gray-300">
                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </p>
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
                                            <?php if (!empty($emp['especialidad'])): ?>
                                                <p class="text-xs text-indigo-600 font-bold"><?= htmlspecialchars($emp['especialidad']) ?></p>
                                            <?php endif; ?>
                                            <p class="text-[10px] text-gray-400"><?= htmlspecialchars($emp['email']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <!-- Teléfono -->
                                <td class="px-6 py-4 text-gray-600">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm"><?= $emp['telefono'] ? htmlspecialchars($emp['telefono']) : '<span class="text-gray-300 italic">Sin teléfono</span>' ?></span>
                                        <?php if (!empty($emp['google_maps'])): ?>
                                            <a href="<?= htmlspecialchars($emp['google_maps']) ?>" target="_blank" class="text-[10px] text-blue-500 hover:underline flex items-center gap-1">
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                Ver ubicación
                                            </a>
                                        <?php endif; ?>
                                    </div>
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
                                                class="flex items-center gap-1 bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                                title="Asignar servicios">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            Servicios
                                        </button>
                                        <!-- Editar -->
                                        <button onclick='openModalEditar(<?= htmlspecialchars(json_encode($emp), ENT_QUOTES) ?>)'
                                                class="flex items-center gap-1 bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                                title="Editar empleado">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            Editar
                                        </button>
                                        <!-- Toggle activo/inactivo -->
                                        <a href="index.php?controller=employee&action=toggleActivo&id=<?= (int)$emp['id'] ?>"
                                           class="flex items-center gap-1 <?= $emp['activo'] ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-500 hover:bg-green-600' ?> text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                           title="<?= $emp['activo'] ? 'Desactivar' : 'Activar' ?>">
                                            <?php if ($emp['activo']): ?>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                Desactivar
                                            <?php else: ?>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                Activar
                                            <?php endif; ?>
                                        </a>
                                        <!-- Eliminar -->
                                        <a href="index.php?controller=employee&action=delete&id=<?= (int)$emp['id'] ?>"
                                           onclick="return confirmDelete(this.href, '¿Eliminar empleado?', '¿Seguro que quieres eliminar permanentemente a <?= htmlspecialchars(addslashes($emp['nombre'])) ?>?')"
                                           class="flex items-center gap-1 bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                           title="Eliminar empleado">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
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

<!-- ── MODAL CREAR EMPLEADO ──────────────────────────────────────────────── -->
<div id="modalCrear" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md modal-content">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Nuevo Empleado
            </h3>
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
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Especialidad</label>
                    <input type="text" name="especialidad" id="crear_especialidad" placeholder="Ej. Cardiología"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-soft focus:border-brand outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Experiencia</label>
                    <input type="text" name="experiencia" id="crear_experiencia" placeholder="Ej. 10 años"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-soft focus:border-brand outline-none transition-all">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Link de ubicación (Google Maps)</label>
                <input type="url" name="google_maps" id="crear_google_maps" placeholder="https://goo.gl/maps/..."
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
            <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Editar Empleado
            </h3>
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
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Especialidad</label>
                    <input type="text" name="especialidad" id="edit_especialidad" placeholder="Ej. Cardiología"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-soft focus:border-brand outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Experiencia</label>
                    <input type="text" name="experiencia" id="edit_experiencia" placeholder="Ej. 10 años"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-soft focus:border-brand outline-none transition-all">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Link de ubicación (Google Maps)</label>
                <input type="url" name="google_maps" id="edit_google_maps" placeholder="https://goo.gl/maps/..."
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
    document.getElementById('edit_id').value           = data.id;
    document.getElementById('edit_nombre').value       = data.nombre;
    document.getElementById('edit_email').value        = data.email;
    document.getElementById('edit_telefono').value     = data.telefono     ?? '';
    document.getElementById('edit_especialidad').value = data.especialidad ?? '';
    document.getElementById('edit_experiencia').value  = data.experiencia  ?? '';
    document.getElementById('edit_google_maps').value  = data.google_maps  ?? '';
    document.getElementById('edit_password').value     = '';
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
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Servicios que ofrece
                </h3>
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
