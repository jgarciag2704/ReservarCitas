<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorios – Panel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
    </style>
</head>
<body class="text-slate-800 antialiased">

<div class="flex flex-col md:flex-row h-screen overflow-hidden">
    <?php require 'Views/Admin/_sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-10 bg-gradient-to-br from-slate-50 to-indigo-50/20">
        
        <div class="mb-10 mt-4 md:mt-0">
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-2">Recordatorios de WhatsApp</h1>
            <p class="text-slate-500 font-medium">Envía avisos rápidos a tus clientes para las citas de mañana</p>
        </div>

        <div class="glass-panel rounded-3xl shadow-sm overflow-hidden border border-slate-200/60">
            <div class="p-6 border-b bg-slate-50/50 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-3">
                    <span class="text-brand">📅</span> Citas de Mañana: <?= date('d/m/Y', strtotime('+1 day')) ?>
                </h2>
                <span class="bg-brand-soft text-brand px-3 py-1 rounded-lg text-xs font-bold tracking-wider uppercase">
                    <?= count($citas) ?> Pendientes
                </span>
            </div>

            <?php if (empty($citas)): ?>
                <div class="p-16 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                         <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-700">No hay citas para recordar</h3>
                    <p class="text-slate-500 text-sm">Mañana parece ser un día tranquilo.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                            <tr>
                                <th class="px-8 py-4">Hora</th>
                                <th class="px-8 py-4">Cliente</th>
                                <th class="px-8 py-4">Servicio</th>
                                <th class="px-8 py-4 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white/50">
                            <?php foreach ($citas as $c): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-8 py-5">
                                        <span class="font-black text-slate-700"><?= htmlspecialchars($c['hora']) ?></span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <span class="font-bold text-slate-900"><?= htmlspecialchars($c['nombre_cliente']) ?></span>
                                    </td>
                                    <td class="px-8 py-5 text-slate-500 font-medium">
                                        <?= htmlspecialchars($c['servicio_nombre'] ?? '—') ?>
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <?php 
                                            $negocioNombre = $this->negocioActual['nombre'] ?? 'nuestro negocio';
                                            $msg = "¡Hola! Te recordamos tu cita de mañana a las {$c['hora']} para el servicio de *{$c['servicio_nombre']}* en *$negocioNombre*. ¡Te esperamos!";
                                            $waUrl = "https://wa.me/" . preg_replace('/[^0-9]/', '', $c['telefono']) . "?text=" . urlencode($msg);
                                        ?>
                                        <a href="<?= $waUrl ?>" target="_blank"
                                           class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2 rounded-xl text-xs font-extrabold shadow-lg shadow-emerald-100 transition-all hover:scale-105 active:scale-95">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                            Recordar vía WhatsApp
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

</body>
</html>
