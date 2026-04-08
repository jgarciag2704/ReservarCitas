<?php
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);
$admin = $_SESSION['user'];

// Obtener detalles del cliente para el link público
$linkPublico = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?controller=cliente&action=index&slug=" . ($negocioActual['slug'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Panel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .stat-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        .glass-panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
    </style>
</head>
<body class="text-slate-800 antialiased selection:bg-indigo-100 selection:text-indigo-900">

<div class="flex flex-col md:flex-row h-screen overflow-hidden">

    <?php require 'Views/Admin/_sidebar.php'; ?>

    <!-- MAIN CON GRADIENTE DE FONDO MUY SUTIL -->
    <main class="flex-1 overflow-y-auto p-4 md:p-10 bg-gradient-to-br from-slate-50 via-slate-50 to-indigo-50/30">

        <!-- Cabecera Premium -->
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4 mt-4 md:mt-0">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mb-2">
                    Hola, <span class="text-brand"><?= htmlspecialchars($admin['nombre'] ?? 'Admin') ?></span> 👋
                </h1>
                <p class="text-slate-500 font-medium flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Resumen de tu negocio para el <?= date('d M, Y') ?>
                </p>
            </div>
            
            <!-- Botón Copiar Link -->
            <?php if(!empty($negocioActual['slug'])): ?>
            <button onclick="copiarLink('<?= $linkPublico ?>', this)" class="group flex items-center gap-2 bg-white border border-slate-200 hover:border-brand text-slate-700 hover:text-brand px-5 py-2.5 rounded-xl font-semibold text-sm shadow-sm hover:shadow-md transition-all">
                <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                <span>Copiar mi link de citas</span>
            </button>
            <?php endif; ?>
        </div>

        <!-- Alertas -->
        <?php if ($success): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-xl mb-8 flex items-center gap-3 shadow-sm font-medium">
                <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-5 py-4 rounded-xl mb-8 flex items-center gap-3 shadow-sm font-medium">
                <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Panel de Mesas en Vivo (Solo Capacidad/Restaurantes) -->
        <?php if (!empty($esCapacidad) && $esCapacidad): ?>
        <div class="mb-10 bg-gradient-to-br from-slate-900 to-indigo-900 rounded-3xl p-8 shadow-xl text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-bl-[100px] -mr-16 -mt-16 z-0 pointer-events-none"></div>
            
            <div class="relative z-10 grid grid-cols-1 md:grid-cols-3 gap-8 items-center">
                
                <!-- Estado Actual en Vivo -->
                <div class="md:col-span-1 flex flex-col justify-center border-b md:border-b-0 md:border-r border-white/10 pb-6 md:pb-0 md:pr-6">
                    <h3 class="text-indigo-200 font-bold mb-2 tracking-widest text-[10px] uppercase">🔴 Monitor en Tiempo Real</h3>
                    <div class="flex items-baseline gap-2">
                        <span class="text-7xl font-extrabold text-white tracking-tighter"><?= $mesasActualesLibres ?></span>
                        <span class="text-2xl font-bold text-slate-400">/ <?= $mesasTotales ?></span>
                    </div>
                    <p class="mt-1 text-slate-300 font-medium text-sm">Mesas Disponibles Ahora</p>
                </div>
                
                <!-- Disponibilidad por Hora (Timeline) -->
                <div class="md:col-span-2">
                    <h3 class="text-indigo-200 font-bold mb-4 tracking-widest text-[10px] uppercase text-center md:text-left">Disponibilidad del Día (Por Hora)</h3>
                    
                    <?php if (empty($mesasOcupadasHoy)): ?>
                        <div class="bg-white/5 rounded-xl p-4 border border-white/10 text-center">
                            <p class="text-slate-400 text-sm">El local se encuentra fuera de horario de servicio según la configuración actual.</p>
                        </div>
                    <?php else: ?>
                        <!-- Usamos scroll snap para navegación táctil fácil en móviles -->
                        <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory">
                            <?php foreach ($mesasOcupadasHoy as $h): 
                                // Color semaforizado
                                $colorCls = 'text-emerald-400';
                                if ($h['libres'] === 0) {
                                    $colorCls = 'text-rose-400';
                                } elseif ($h['libres'] <= ceil($mesasTotales * 0.2)) {
                                    // Menos del 20%
                                    $colorCls = 'text-amber-400';
                                }
                            ?>
                                <div class="flex-shrink-0 snap-start bg-white/10 backdrop-blur-md rounded-2xl p-4 w-28 text-center border border-white/10 hover:bg-white/20 transition-colors shadow-inner">
                                    <p class="text-sm font-bold text-slate-100 mb-2"><?= $h['hora'] ?></p>
                                    <div class="text-3xl font-black mb-1 <?= $colorCls ?>"><?= $h['libres'] ?></div>
                                    <p class="text-[10px] text-slate-300 uppercase tracking-widest font-semibold flex items-center justify-center gap-1">
                                        🪑 Libres
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php endif; ?>

        <!-- Tarjetas de estadísticas Glassmorphic -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

            <a href="index.php?controller=admin&action=servicios" class="stat-card glass-panel rounded-3xl p-6 flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110 z-0"></div>
                <div class="relative z-10 flex justify-between items-start mb-4">
                    <div class="p-3 bg-brand rounded-2xl shadow-lg shadow-brand-soft text-white">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"></path></svg>
                    </div>
                </div>
                <div class="relative z-10">
                    <h3 class="text-4xl font-extrabold text-slate-800 mb-1"><?= $totalServicios ?></h3>
                    <p class="text-slate-500 font-medium text-sm text-transform: uppercase tracking-wider">Servicios Activos</p>
                </div>
            </a>

            <a href="index.php?controller=admin&action=horarios" class="stat-card glass-panel rounded-3xl p-6 flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-purple-50 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110 z-0"></div>
                <div class="relative z-10 flex justify-between items-start mb-4">
                    <div class="p-3 bg-gradient-to-br from-purple-500 to-fuchsia-600 rounded-2xl shadow-lg shadow-purple-200 text-white">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="relative z-10">
                    <h3 class="text-4xl font-extrabold text-slate-800 mb-1"><?= $totalHorarios ?></h3>
                    <p class="text-slate-500 font-medium text-sm text-transform: uppercase tracking-wider">Horarios Definidos</p>
                </div>
            </a>

            <a href="index.php?controller=admin&action=citas" class="stat-card glass-panel rounded-3xl p-6 flex flex-col justify-between relative overflow-hidden group">
                 <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-50 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110 z-0"></div>
                <div class="relative z-10 flex justify-between items-start mb-4">
                    <div class="p-3 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl shadow-lg shadow-teal-200 text-white">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                </div>
                <div class="relative z-10">
                    <h3 class="text-4xl font-extrabold text-slate-800 mb-1"><?= $totalCitas ?></h3>
                    <p class="text-slate-500 font-medium text-sm text-transform: uppercase tracking-wider">Citas Históricas</p>
                </div>
            </a>
        </div>

        <!-- ── GRÁFICOS ───────────────────────────────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
            <!-- Gráfico 1: Citas esta semana -->
            <div class="glass-panel p-8 rounded-3xl shadow-sm">
                <h4 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <span class="text-brand">📈</span> Tendencia de Citas (7 días)
                </h4>
                <div class="h-64">
                    <canvas id="chartSemana"></canvas>
                </div>
            </div>
            
            <!-- Gráfico 2: Servicios más populares -->
            <div class="glass-panel p-8 rounded-3xl shadow-sm">
                <h4 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <span class="text-brand">📊</span> Servicios más pedidos
                </h4>
                <div class="h-64">
                    <canvas id="chartServicios"></canvas>
                </div>
            </div>
        </div>

        <!-- Citas de hoy Premium -->
        <div class="glass-panel rounded-3xl shadow-sm p-8 border hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                    <span class="bg-brand-soft text-brand p-2 rounded-lg">🗓️</span>
                    Citas Programadas para Hoy
                </h2>
                <a href="index.php?controller=admin&action=citas" class="text-sm font-semibold text-brand hover:underline transition-colors">Ver todas &rarr;</a>
            </div>

            <?php if (empty($citasHoy)): ?>
                <div class="bg-slate-50 border border-dashed border-slate-300 rounded-2xl p-12 text-center">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm text-slate-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-700 mb-1">Día libre</h3>
                    <p class="text-slate-500 text-sm max-w-sm mx-auto">No tienes ninguna cita programada para el día de hoy. ¡Aprovecha para configurar tus servicios!</p>
                </div>
            <?php else: ?>
                <div class="overflow-hidden rounded-2xl border border-slate-200/60 shadow-sm">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50/80 backdrop-blur-sm border-b border-slate-200/60">
                            <tr>
                                <th class="px-6 py-4 font-semibold text-slate-600">Hora</th>
                                <th class="px-6 py-4 font-semibold text-slate-600">Cliente</th>
                                <th class="px-6 py-4 font-semibold text-slate-600">Servicio</th>
                                <th class="px-6 py-4 font-semibold text-slate-600 text-center">Contacto</th>
                                <th class="px-6 py-4 font-semibold text-slate-600">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php foreach ($citasHoy as $c): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-700 px-3 py-1.5 rounded-lg font-bold text-xs">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <?= htmlspecialchars($c['hora']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-100 to-purple-100 text-indigo-700 font-bold flex items-center justify-center text-xs">
                                                <?= strtoupper(substr($c['nombre_cliente'], 0, 1)) ?>
                                            </div>
                                            <div class="font-semibold text-slate-800"><?= htmlspecialchars($c['nombre_cliente']) ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 font-medium"><?= htmlspecialchars($c['servicio_nombre'] ?? '—') ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if(!empty($c['telefono'])): ?>
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $c['telefono']) ?>?text=<?= urlencode("Hola {$c['nombre_cliente']}, te contacto por tu cita de hoy a las {$c['hora']}.") ?>" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-50 text-green-600 hover:bg-green-500 hover:text-white transition-colors" title="Enviar WhatsApp a <?= htmlspecialchars($c['telefono']) ?>">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-slate-300">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                            $badges = [
                                                'pendiente'  => 'bg-amber-100/80 text-amber-700 border border-amber-200',
                                                'confirmada' => 'bg-indigo-100/80 text-indigo-700 border border-indigo-200',
                                                'completada' => 'bg-emerald-100/80 text-emerald-700 border border-emerald-200',
                                                'cancelada'  => 'bg-rose-100/80 text-rose-700 border border-rose-200',
                                            ];
                                            $estado = $c['estado'] ?? 'pendiente';
                                            $cls    = $badges[$estado] ?? 'bg-slate-100 text-slate-800 border border-slate-200';
                                        ?>
                                        <span class="px-3 py-1.5 rounded-full text-[11px] font-black tracking-wide uppercase shadow-sm <?= $cls ?>">
                                            <?= $estado ?>
                                        </span>
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
    // ── Lógica de Gráficos ───────────────────────────────────────────────────
    const statsSemana = <?= json_encode($statsSemana) ?>;
    const statsServicios = <?= json_encode($statsServicios) ?>;
    const brandColor = '<?= $negocioActual['color'] ?? '#6366F1' ?>';

    // Gráfico de Citas por Día
    const ctxSemana = document.getElementById('chartSemana').getContext('2d');
    new Chart(ctxSemana, {
        type: 'line',
        data: {
            labels: statsSemana.map(s => s.fecha),
            datasets: [{
                label: 'Citas',
                data: statsSemana.map(s => s.total),
                borderColor: brandColor,
                backgroundColor: brandColor + '20',
                fill: true,
                tension: 0.4,
                borderWidth: 4,
                pointBackgroundColor: brandColor,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Plus Jakarta Sans' } }, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans' } } }
            }
        }
    });

    // Gráfico de Servicios (Doughnut)
    const ctxServicios = document.getElementById('chartServicios').getContext('2d');
    new Chart(ctxServicios, {
        type: 'doughnut',
        data: {
            labels: statsServicios.map(s => s.nombre),
            datasets: [{
                data: statsServicios.map(s => s.total),
                backgroundColor: [brandColor, brandColor + 'CC', brandColor + '99', brandColor + '66', brandColor + '33'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'bottom', 
                    labels: { padding: 30, usePointStyle: true, font: { family: 'Plus Jakarta Sans', size: 12, weight: '600' } } 
                },
                tooltip: { backgroundColor: '#1e293b', padding: 12, titleFont: { size: 14 } }
            },
            cutout: '70%'
        }
    });

    function copiarLink(texto, boton) {
        const span = boton.querySelector('span');
        const originalText = span ? span.innerText : 'Copiar mi link de citas';

        const updateUI = () => {
            if (span) span.innerText = '¡Copiado!';
            boton.classList.add('bg-brand-soft', 'text-brand', 'border-brand');
            setTimeout(() => {
                if (span) span.innerText = originalText;
                boton.classList.remove('bg-brand-soft', 'text-brand', 'border-brand');
            }, 2000);
        };

        // Intento moderno con Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(texto).then(updateUI).catch(err => {
                console.error('Error al copiar: ', err);
                fallbackCopyTextToClipboard(texto, updateUI);
            });
        } else {
            // Fallback para contextos no seguros o navegadores viejos
            fallbackCopyTextToClipboard(texto, updateUI);
        }
    }

    function fallbackCopyTextToClipboard(text, callback) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        
        // Aseguramos que no se vea el textarea
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        textArea.style.opacity = "0";

        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) callback();
        } catch (err) {
            console.error('Fallback: Error al copiar', err);
        }

        document.body.removeChild(textArea);
    }
</script>

</body>
</html>
