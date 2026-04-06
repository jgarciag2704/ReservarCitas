<?php require 'views/layout/header.php'; ?>

<h1 class="text-3xl mb-6"><?= $cliente['nombre'] ?></h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<?php foreach($servicios as $s): ?>
    <div class="bg-gray-800 p-4 rounded-xl">
        <h2 class="text-xl"><?= $s['nombre'] ?></h2>
        <p>$<?= $s['precio'] ?></p>

        <form method="POST" action="index.php?controller=cliente&action=agendar">
            <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
            <input type="hidden" name="servicio_id" value="<?= $s['id'] ?>">

            <input name="nombre" placeholder="Tu nombre" class="w-full mt-2 p-2 text-black">
            <input name="telefono" placeholder="Teléfono" class="w-full mt-2 p-2 text-black">
            <input type="date" name="fecha" class="w-full mt-2 p-2 text-black">
            <input type="time" name="hora" class="w-full mt-2 p-2 text-black">

            <button class="bg-blue-600 mt-3 p-2 w-full rounded">
                Agendar
            </button>
        </form>
    </div>
<?php endforeach; ?>
</div>
<select name="hora" id="horaSelect" class="w-full mt-2 p-2 text-black"></select>

<script>
function cargarHoras(cliente, servicio, fecha){

    fetch(`index.php?controller=cita&action=horasDisponibles&cliente_id=${cliente}&servicio_id=${servicio}&fecha=${fecha}`)
    .then(res => res.json())
    .then(data => {

        let select = document.getElementById("horaSelect");
        select.innerHTML = "";

        data.forEach(hora => {
            let option = document.createElement("option");
            option.value = hora;
            option.text = hora;
            select.appendChild(option);
        });
    });
}
</script>

<?php require 'views/layout/footer.php'; ?>