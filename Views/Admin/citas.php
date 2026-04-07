<?php
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$estadosValidos = ['pendiente', 'confirmada', 'cancelada', 'completada'];
$badgeClasses   = [
    'pendiente'  => 'bg-yellow-100 text-yellow-800',
    'confirmada' => 'bg-brand-soft text-brand',
    'completada' => 'bg-green-100  text-green-800',
    'cancelada'  => 'bg-red-100    text-red-800',
    'no_llego'   => 'bg-slate-200  text-slate-700',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citas – Panel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="flex flex-col md:flex-row h-screen overflow-hidden">

    <?php require 'Views/Admin/_sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-10 bg-slate-50">

        <div class="mb-8 mt-4 md:mt-0">
            <h1 class="text-3xl font-bold text-gray-900">Citas</h1>
            <p class="text-gray-500 mt-1">Gestiona todas las citas de tu negocio</p>
        </div>


        <!-- Tabla de citas -->
        <div class="bg-white rounded-2xl shadow overflow-hidden">
            <?php if (empty($citas)): ?>
                <div class="text-center py-16 text-gray-400">
                    <p class="text-4xl mb-3">📅</p>
                    <p>No hay citas registradas aún.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600">
                                <th class="p-4">Fecha</th>
                                <th class="p-4">Hora</th>
                                <th class="p-4">Cliente</th>
                                <th class="p-4">Teléfono</th>
                                <th class="p-4">Servicio</th>
                                <th class="p-4">Especialista</th>
                                <th class="p-4">Estado</th>
                                <th class="p-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($citas as $c): ?>
                                <?php
                                    $estado = $c['estado'] ?? 'pendiente';
                                    $badge  = $badgeClasses[$estado] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <tr class="border-t hover:bg-gray-50 transition-colors">
                                    <td class="p-4"><?= htmlspecialchars($c['fecha']) ?></td>
                                    <td class="p-4 font-medium"><?= htmlspecialchars($c['hora']) ?></td>
                                    <td class="p-4"><?= htmlspecialchars($c['nombre_cliente']) ?></td>
                                    <td class="p-4"><?= htmlspecialchars($c['telefono']) ?></td>
                                    <td class="p-4"><?= htmlspecialchars($c['servicio_nombre'] ?? '—') ?></td>
                                    <td class="p-4">
                                        <?php if (!empty($c['empleado_nombre'])): ?>
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-full">
                                                👤 <?= htmlspecialchars($c['empleado_nombre']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $badge ?>">
                                            <?= ucfirst($estado) ?>
                                        </span>
                                    </td>

                                    <td class="p-4 text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <!-- ACEPTAR -->
                                            <?php if($estado === 'pendiente'): ?>
                                            <button onclick="gestionarCita(<?= (int)$c['id'] ?>, 'confirmada', '<?= htmlspecialchars($c['telefono']) ?>', '<?= rawurlencode($c['nombre_cliente']) ?>', '<?= $c['fecha'] ?>', '<?= $c['hora'] ?>', 'aceptar')" 
                                                    class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all shadow-sm" title="Aceptar y enviar WhatsApp">
                                                 Aceptar
                                            </button>
                                            <button onclick="gestionarCita(<?= (int)$c['id'] ?>, 'cancelada', '<?= htmlspecialchars($c['telefono']) ?>', '<?= rawurlencode($c['nombre_cliente']) ?>', '<?= $c['fecha'] ?>', '<?= $c['hora'] ?>', 'rechazar')" 
                                                    class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all shadow-sm" title="Rechazar y avisar por WhatsApp">
                                                Rechazar
                                            </button>
                                        <?php endif; ?>

                                        <!-- COMPLETAR -->
                                        <?php if($estado === 'confirmada'): ?>
                                            <button onclick="gestionarCita(<?= (int)$c['id'] ?>, 'completada')" 
                                                    class="inline-flex items-center gap-1.5 bg-indigo-500 hover:bg-indigo-600 text-white px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all shadow-sm">
                                                🏁 Completar
                                            </button>
                                            <button onclick="gestionarCita(<?= (int)$c['id'] ?>, 'no_llego')" 
                                                    class="inline-flex items-center gap-1.5 bg-slate-400 hover:bg-slate-500 text-white px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all shadow-sm" title="Marcar como inasistencia">
                                                🚫 No llegó
                                            </button>
                                        <?php endif; ?>

                                        <!-- ELIMINAR (SIEMPRE DISPONIBLE) -->
                                        <a href="index.php?controller=admin&action=deleteCita&id=<?= (int)$c['id'] ?>"
                                           onclick="return confirmDelete(this.href, '¿Eliminar cita?', 'Esta cita se borrará permanentemente del sistema.')"
                                           class="inline-flex items-center justify-center w-8 h-8 bg-rose-50 hover:bg-rose-500 text-rose-500 hover:text-white rounded-lg transition-all" title="Eliminar del sistema">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
function gestionarCita(id, estado, telefono = '', nombre = '', fecha = '', hora = '', accion = '') {
    const csrfToken = '<?= $_SESSION['csrf_token'] ?>';
    const formData = new FormData();
    formData.append('id', id);
    formData.append('estado', estado);
    formData.append('csrf_token', csrfToken);

    fetch('index.php?controller=admin&action=updateStatus', {
        method: 'POST',
        body: formData,
        headers: { 
            'X-Requested-With': 'XMLHttpRequest' 
        }
    })
    .then(async response => {
        const text = await response.text();
        if (!response.ok) {
            throw new Error(`Error del servidor (${response.status}): ${text}`);
        }
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Error de parseo JSON:', text);
            throw new Error('La respuesta del servidor no es un JSON válido');
        }
    })
    .then(data => {
        if (data.success) {
            // Si hay que enviar WhatsApp
            if (accion !== '') {
                enviarWhatsApp(telefono, nombre, fecha, hora, accion);
            }
            // Recargar la página para ver cambios
            location.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error al actualizar la cita' });
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: error.message });
    });
}

function enviarWhatsApp(telefono, nombre, fecha, hora, accion) {
    // Decodificar el nombre (rawurlencode usa %20 para espacios)
    nombre = decodeURIComponent(nombre.replace(/\+/g, ' '));
    let msg = "";
    
    if (accion === 'aceptar') {
        msg = `Hola ${nombre}, tu cita para el día ${fecha} a las ${hora} ha sido *Confirmada*. ¡Te esperamos!`;
    } else if (accion === 'rechazar') {
        msg = `Hola ${nombre}, lamentamos informarte que por motivos de agenda no podremos atenderte el ${fecha} a las ${hora}. Por favor, selecciona otro horario en nuestro portal.`;
    }

    if (msg !== "") {
        const limpiaTel = telefono.replace(/[^0-9]/g, "");
        const waUrl = `https://wa.me/${limpiaTel}?text=${encodeURIComponent(msg)}`;
        window.open(waUrl, '_blank');
    }
}
</script>

</body>
</html>
