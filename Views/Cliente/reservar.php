<?php
// $cliente, $servicios, $success, $error vienen del ClienteController
$colorPrimario = !empty($cliente['color']) ? $cliente['color'] : '#3B82F6';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cliente['nombre']) ?> – Reserva tu cita</title>
    <meta name="description" content="Agenda tu cita en <?= htmlspecialchars($cliente['nombre']) ?> de forma rápida y sencilla.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- intl-tel-input para teléfonos internacionales -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.1/build/css/intlTelInput.css">
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.1/build/js/intlTelInput.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }

        :root { --color: <?= htmlspecialchars($colorPrimario) ?>; }

        .btn-primary {
            background-color: var(--color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-primary:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .btn-primary:active { transform: translateY(0); }

        .service-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 2px solid transparent;
        }
        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.01);
            border-color: #f1f5f9;
        }
        .service-card.selected {
            border-color: var(--color);
            background-color: #f8fafc;
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--color) 20%, transparent);
        }

        .hora-btn {
            border: 2px solid #e2e8f0;
            transition: all 0.2s;
            color: #475569;
        }
        .hora-btn:hover  { border-color: color-mix(in srgb, var(--color) 50%, transparent); color: var(--color); transform: translateY(-1px); }
        .hora-btn.active { background-color: var(--color); border-color: var(--color); color: #fff; transform: scale(1.02); box-shadow: 0 4px 6px -1px color-mix(in srgb, var(--color) 30%, transparent); }

        .step { display: none; opacity: 0; }
        .step.active { display: block; animation: smoothEnter 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards; }

        @keyframes smoothEnter { 
            0% { opacity: 0; transform: translateY(15px); } 
            100% { opacity: 1; transform: translateY(0); } 
        }

        /* Glassmorphism background effect */
        .glass-bg {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 350px;
            background: linear-gradient(135deg, var(--color), color-mix(in srgb, var(--color) 70%, black));
            border-bottom-left-radius: 3rem;
            border-bottom-right-radius: 3rem;
            z-index: -1;
            overflow: hidden;
        }
        .glass-bg::after {
            content: '';
            position: absolute; width: 200%; height: 200%;
            background: radial-gradient(circle at center, rgba(255,255,255,0.1) 0%, transparent 60%);
            top: -50%; left: -50%;
        }
        .emp-card { cursor: pointer; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
        .emp-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .emp-card.active { border-color: var(--color) !important; background-color: color-mix(in srgb, var(--color) 5%, white) !important; }
        
        .emp-card.active { border-color: var(--color) !important; background-color: color-mix(in srgb, var(--color) 5%, white) !important; }
        
        /* Estilos intl-tel-input premium */
        .iti { width: 100%; }
        .iti__country-list { 
            border-radius: 1.5rem; 
            padding: 0.5rem; 
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
        }
        .iti__selected-dial-code { font-weight: 600; color: #1e293b; }
        .iti__selected-flag { border-radius: 0.8rem 0 0 0.8rem; background-color: #f8fafc; border-right: 1px solid #e2e8f0; width: 85px !important; }
        .iti__tel-input { 
            border-radius: 0.8rem !important; 
            padding-top: 0.875rem !important; 
            padding-bottom: 0.875rem !important;
            font-weight: 500 !important;
        }
    </style>
</head>

<body class="min-h-screen relative text-slate-800 antialiased">
    
    <div class="glass-bg"></div>

    <!-- ── HÉADER DEL NEGOCIO (PREMIUM) ─────────────────────────────────── -->
    <header class="pt-12 pb-10 px-4">
        <div class="max-w-2xl mx-auto bg-white/10 backdrop-blur-md border border-white/20 shadow-2xl rounded-[2.5rem] p-6 sm:p-8 flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-6 relative overflow-hidden">
            
            <!-- Brillo estético de fondo (animado/radial) -->
            <div class="absolute -top-20 -right-20 w-48 h-48 bg-white/20 rounded-full blur-3xl"></div>
            
            <!-- Logo container -->
            <div class="relative z-10 flex-shrink-0 group">
                <?php if (!empty($cliente['logo'])): ?>
                    <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-full p-1.5 bg-gradient-to-tr from-white/60 to-white shadow-xl transition-transform duration-500 group-hover:scale-105">
                        <img src="/<?= htmlspecialchars($cliente['logo']) ?>" 
                             alt="<?= htmlspecialchars($cliente['nombre']) ?>" 
                             class="w-full h-full object-cover rounded-full border-4 border-white cursor-pointer">
                    </div>
                <?php else: ?>
                    <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-full p-1.5 bg-gradient-to-tr from-white/40 to-white shadow-xl transition-transform duration-500 group-hover:scale-105">
                        <div class="w-full h-full rounded-full bg-white flex items-center justify-center text-5xl font-black text-slate-800 shadow-inner cursor-pointer">
                            <?= strtoupper(substr($cliente['nombre'], 0, 1)) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Badgesito Overlap (Check verificado) -->
                <div class="absolute bottom-1 right-1 bg-emerald-400 border-[3px] border-white text-white w-9 h-9 rounded-full flex items-center justify-center shadow-lg" title="Perfil Verificado y Seguro">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                </div>
            </div>

            <!-- Información -->
            <div class="relative z-10 flex-1 pt-1 sm:pt-2">
                <div class="inline-flex items-center gap-1.5 bg-white/20 hover:bg-white/30 transition-colors px-3 py-1 rounded-full border border-white/20 mb-3 shadow-sm cursor-default">
                    <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span class="text-[10px] font-extrabold text-white uppercase tracking-widest drop-shadow-md">Reserva Protegida</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-2 drop-shadow-lg leading-tight">
                    <?= htmlspecialchars($cliente['nombre']) ?>
                </h1>
                
                <?php if (!empty($cliente['especialidad'])): ?>
                    <p class="text-white/95 text-sm sm:text-base font-semibold mb-5 max-w-sm drop-shadow-md pb-4 border-b border-white/20 sm:mx-0 mx-auto">
                        <?= htmlspecialchars($cliente['especialidad']) ?>
                    </p>
                <?php else: ?>
                    <p class="text-white/95 text-sm sm:text-base font-semibold mb-5 max-w-sm drop-shadow-md pb-4 border-b border-white/20 sm:mx-0 mx-auto">
                        Agenda tu espacio en segundos y sin complicaciones.
                    </p>
                <?php endif; ?>

                <div class="flex flex-wrap justify-center sm:justify-start gap-2.5">
                    <?php if (!empty($cliente['experiencia'])): ?>
                        <div class="flex items-center gap-2 bg-black/10 hover:bg-black/20 backdrop-blur-md transition-all border border-white/10 px-3.5 py-1.5 rounded-xl shadow-sm hover:shadow-md cursor-default">
                            <span class="flex items-center justify-center w-6 h-6 bg-white/20 rounded-lg text-white shadow-inner">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </span>
                            <span class="text-xs font-extrabold text-white tracking-wide"><?= htmlspecialchars($cliente['experiencia']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($cliente['google_maps'])): ?>
                        <a href="<?= htmlspecialchars($cliente['google_maps']) ?>" target="_blank" class="flex items-center gap-2 bg-black/10 hover:bg-black/20 backdrop-blur-md transition-all border border-white/10 px-3.5 py-1.5 rounded-xl shadow-sm hover:-translate-y-0.5 hover:shadow-md group/map">
                            <span class="flex items-center justify-center w-6 h-6 bg-white rounded-lg text-red-500 shadow-sm group-hover/map:scale-110 transition-transform">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </span>
                            <span class="text-xs font-extrabold text-white tracking-wide">Ubicación</span>
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 pb-20 -mt-2">

        <div class="bg-white/80 backdrop-blur-xl rounded-[2.5rem] shadow-xl shadow-slate-200/50 p-6 md:p-10 border border-white relative z-10 overflow-hidden">

        <!-- Alertas dinámicas vía SweetAlert (JS handles this) -->

        <?php if (empty($servicios)): ?>
            <div class="text-center py-20">
                <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner text-slate-300">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Sin servicios</h3>
                <p class="text-slate-500 font-medium">Este negocio aún no ha configurado sus servicios disponibles.</p>
            </div>
        <?php else: ?>

        <!-- ── INDICADOR DE PASOS ELEGANTE ─────────────────────────────────────────── -->
        <?php if (!$success): ?>
        <div class="relative mb-12 flex justify-between items-center px-2">
            <!-- Barra de progreso fondo -->
            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-slate-100 rounded-full -z-10"></div>
            <!-- Barra de progreso activa (se actualiza vía JS) -->
            <div id="progress-bar" class="absolute left-0 top-1/2 -translate-y-1/2 h-1 rounded-full -z-10 transition-all duration-500 ease-out" style="background-color: var(--color); width: 0%;"></div>

            <!-- Pasos -->
            <button id="ind-btn-1" onclick="if(!this.disabled) irPaso(1)" class="relative flex flex-col items-center gap-2 group cursor-pointer focus:outline-none">
                <span id="ind-circle-1" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm text-white shadow-md transition-all duration-300" style="background-color: var(--color)">1</span>
                <span id="ind-text-1" class="absolute -bottom-6 text-xs font-bold text-slate-800 whitespace-nowrap">Servicio</span>
            </button>
            <button id="ind-btn-2" onclick="if(!this.disabled) irPaso(2)" disabled class="relative flex flex-col items-center gap-2 group cursor-not-allowed focus:outline-none disabled:opacity-100">
                <span id="ind-circle-2" class="w-10 h-10 rounded-full bg-slate-100 border-2 border-slate-200 flex items-center justify-center font-bold text-sm text-slate-400 transition-all duration-300">2</span>
                <span id="ind-text-2" class="absolute -bottom-6 text-xs font-semibold text-slate-400 whitespace-nowrap">Cuándo</span>
            </button>
            <button id="ind-btn-3" onclick="if(!this.disabled) irPaso(3)" disabled class="relative flex flex-col items-center gap-2 group cursor-not-allowed focus:outline-none disabled:opacity-100">
                <span id="ind-circle-3" class="w-10 h-10 rounded-full bg-slate-100 border-2 border-slate-200 flex items-center justify-center font-bold text-sm text-slate-400 transition-all duration-300">3</span>
                <span id="ind-text-3" class="absolute -bottom-6 text-xs font-semibold text-slate-400 whitespace-nowrap">Confirma</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- ══════════════════════════════════════════════════════════════════
             PASO 1: ELEGIR SERVICIO
        ═══════════════════════════════════════════════════════════════════ -->
        <div id="step-1" class="step active">
            <div class="mb-8 text-center pt-2">
                <h2 class="text-2xl font-extrabold text-slate-800">Elige un servicio</h2>
                <p class="text-slate-500 mt-1">¿En qué podemos ayudarte hoy?</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($servicios as $s): ?>
                    <div class="service-card bg-white rounded-2xl p-5 shadow-sm border border-slate-200"
                         onclick="seleccionarServicio(<?= (int)$s['id'] ?>, '<?= htmlspecialchars($s['nombre'], ENT_QUOTES) ?>', <?= (int)$s['duracion'] ?>, <?= (float)$s['precio'] ?>)">
                        <h3 class="font-extrabold text-slate-800 text-[1.05rem] mb-3"><?= htmlspecialchars($s['nombre']) ?></h3>
                        <div class="flex items-center justify-between mt-auto">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 bg-slate-100 px-2.5 py-1.5 rounded-lg">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <?= (int)$s['duracion'] ?> min
                            </span>
                            <?php if ((float)$s['precio'] > 0): ?>
                                <span class="font-black text-slate-700 text-lg">$<?= number_format((float)$s['precio'], 2) ?></span>
                            <?php else: ?>
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Gratis</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════
             PASO 2: FECHA Y HORA
        ═══════════════════════════════════════════════════════════════════ -->
        <div id="step-2" class="step pt-2">

            <div class="mb-6 text-center">
                <h2 class="text-2xl font-extrabold text-slate-800">¿Cuándo te esperamos?</h2>
                <p class="text-slate-500 mt-1">Has elegido: <strong id="resumen-servicio" class="text-slate-800 font-bold"></strong></p>
            </div>

            <div class="bg-slate-50 rounded-2xl p-5 space-y-6 border border-slate-100">

                <?php if (($cliente['tipo_reserva'] ?? 'individual') === 'capacidad'): ?>
                <!-- Cantidad de personas -->
                <div class="mb-4">
                    <label class="block text-sm font-bold text-slate-700 mb-2">0. ¿Cuántas personas son?</label>
                    <div class="relative">
                        <input type="number" id="input-personas" min="1" max="50" value="1"
                               class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-slate-700 font-bold focus:outline-none focus:ring-2 transition-shadow shadow-sm"
                               style="--tw-ring-color: var(--color)"
                               onchange="estadoCita.personas = this.value; if(estadoCita.fecha) cargarHoras(estadoCita.fecha);">
                    </div>
                </div>
                <?php endif; ?>

                <!-- Buscador de fecha moderno -->
                <div class="relative">
                    <label class="block text-sm font-bold text-slate-700 mb-2">1. Selecciona el día</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <input type="date" id="fechaInput"
                               min="<?= date('Y-m-d') ?>"
                               class="w-full bg-white border border-slate-200 rounded-xl pl-11 pr-4 py-3.5 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-opacity-50 transition-shadow shadow-sm cursor-pointer"
                               style="--tw-ring-color: var(--color)"
                               onchange="cargarHoras(this.value)">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="block text-sm font-bold text-slate-700 mb-3">2. Escoge una hora</label>
                    <div id="horasContainer" class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm min-h-[120px] flex items-center justify-center">
                        <div class="text-center text-slate-400">
                            <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-sm font-medium">Elige una fecha arriba primero</p>
                        </div>
                    </div>
                    <p id="horasMensaje" class="text-sm font-semibold text-rose-500 hidden mt-3 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Día completamente reservado. Intenta con otro.
                    </p>
                </div>

            </div>

            <div class="mt-8 flex gap-3">
                <button onclick="irPaso(1)" class="w-1/3 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors">Volver</button>
                <button onclick="irPaso(3)" id="btnSiguiente2" disabled class="w-2/3 btn-primary text-white py-3.5 rounded-xl font-bold text-[1.05rem] shadow-lg disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    Continuar <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════
             PASO 3: DATOS DEL CLIENTE
        ═══════════════════════════════════════════════════════════════════ -->
        <div id="step-3" class="step pt-2">

            <div class="mb-6 text-center">
                <h2 class="text-2xl font-extrabold text-slate-800">¡Casi listo!</h2>
                <p class="text-slate-500 mt-1">Completa tus datos para agendar la cita.</p>
            </div>

            <!-- Resumen Premium -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-5 mb-8 flex items-center justify-between">
                <div>
                    <span class="text-xs font-extrabold text-indigo-500 uppercase tracking-widest block mb-1">Tu reservación</span>
                    <p class="font-extrabold text-indigo-950 text-lg" id="resumen-final-servicio"></p>
                    <p class="text-sm font-semibold text-indigo-700" id="resumen-final-fecha"></p>
                </div>
                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm text-indigo-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <form id="form-reserva" method="POST" action="index.php?controller=cliente&action=agendar" class="space-y-5" novalidate>
                <?= csrf_field() ?>

                <!-- Campos ocultos -->
                <input type="hidden" name="slug"           value="<?= htmlspecialchars($cliente['slug']) ?>">
                <input type="hidden" name="cliente_id"     value="<?= (int)$cliente['id'] ?>">
                <input type="hidden" name="servicio_id"    id="h-servicio_id">
                <input type="hidden" name="servicio_nombre" id="h-servicio_nombre">
                <input type="hidden" name="fecha"          id="h-fecha">
                <input type="hidden" name="hora"           id="h-hora">
                <input type="hidden" name="empleado_id"    id="h-empleado_id">
                <input type="hidden" name="personas"       id="h-personas">
                <input type="hidden" name="telefono"       id="h-telefono-full">

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Tu nombre completo</label>
                    <input type="text" name="nombre" id="input-nombre" required placeholder="Ej. Ana Lilia Torres"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-opacity-50 transition-all shadow-sm"
                           style="--tw-ring-color: var(--color)">
                    <p id="error-nombre" class="hidden text-xs text-red-500 font-bold mt-1.5 ml-1">Solo se permiten letras y espacios.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Número de teléfono</label>
                    <input type="tel" id="input-telefono" required placeholder="5551234567"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-opacity-50 transition-all shadow-sm"
                           style="--tw-ring-color: var(--color)">
                    <p id="error-telefono" class="hidden text-xs text-red-500 font-bold mt-2 ml-1">Por favor ingresa un número de teléfono válido.</p>
                </div>

                <div class="mt-8 flex gap-3 pt-4">
                    <button type="button" onclick="irPaso(2)" class="w-1/3 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors">Atrás</button>
                    <button type="submit" id="btn-final-confirmar" class="w-2/3 btn-primary text-white py-3.5 rounded-xl font-bold text-[1.05rem] shadow-lg flex justify-center items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Confirmar Cita
                    </button>
                </div>
            </form>
        </div>

        <?php endif; ?>
        
        </div> <!-- Fin de tarjeta Glassmorphic -->
    </main>

    <!-- ── FOOTER ─────────────────────────────────────────────────────────── -->
    <footer class="text-center pb-8 pt-4">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
            <svg class="w-4 h-4 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>
            Powered by JGARCIAG
        </p>
    </footer>

<script>
// ── Estado ────────────────────────────────────────────────────────────────────
const CLIENTE_ID = <?= (int)$cliente['id'] ?>;
let estadoCita = {
    servicioId:    null,
    servicioNombre: null,
    fecha:         null,
    hora:          null,
    empleadoId:    null,
    empleadoNombre: null,
    personas:      document.getElementById('input-personas') ? document.getElementById('input-personas').value : 1
};

// ── Navegación de pasos ───────────────────────────────────────────────────────
function irPaso(n) {
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.getElementById('step-' + n).classList.add('active');

    const progressBar = document.getElementById('progress-bar');
    if (progressBar) {
        if (n === 1) progressBar.style.width = '0%';
        if (n === 2) progressBar.style.width = '50%';
        if (n === 3) progressBar.style.width = '100%';
    }

    [1,2,3].forEach(i => {
        const circle = document.getElementById('ind-circle-' + i);
        const text   = document.getElementById('ind-text-' + i);
        const btn    = document.getElementById('ind-btn-' + i);
        if (!circle) return;

        if (i <= n) {
            circle.style.backgroundColor = 'var(--color)';
            circle.style.borderColor = 'transparent';
            circle.classList.remove('bg-slate-100', 'border-2', 'border-slate-200', 'text-slate-400');
            circle.classList.add('text-white', 'shadow-md');
            text.classList.remove('text-slate-400', 'font-semibold');
            text.classList.add('text-slate-800', 'font-bold');
            if (btn) btn.disabled = false;
        } else {
            circle.style.backgroundColor = '';
            circle.style.borderColor = '';
            circle.classList.add('bg-slate-100', 'border-2', 'border-slate-200', 'text-slate-400');
            circle.classList.remove('text-white', 'shadow-md');
            text.classList.add('text-slate-400', 'font-semibold');
            text.classList.remove('text-slate-800', 'font-bold');
            if (btn) btn.disabled = true;
        }
    });
}

// ── Paso 1: seleccionar servicio ──────────────────────────────────────────────
function seleccionarServicio(id, nombre, duracion, precio) {
    estadoCita.servicioId     = id;
    estadoCita.servicioNombre = nombre;
    estadoCita.empleadoId     = null;
    estadoCita.empleadoNombre = null;

    document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    document.getElementById('resumen-servicio').textContent = nombre + ' · ' + duracion + ' min';

    setTimeout(() => irPaso(2), 250);
}

// ── Paso 2: cargar horas disponibles (por servicio) ───────────────────────────
function cargarHoras(fecha) {
    estadoCita.fecha      = fecha;
    estadoCita.hora       = null;
    estadoCita.empleadoId = null;
    estadoCita.empleadoNombre = null;

    document.getElementById('btnSiguiente2').disabled = true;
    document.getElementById('horasMensaje').classList.add('hidden');

    // Limpiar picker de empleados si existe
    const picker = document.getElementById('empleadoPicker');
    if (picker) picker.remove();

    const container = document.getElementById('horasContainer');
    container.innerHTML = '<p class="text-sm text-gray-400 animate-pulse">Cargando horarios...</p>';

    fetch(`index.php?controller=cliente&action=horasDisponibles&cliente_id=${CLIENTE_ID}&servicio_id=${estadoCita.servicioId}&fecha=${fecha}&personas=${estadoCita.personas}`)
        .then(r => r.json())
        .then(horas => {
            container.innerHTML = '';

            if (!horas.length) {
                document.getElementById('horasMensaje').classList.remove('hidden');
                return;
            }

            const grid = document.createElement('div');
            grid.className = 'grid grid-cols-4 sm:grid-cols-6 gap-2';

            horas.forEach(hora => {
                const btn  = document.createElement('button');
                btn.type   = 'button';
                btn.textContent = hora;
                btn.className   = 'hora-btn rounded-lg py-2 text-sm font-medium text-gray-700 bg-white';
                btn.onclick     = () => seleccionarHora(btn, hora);
                grid.appendChild(btn);
            });

            container.appendChild(grid);
        })
        .catch(() => {
            container.innerHTML = '<p class="text-sm text-red-500">Error al cargar horarios.</p>';
        });
}

// ── Seleccionar hora: consultar empleados disponibles ─────────────────────────
function seleccionarHora(btn, hora) {
    // Resetear estado de hora/empleado
    document.querySelectorAll('.hora-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    estadoCita.hora       = hora;
    estadoCita.empleadoId = null;
    estadoCita.empleadoNombre = null;
    document.getElementById('btnSiguiente2').disabled = true;

    // Quitar picker anterior
    const pickerViejo = document.getElementById('empleadoPicker');
    if (pickerViejo) pickerViejo.remove();

    // Cargar empleados disponibles para esta hora y servicio
    cargarEmpleados(hora);
}

function cargarEmpleados(hora) {
    const url = `index.php?controller=cliente&action=empleadosDisponibles`
              + `&cliente_id=${CLIENTE_ID}&servicio_id=${estadoCita.servicioId}`
              + `&fecha=${estadoCita.fecha}&hora=${hora}`;

    fetch(url)
        .then(r => r.json())
        .then(res => {
            // Si el servicio no tiene empleados asignados, habilitar directamente
            if (res.sin_asignacion) {
                estadoCita.empleadoId   = null;
                estadoCita.empleadoNombre = null;
                document.getElementById('btnSiguiente2').disabled = false;
                return;
            }

            if (!res.empleados || !res.empleados.length) {
                // Todos los empleados están ocupados en esta hora
                mostrarPickerError('Todos los especialistas están ocupados a esta hora. Elige otra.');
                return;
            }
            const picker = document.getElementById('empleadoPicker');
            if (picker) picker.remove();

            if (res.auto_assign) {
                // Solo un empleado asignado al servicio: auto-asignar
                const emp = res.empleados[0];
                estadoCita.empleadoId   = emp.id;
                estadoCita.empleadoNombre = emp.nombre;
                mostrarAutoAsignado(emp);
                document.getElementById('btnSiguiente2').disabled = false;
            } else if (res.empleados.length >= 1) {
                // Múltiples empleados asignados al servicio (aunque solo 1 esté libre ahora): mostrar picker + "Cualquiera"
                mostrarPickerEmpleados(res.empleados);
                document.getElementById('btnSiguiente2').disabled = true; // Forzar a elegir
            } else {
                // Caso sin empleados o ninguno libre (res.empleados es vacío)
                document.getElementById('btnSiguiente2').disabled = false;
            }
        })
        .catch(() => {
            document.getElementById('btnSiguiente2').disabled = false;
        });
}

function mostrarAutoAsignado(emp) {
    const horasContainer = document.getElementById('horasContainer');
    const picker = document.createElement('div');
    picker.id = 'empleadoPicker';
    picker.className = 'mt-4 p-3 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3';
    picker.innerHTML = `
        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0" style="background-color: var(--color)">
            ${emp.nombre.charAt(0).toUpperCase()}
        </div>
        <div>
            <p class="text-xs font-bold text-green-700 uppercase tracking-wide">Especialista asignado</p>
            <p class="text-sm font-semibold text-slate-800">${emp.nombre}</p>
        </div>
        <svg class="w-5 h-5 text-green-500 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    `;
    horasContainer.after(picker);
}

function mostrarPickerEmpleados(empleados) {
    const horasContainer = document.getElementById('horasContainer');
    const picker = document.getElementById('empleadoPicker') || document.createElement('div');
    picker.id = 'empleadoPicker';
    picker.className = 'mt-6 bg-slate-50/50 p-5 rounded-3xl border border-slate-100 animate-in fade-in slide-in-from-top-2 duration-300';
    
    let html = `<p class="text-xs font-extrabold text-slate-400 uppercase tracking-widest mb-4">¿Quién te gustaría que te atienda?</p>
                <div class="grid grid-cols-1 gap-3">`;

    // Opción "Cualquiera"
    html += `
        <div onclick="elegirEmpleado(this, null, 'Cualquiera (Asignación automática)')" 
             class="emp-card flex items-center gap-4 p-4 rounded-2xl border-2 border-slate-100 bg-white shadow-sm ring-brand/10 hover:ring-8">
            <div class="w-11 h-11 rounded-2xl bg-slate-900 flex items-center justify-center text-white flex-shrink-0">
                <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.143-7.714L1 12l7.714-2.143L11 3z"></path></svg>
            </div>
            <div>
                <p class="font-bold text-slate-900 leading-tight">Cualquiera</p>
                <p class="text-[10px] text-slate-500 font-medium">Asignar al profesional más libre</p>
            </div>
            <div class="ml-auto w-6 h-6 rounded-full border-2 border-slate-200 flex items-center justify-center check-dot"></div>
        </div>
    `;

    empleados.forEach(emp => {
        const especialidad = emp.especialidad ? `<p class="text-[10px] text-brand font-bold uppercase tracking-tight">${emp.especialidad}</p>` : '';
        const experiencia  = emp.experiencia  ? `<p class="text-[10px] text-slate-400 font-medium">${emp.experiencia} de experiencia</p>` : '';
        const mapsLink    = emp.google_maps  ? `
            <a href="${emp.google_maps}" target="_blank" onclick="event.stopPropagation()" class="ml-auto p-2 bg-slate-50 rounded-lg text-slate-400 hover:text-brand transition-colors" title="Ver ubicación">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </a>` : '';

        html += `
            <div onclick="elegirEmpleado(this, ${emp.id}, '${emp.nombre.replace(/'/g, "\\'")}')" 
                 class="emp-card flex items-center gap-4 p-4 rounded-2xl border-2 border-slate-100 bg-white shadow-sm ring-brand/10 hover:ring-8">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-white font-black text-lg flex-shrink-0" style="background-color: var(--color)">
                    ${emp.nombre.charAt(0).toUpperCase()}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-slate-900 leading-tight truncate">${emp.nombre}</p>
                    ${especialidad}
                    ${experiencia}
                </div>
                ${mapsLink}
                <div class="w-6 h-6 rounded-full border-2 border-slate-200 flex items-center justify-center check-dot"></div>
            </div>
        `;
    });

    html += `</div>`;
    picker.innerHTML = html;
    if (!document.getElementById('empleadoPicker')) {
        horasContainer.after(picker);
    }
}

function elegirEmpleado(card, id, nombre) {
    document.querySelectorAll('.emp-card').forEach(c => {
        c.classList.remove('active', 'border-brand');
        c.querySelector('.check-dot').innerHTML = '';
        c.querySelector('.check-dot').className = 'ml-auto w-6 h-6 rounded-full border-2 border-slate-200 flex items-center justify-center check-dot';
    });

    card.classList.add('active');
    card.querySelector('.check-dot').className = 'ml-auto w-6 h-6 rounded-full bg-brand border-brand flex items-center justify-center check-dot';
    card.querySelector('.check-dot').innerHTML = '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';

    estadoCita.empleadoId   = id;
    estadoCita.empleadoNombre = nombre;
    document.getElementById('btnSiguiente2').disabled = false;
    
    // Smooth scroll al botón si se necesita
    document.getElementById('btnSiguiente2').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function mostrarPickerError(msg) {
    const horasContainer = document.getElementById('horasContainer');
    const picker = document.createElement('div');
    picker.id = 'empleadoPicker';
    picker.className = 'mt-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm font-semibold rounded-xl';
    picker.textContent = msg;
    horasContainer.after(picker);
}

// ── Paso 3: trasladar datos al formulario y Bloqueo Temporal ───────────────
const _irPasoOriginal = irPaso;
irPaso = async function(n) {
    if (n === 3) {
        if (!estadoCita.servicioId || !estadoCita.fecha || !estadoCita.hora) {
            Swal.fire({ icon: 'warning', title: 'Atención', text: 'Completa todos los campos antes de continuar.' });
            return;
        }

        const btnActivo = document.querySelector('.hora-btn.active');
        if (btnActivo) btnActivo.textContent = 'Bloqueando...';

        try {
            const res = await fetch('index.php?controller=cliente&action=bloquearHora', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cliente_id:  CLIENTE_ID,
                    fecha:       estadoCita.fecha,
                    hora:        estadoCita.hora,
                    empleado_id: estadoCita.empleadoId || null,
                })
            });
            const data = await res.json();

            if (!data.success) {
                Swal.fire({ icon: 'error', title: '¡Oops!', text: 'Uy, alguien más acaba de seleccionar esta hora. Por favor elige otra.' });
                cargarHoras(estadoCita.fecha);
                return;
            }
        } catch (e) {
            console.error('Error al bloquear hora', e);
        }

        document.getElementById('h-servicio_id').value    = estadoCita.servicioId;
        document.getElementById('h-servicio_nombre').value = estadoCita.servicioNombre;
        document.getElementById('h-fecha').value           = estadoCita.fecha;
        document.getElementById('h-hora').value            = estadoCita.hora;
        document.getElementById('h-empleado_id').value     = estadoCita.empleadoId || '';
        if (document.getElementById('h-personas')) {
            document.getElementById('h-personas').value = estadoCita.personas;
        }

        const fechaLeg = new Date(estadoCita.fecha + 'T00:00').toLocaleDateString('es-MX', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
        document.getElementById('resumen-final-servicio').textContent = estadoCita.servicioNombre;

        let resumenFecha = fechaLeg + ' · ' + estadoCita.hora;
        if (estadoCita.empleadoNombre) resumenFecha += ' · con ' + estadoCita.empleadoNombre;
        document.getElementById('resumen-final-fecha').textContent = resumenFecha;
    }
    _irPasoOriginal(n);
};

// Alertas de PHP automáticas
document.addEventListener('DOMContentLoaded', () => {
    <?php if ($success): ?>
        Swal.fire({
            icon: 'success',
            title: '¡Cita Confirmada!',
            html: '<?= addslashes($success) ?>',
            confirmButtonColor: 'var(--color)',
            confirmButtonText: 'Aceptar',
            showCancelButton: <?= !empty($cliente['telefono']) ? 'true' : 'false' ?>,
            cancelButtonText: 'Avisar a <?= htmlspecialchars($cliente['nombre']) ?>',
            cancelButtonColor: '#25D366'
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel) {
                <?php if (!empty($cliente['telefono'])): ?>
                    window.open('https://wa.me/<?= preg_replace("/[^0-9]/", "", $cliente["telefono"]) ?>?text=<?= urlencode($whatsapp_text ?? "¡Hola! Acabo de agendar una cita.") ?>', '_blank');
                <?php endif; ?>
            }
        });
    <?php endif; ?>

    <?php if ($error): ?>
        Swal.fire({ icon: 'error', title: 'Error', html: '<?= addslashes($error) ?>', confirmButtonColor: 'var(--color)' });
    <?php endif; ?>

    // Validación en tiempo real (As-you-type)
    const btnConfirmar = document.getElementById('btn-final-confirmar');
    const inputNombre  = document.getElementById('input-nombre');
    const inputTel     = document.getElementById('input-telefono');
    const inputFull    = document.getElementById('h-telefono-full');
    const errorNombre  = document.getElementById('error-nombre');
    const errorTel     = document.getElementById('error-telefono');

    // Inicializar intl-tel-input
    let iti = window.intlTelInput(inputTel, {
        initialCountry: "mx",
        preferredCountries: ["mx", "us"],
        separateDialCode: true,
        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.1/build/js/utils.js",
    });

    function validarFormulario() {
        const valNombre   = inputNombre.value.trim();
        const regexNombre = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/;

        const nombreOk   = regexNombre.test(valNombre) && valNombre.length > 2;
        const telefonoOk = iti.isValidNumber();

        // Actualizar campo oculto final para la BD
        if (telefonoOk) {
            inputFull.value = iti.getNumber();
        }

        // Estilos Nombre
        if (valNombre.length > 0 && !regexNombre.test(valNombre)) {
            inputNombre.classList.add('border-red-400', 'ring-red-100');
            errorNombre.classList.remove('hidden');
        } else {
            inputNombre.classList.remove('border-red-400', 'ring-red-100');
            errorNombre.classList.add('hidden');
        }

        // Estilos Teléfono (solo marcar error si no es válido y no está vacío)
        if (inputTel.value.length > 0 && !telefonoOk) {
            inputTel.classList.add('border-red-400', 'ring-red-100');
            errorTel.classList.remove('hidden');
        } else {
            inputTel.classList.remove('border-red-400', 'ring-red-100');
            errorTel.classList.add('hidden');
        }

        // Habilitar / Deshabilitar Botón
        if (nombreOk && telefonoOk) {
            btnConfirmar.disabled = false;
            btnConfirmar.style.opacity = '1';
            btnConfirmar.style.cursor  = 'pointer';
        } else {
            btnConfirmar.disabled = true;
            btnConfirmar.style.opacity = '0.5';
            btnConfirmar.style.cursor  = 'not-allowed';
        }
    }

    if (inputNombre && inputTel) {
        inputNombre.addEventListener('input', validarFormulario);
        inputTel.addEventListener('input', validarFormulario);
        inputTel.addEventListener('countrychange', validarFormulario);
        validarFormulario(); // Inicializar estado
    }

    // Submit asíncrono para mostrar Modal Dinámico
    const formReserva = document.getElementById('form-reserva');
    if (formReserva) {
        formReserva.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (btnConfirmar.disabled) return;

            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = 'Procesando...';

            const formData = new FormData(this);
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json();

                if (result.status) {
                    mostrarExitoModal();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.message, confirmButtonColor: 'var(--color)' });
                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Confirmar Cita';
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al procesar la cita.', confirmButtonColor: 'var(--color)' });
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Confirmar Cita';
            }
        });
    }

    function mostrarExitoModal() {
        const partesFecha = estadoCita.fecha.split('-');
        const fechaObj = new Date(partesFecha[0], partesFecha[1] - 1, partesFecha[2]);
        const fechaFormateada = fechaObj.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        const horaFormateada = estadoCita.hora;

        const telefonoDisplay = "<?= htmlspecialchars($cliente['telefono'] ?? '') ?>";
        const nombre = document.getElementById('input-nombre').value.trim();
        const negocio = "<?= htmlspecialchars($cliente['nombre']) ?>";
        
        let contactoHtml = '';
        if (telefonoDisplay) {
            let numLimpio = telefonoDisplay.replace(/\D/g, '');
            let formattedPhone = `<span class="tracking-widest">${telefonoDisplay}</span>`;
            let badgeLadaHtml = '';

            if (numLimpio.length >= 10) {
                let numLocal = numLimpio.slice(-10);
                let lada = numLimpio.slice(0, -10);
                
                let numLocalFormatted = numLocal.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
                
                if (lada) {
                    badgeLadaHtml = `<span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-md text-sm font-bold mr-1.5 border border-indigo-200">+${lada}</span>`;
                }
                
                formattedPhone = `<span class="tracking-widest">${numLocalFormatted}</span>`;
            }

            contactoHtml = `
                <p class="text-sm text-slate-600 font-medium mb-3 text-center">Para cualquier duda, contáctanos al:</p>
                <div class="flex items-center justify-center bg-slate-100 py-3.5 border border-slate-200 rounded-xl mb-5 text-slate-800 font-extrabold text-xl shadow-inner max-w-[280px] mx-auto">
                    <svg class="w-5 h-5 text-indigo-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    ${badgeLadaHtml}
                    ${formattedPhone}
                </div>
            `;
        } else {
            contactoHtml = `<p class="text-sm text-slate-600 font-medium mb-5 text-center">Tu cita ya está confirmada.</p>`;
        }

        const htmlExito = `
            <div class="text-center" style="animation: smoothEnter 0.5s ease-out forwards;">
                <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
                    <svg class="w-10 h-10 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-extrabold text-slate-800 mb-2">✅ ¡Cita confirmada!</h2>
                <p class="text-slate-600 font-medium text-lg leading-relaxed mb-6">
                    Te esperamos el <strong class="text-slate-800">${fechaFormateada}</strong> a las <strong class="text-slate-800">${horaFormateada}</strong>
                </p>
                
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 text-left max-w-sm mx-auto shadow-sm">
                    ${contactoHtml}
                    
                    <div class="flex flex-col gap-3">
                        <button type="button" onclick="window.location.reload()" class="w-full btn-primary text-white py-3.5 rounded-xl font-bold text-[1.05rem] shadow-lg flex justify-center items-center gap-2">
                            Listo
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('step-3').innerHTML = htmlExito;
        window.scrollTo({ top: document.getElementById('step-3').offsetTop - 50, behavior: 'smooth' });
        
        const circle3 = document.getElementById('ind-circle-3');
        if(circle3) {
            circle3.innerHTML = '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
        }
    }
});
</script>

</body>
</html>

