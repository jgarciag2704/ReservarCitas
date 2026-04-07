<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda – Panel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FullCalendar -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .fc { --fc-border-color: #e2e8f0; --fc-button-bg-color: #6366f1; --fc-button-border-color: #6366f1; }
        .fc .fc-button-primary:hover { background-color: #4f46e5; border-color: #4f46e5; }
        .fc .fc-toolbar-title { font-size: 1.25rem; font-weight: 800; color: #1e293b; }
        .fc-event { border: none; padding: 2px 4px; border-radius: 6px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body class="text-slate-800 antialiased">

<div class="flex flex-col md:flex-row h-screen overflow-hidden">
    <?php require 'Views/Admin/_sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-10">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4 mt-4 md:mt-0">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-1">Agenda Visual</h1>
                <p class="text-slate-500 font-medium">Gestiona tus citas desde el calendario interactivo</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <!-- Filtro por empleado -->
                <?php if (!empty($empleadosFiltro)): ?>
                <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-sm">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">👤 Empleado</label>
                    <select id="filtroEmpleado"
                            onchange="aplicarFiltroEmpleado(this.value)"
                            class="bg-transparent text-sm font-bold text-slate-700 focus:outline-none cursor-pointer">
                        <option value="">Todos</option>
                        <?php foreach ($empleadosFiltro as $emp): ?>
                            <option value="<?= (int)$emp['id'] ?>">
                                <?= htmlspecialchars($emp['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <a href="index.php?controller=admin&action=citas"
                   class="bg-white border border-slate-200 text-slate-700 px-5 py-2.5 rounded-xl text-sm font-extrabold hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    Vista Lista
                </a>
            </div>
        </div>

        <div class="bg-white p-4 md:p-8 rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-200/60 mb-10 overflow-hidden">
            <div id='calendar' class="min-h-[500px]"></div>
        </div>
    </main>
</div>

<script>
let calendar;

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día'
        },
        events: buildEventsUrl(),
        eventClick: function(info) {
            window.location.href = 'index.php?controller=admin&action=citas';
        },
        height: 'auto',
        nowIndicator: true,
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00'
    });
    calendar.render();
});

function buildEventsUrl(empleadoId) {
    let url = 'index.php?controller=admin&action=apiEventos';
    if (empleadoId) url += '&empleado_id=' + empleadoId;
    return url;
}

function aplicarFiltroEmpleado(empleadoId) {
    if (!calendar) return;
    calendar.removeAllEventSources();
    calendar.addEventSource(buildEventsUrl(empleadoId || null));
}
</script>

</body>
</html>
