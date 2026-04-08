<?php
$errorLogin = $_SESSION['error_login'] ?? null;
unset($_SESSION['error_login']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión – Reservia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass {
            background: rgba(255,255,255,.08);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,.15);
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-950 to-gray-900 flex items-center justify-center p-4">

<div class="glass rounded-2xl w-full max-w-sm p-8 shadow-2xl">

    <!-- Logo / título -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-tr from-blue-600 to-indigo-500 rounded-2xl mb-4 shadow-lg shadow-blue-500/40">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </div>
        <h1 class="text-2xl font-bold text-white">Reservia</h1>
        <p class="text-blue-300 text-sm mt-1">Accede a tu panel de administración</p>
    </div>

    <!-- Error flash -->
    <?php if ($errorLogin): ?>
        <div class="bg-red-500/20 border border-red-500/40 text-red-300 text-sm px-4 py-3 rounded-xl mb-5 flex items-center gap-2">
            <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span><?= htmlspecialchars($errorLogin) ?></span>
        </div>
    <?php endif; ?>

    <!-- Formulario -->
    <form method="POST" action="index.php?controller=auth&action=autenticar" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label for="email" class="block text-sm font-medium text-blue-200 mb-1.5">Correo electrónico</label>
            <input id="email" type="email" name="email" required autocomplete="email"
                   placeholder="admin@ejemplo.com"
                   class="w-full bg-white/10 border border-white/20 text-white placeholder-blue-300/60 rounded-xl px-4 py-3 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-blue-200 mb-1.5">Contraseña</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   placeholder="••••••••"
                   class="w-full bg-white/10 border border-white/20 text-white placeholder-blue-300/60 rounded-xl px-4 py-3 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 rounded-xl text-sm
                       transition-all duration-200 hover:shadow-lg hover:shadow-blue-500/30 mt-2">
            Iniciar sesión →
        </button>

    </form>

</div>

</body>
</html>