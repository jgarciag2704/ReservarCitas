<?php
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
$warning = $_SESSION['warning'] ?? null;
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['warning']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SuperAdmin - Clientes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="flex h-screen">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-gray-900 text-white p-5">
        <h1 class="text-2xl font-bold mb-8">SuperAdmin</h1>

        <nav class="space-y-3">
            <a href="/index.php?controller=superadmin&action=index">Clientes</a>
            <a href="index.php?controller=auth&action=logout" class="block px-3 py-2 rounded hover:bg-gray-800">Salir</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-8 overflow-y-auto">

        <!-- HEADER -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold">Gestión de Clientes</h2>

            <button onclick="openModal()" 
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                + Crear Cliente
            </button>
        </div>

        <!-- ALERTAS -->
        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($warning): ?>
            <div class="bg-yellow-100 text-yellow-800 p-3 rounded mb-4 border border-yellow-300">
                <?= $warning ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- TABLA -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-3">Logo</th>
                        <th class="p-3">Nombre</th>
                        <th class="p-3">Slug</th>
                        <th class="p-3">Teléfono</th>
                        <th class="p-3">Color</th>
                        <th class="p-3">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($clientes as $c): ?>
                        <tr class="border-t">
                            <td class="p-3">
                                <?php if ($c['logo']): ?>
                                    <img src="/<?= htmlspecialchars($c['logo']) ?>" alt="Logo" class="w-10 h-10 object-cover rounded-full shadow">
                                <?php else: ?>
                                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-500 text-sm font-bold shadow">
                                        <?= strtoupper(substr($c['nombre'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="p-3"><?= htmlspecialchars($c['nombre']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($c['slug']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
                            <td class="p-3">
                                <span class="px-3 py-1 rounded text-white"
                                      style="background-color: <?= $c['color'] ?>">
                                    <?= $c['color'] ?>
                                </span>
                            </td>
                            <td class="p-3 space-x-2">

                                <!-- EDITAR -->
                                <button 
                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($c)) ?>)"
                                    class="bg-yellow-500 text-white px-3 py-1 rounded">
                                    Editar
                                </button>

                                <!-- ELIMINAR -->
                                <a href="/index.php?controller=superadmin&action=delete&id=<?= $c['id'] ?>"
   onclick="return confirm('¿Eliminar cliente?')"
   class="bg-red-600 text-white px-3 py-1 rounded">
    Eliminar
</a>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<!-- MODAL CREAR -->
<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
    <div class="bg-white p-6 rounded-lg w-96">

        <h3 class="text-xl mb-4 font-semibold">Crear Cliente</h3>

<form method="POST" action="/index.php?controller=superadmin&action=store" enctype="multipart/form-data">
    <?= csrf_field() ?>
                    <input type="text" name="nombre" placeholder="Nombre"
                   class="w-full border p-2 rounded" required>

            <input type="text" name="slug" placeholder="Slug (ej: empresa1)"
                   class="w-full border p-2 rounded" required>

            <label class="block text-sm text-gray-600 mt-2">Logo (Opcional)</label>
            <input type="file" name="logo" accept="image/*" class="w-full border p-2 rounded">

            <input type="tel" name="telefono" placeholder="Teléfono (Ej: 52155555555)"
                   class="w-full border p-2 rounded">

            <input type="color" name="color"
                   class="w-full border p-2 rounded">

            <hr>

            <h4 class="font-semibold">Usuario Admin</h4>

            <input type="email" name="email" placeholder="Email admin"
                   class="w-full border p-2 rounded" required>

            <input type="password" name="password" placeholder="Password"
                   class="w-full border p-2 rounded" required>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal()"
                        class="px-4 py-2 bg-gray-300 rounded">
                    Cancelar
                </button>

                <button class="px-4 py-2 bg-blue-600 text-white rounded">
                    Guardar
                </button>
            </div>

        </form>
    </div>
</div>

<!-- MODAL EDITAR -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
    <div class="bg-white p-6 rounded-lg w-96">

        <h3 class="text-xl mb-4 font-semibold">Editar Cliente</h3>
<form method="POST" action="/index.php?controller=superadmin&action=update" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" id="edit_id">

    <input type="text" name="nombre" id="edit_nombre"
           class="w-full border p-2 rounded" required>

    <input type="text" name="slug" id="edit_slug"
           class="w-full border p-2 rounded" required>

    <label class="block text-sm text-gray-600 mt-2">Logo (Opcional)</label>
    <img id="edit_logo_preview" src="" alt="Logo" class="hidden w-16 h-16 object-cover rounded shadow mb-2">
    <input type="file" name="logo" accept="image/*" class="w-full border p-2 rounded">

    <input type="tel" name="telefono" id="edit_telefono" placeholder="Teléfono"
           class="w-full border p-2 rounded">

    <input type="color" name="color" id="edit_color"
           class="w-full border p-2 rounded">

    <div class="flex justify-end space-x-2">
        <button type="button" onclick="closeEditModal()"
                class="px-4 py-2 bg-gray-300 rounded">
            Cancelar
        </button>

        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">
            Actualizar
        </button>
    </div>
</form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

function openEditModal(data) {
    document.getElementById('editModal').classList.remove('hidden');

    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_nombre').value = data.nombre;
    document.getElementById('edit_slug').value = data.slug;
    document.getElementById('edit_telefono').value = data.telefono || '';
    document.getElementById('edit_color').value = data.color;

    const logoPreview = document.getElementById('edit_logo_preview');
    if (data.logo) {
        logoPreview.src = '/' + data.logo;
        logoPreview.classList.remove('hidden');
    } else {
        logoPreview.classList.add('hidden');
    }
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

</body>
</html>