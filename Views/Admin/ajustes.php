<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajustes de Negocio – Panel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
    </style>
</head>
<body class="text-slate-800 antialiased selection:bg-indigo-100">

<div class="flex flex-col md:flex-row h-screen overflow-hidden">
    <?php require 'Views/Admin/_sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-10 bg-gradient-to-br from-slate-50 via-slate-50 to-indigo-50/20">

        <div class="mb-10 mt-4 md:mt-0">
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-2">Ajustes de Negocio</h1>
            <p class="text-slate-500 font-medium">Personaliza tu perfil público y marca</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-xl mb-8 flex items-center gap-3 shadow-sm font-medium">
                <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-5 py-4 rounded-xl mb-8 flex items-center gap-3 shadow-sm font-medium">
                <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="max-w-3xl">
            <form action="index.php?controller=admin&action=updateAjustes" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 gap-8">
                <?= csrf_field() ?>

                <div class="glass-panel p-8 rounded-3xl shadow-sm border border-slate-200/60 transition-all hover:shadow-md">
                    <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                        <span class="text-brand">🎨</span> Identidad y Marca
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider">Nombre del Negocio</label>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($negocio['nombre']) ?>" required
                                   class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl focus:ring-4 focus:ring-brand-soft focus:border-brand transition-all outline-none">
                        </div>

                        <!-- Slug -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider">URL Pública (Slug)</label>
                            <div class="flex items-center gap-2">
                                <span class="text-slate-400 text-sm font-medium">.../slug=</span>
                                <input type="text" name="slug" value="<?= htmlspecialchars($negocio['slug']) ?>" required
                                       class="flex-1 bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl focus:ring-4 focus:ring-brand-soft focus:border-brand transition-all outline-none">
                            </div>
                        </div>

                        <!-- Color -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider">Color de Marca</label>
                            <div class="flex items-center gap-4">
                                <input type="color" name="color" value="<?= htmlspecialchars($negocio['color'] ?? '#6366f1') ?>"
                                       class="h-12 w-20 bg-white border border-slate-200 rounded-lg cursor-pointer">
                                <span class="text-slate-500 text-sm font-medium">Elige tu color corporativo</span>
                            </div>
                        </div>

                        <!-- Logo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider">Logo / Avatar</label>
                            <div class="flex items-center gap-4">
                                <?php if($negocio['logo']): ?>
                                    <img src="<?= $negocio['logo'] ?>" class="w-12 h-12 object-cover rounded-xl shadow-sm border" alt="Logo actual">
                                <?php endif; ?>
                                <input type="file" name="logo" accept="image/*"
                                       class="flex-1 text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-brand-soft file:text-brand hover:file:bg-brand hover:file:text-white transition-all cursor-pointer">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Capacidad y Aforo -->
                <div class="glass-panel p-8 rounded-3xl shadow-sm border border-slate-200/60 transition-all hover:shadow-md">
                    <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                        <span class="text-brand">🏢</span> Configuración de Reservas y Aforo
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider">Modo de Reserva</label>
                            <select id="tipo_reserva" name="tipo_reserva" onchange="toggleCapacidadFields()" class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl focus:ring-4 focus:ring-brand-soft focus:border-brand transition-all outline-none">
                                <option value="individual" <?= ($negocio['tipo_reserva'] ?? 'individual') === 'individual' ? 'selected' : '' ?>>Especialistas individuales (Agendas)</option>
                                <option value="capacidad" <?= ($negocio['tipo_reserva'] ?? 'individual') === 'capacidad' ? 'selected' : '' ?>>Restaurante / Aforo (Mesas)</option>
                            </select>
                        </div>
                        
                        <div id="capacidad_fields" class="contents">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider">Mesas Totales del Local</label>
                                <input type="number" min="1" name="cantidad_mesas" value="<?= htmlspecialchars($negocio['cantidad_mesas'] ?? 1) ?>" class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl focus:ring-4 focus:ring-brand-soft focus:border-brand transition-all outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider">Sillas p/Mesa Estandar</label>
                                <input type="number" min="1" name="sillas_por_mesa" value="<?= htmlspecialchars($negocio['sillas_por_mesa'] ?? 4) ?>" class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl focus:ring-4 focus:ring-brand-soft focus:border-brand transition-all outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider">% Máximo para Online</label>
                                <input type="number" min="1" max="100" name="porcentaje_online" value="<?= htmlspecialchars($negocio['porcentaje_online'] ?? 100) ?>" class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl focus:ring-4 focus:ring-brand-soft focus:border-brand transition-all outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider">Tiempo de Gracia (No-show min)</label>
                                <input type="number" min="0" name="tiempo_gracia" value="<?= htmlspecialchars($negocio['tiempo_gracia'] ?? 15) ?>" class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl focus:ring-4 focus:ring-brand-soft focus:border-brand transition-all outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Card (Visual) -->
                <div class="bg-brand p-8 rounded-3xl shadow-xl shadow-brand-soft text-white relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110"></div>
                    <h4 class="text-xl font-bold mb-2">¡Haz brillar tu marca!</h4>
                    <p class="text-indigo-50/80 text-sm mb-6 leading-relaxed">Los cambios que realices aquí se aplicarán instantáneamente tanto en tu panel administrativo como en el portal de citas para tus clientes.</p>
                    <button type="submit" class="bg-white text-brand px-8 py-3.5 rounded-2xl font-extrabold text-sm hover:scale-105 transition-transform shadow-lg">
                        Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
    function toggleCapacidadFields() {
        const modo = document.getElementById('tipo_reserva').value;
        const panel = document.getElementById('capacidad_fields');
        if (modo === 'capacidad') {
            panel.style.display = ''; // Usamos grid, si está visible usa su display natural
            panel.classList.remove('hidden');
        } else {
            panel.style.display = 'none';
            panel.classList.add('hidden');
        }
    }
    document.addEventListener('DOMContentLoaded', toggleCapacidadFields);
</script>

</body>
</html>
