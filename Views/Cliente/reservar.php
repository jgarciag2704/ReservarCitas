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
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; } </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    </style>
</head>

<body class="min-h-screen relative text-slate-800 antialiased">
    
    <div class="glass-bg"></div>

    <!-- ── HÉADER DEL NEGOCIO ─────────────────────────────────────────────── -->
    <header class="text-white pt-16 pb-12 px-4 text-center">
        <?php if (!empty($cliente['logo'])): ?>
            <div class="inline-flex items-center justify-center w-24 h-24 rounded-[2rem] bg-white p-1.5 shadow-2xl border border-white/30 backdrop-blur-sm mb-6 transition-transform hover:scale-105 duration-500">
                <img src="/<?= htmlspecialchars($cliente['logo']) ?>" 
                     alt="<?= htmlspecialchars($cliente['nombre']) ?>" 
                     class="w-full h-full object-cover rounded-[1.7rem]">
            </div>
        <?php else: ?>
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-md mb-6 shadow-lg border border-white/20">
                <span class="text-3xl font-bold"><?= strtoupper(substr($cliente['nombre'], 0, 1)) ?></span>
            </div>
        <?php endif; ?>
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-3 drop-shadow-md"><?= htmlspecialchars($cliente['nombre']) ?></h1>
        <p class="text-white/90 text-lg md:text-xl font-medium max-w-lg mx-auto leading-relaxed">Agenda tu espacio en segundos y sin complicaciones.</p>
    </header>

    <main class="max-w-2xl mx-auto px-4 pb-20 -mt-2">

        <div class="bg-white/80 backdrop-blur-xl rounded-[2.5rem] shadow-xl shadow-slate-200/50 p-6 md:p-10 border border-white relative z-10 overflow-hidden">

        <!-- Alertas dinámicas vía SweetAlert (JS handles this) -->

        <?php if (empty($servicios)): ?>
            <div class="text-center py-20">
                <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                    <span class="text-5xl grayscale opacity-50">😴</span>
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
            <div class="mb-6 mb-8 text-center pt-2">
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
                <p class="text-slate-500 mt-1">Has elegido <strong id="resumen-servicio" class="text-slate-800 font-bold"></strong></p>
            </div>

            <div class="bg-slate-50 rounded-2xl p-5 space-y-6 border border-slate-100">

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
                <h2 class="text-2xl font-extrabold text-slate-800">Casi listo</h2>
                <p class="text-slate-500 mt-1">Completa tus datos para agendar</p>
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

            <form method="POST" action="index.php?controller=cliente&action=agendar" class="space-y-5">
                <?= csrf_field() ?>

                <!-- Campos ocultos -->
                <input type="hidden" name="slug"           value="<?= htmlspecialchars($cliente['slug']) ?>">
                <input type="hidden" name="cliente_id"     value="<?= (int)$cliente['id'] ?>">
                <input type="hidden" name="servicio_id"    id="h-servicio_id">
                <input type="hidden" name="servicio_nombre" id="h-servicio_nombre">
                <input type="hidden" name="fecha"          id="h-fecha">
                <input type="hidden" name="hora"           id="h-hora">
                <input type="hidden" name="empleado_id"    id="h-empleado_id">

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Tu nombre completo</label>
                    <input type="text" name="nombre" required placeholder="Ej. Ana Lilia Torres"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-opacity-50 transition-all shadow-sm"
                           style="--tw-ring-color: var(--color)">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Número de teléfono</label>
                    <input type="tel" name="telefono" required placeholder="Ej. 555-123-4567"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-opacity-50 transition-all shadow-sm"
                           style="--tw-ring-color: var(--color)">
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

    fetch(`index.php?controller=cliente&action=horasDisponibles&cliente_id=${CLIENTE_ID}&servicio_id=${estadoCita.servicioId}&fecha=${fecha}`)
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
                mostrarPickerError('⚠️ Todos los especialistas están ocupados a esta hora. Elige otra.');
                return;
            }

            if (res.auto_assign) {
                // Un solo empleado disponible: auto-asignar
                const emp = res.empleados[0];
                estadoCita.empleadoId   = emp.id;
                estadoCita.empleadoNombre = emp.nombre;
                mostrarAutoAsignado(emp);
                document.getElementById('btnSiguiente2').disabled = false;
            } else {
                // Múltiples empleados: mostrar picker
                mostrarPickerEmpleados(res.empleados);
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
    const picker = document.createElement('div');
    picker.id = 'empleadoPicker';
    picker.className = 'mt-4 space-y-2';
    picker.innerHTML = `<p class="text-sm font-bold text-slate-700 mb-2">Elige tu especialista:</p>`;

    empleados.forEach(emp => {
        const card = document.createElement('button');
        card.type = 'button';
        card.dataset.empId   = emp.id;
        card.dataset.empName = emp.nombre;
        card.className = 'emp-card w-full flex items-center gap-3 p-3 rounded-xl border-2 border-slate-200 hover:border-opacity-50 transition-all text-left';
        card.style.setProperty('--hover-color', 'var(--color)');
        card.innerHTML = `
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0" style="background-color: var(--color)">
                ${emp.nombre.charAt(0).toUpperCase()}
            </div>
            <span class="text-sm font-semibold text-slate-700">${emp.nombre}</span>
            <span class="ml-auto text-xs text-slate-400">Disponible ✓</span>
        `;
        card.onclick = () => elegirEmpleado(card, emp.id, emp.nombre);
        picker.appendChild(card);
    });

    horasContainer.after(picker);
}

function elegirEmpleado(card, id, nombre) {
    document.querySelectorAll('.emp-card').forEach(c => {
        c.classList.remove('border-opacity-100');
        c.style.borderColor = '#e2e8f0';
        c.style.backgroundColor = '';
    });
    card.style.borderColor = 'var(--color)';
    card.style.backgroundColor = 'color-mix(in srgb, var(--color) 8%, white)';

    estadoCita.empleadoId   = id;
    estadoCita.empleadoNombre = nombre;
    document.getElementById('btnSiguiente2').disabled = false;
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
                Swal.fire({ icon: 'error', title: '¡Oops!', text: '⚠️ Uy, alguien más acaba de seleccionar esta hora. Por favor elige otra.' });
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
            text: '<?= addslashes($success) ?>',
            confirmButtonColor: 'var(--color)',
            confirmButtonText: 'Aceptar',
            showCancelButton: <?= !empty($cliente['telefono']) ? 'true' : 'false' ?>,
            cancelButtonText: 'Avisar por WhatsApp',
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
        Swal.fire({ icon: 'error', title: 'Error', text: '<?= addslashes($error) ?>', confirmButtonColor: 'var(--color)' });
    <?php endif; ?>
});
</script>

</body>
</html>

