-- ============================================================
-- Migración: agregar columna 'estado' a la tabla citas
-- y columna 'precio' a la tabla servicios
-- Ejecutar una sola vez.
-- ============================================================

-- Columna estado en citas (si no existe)
ALTER TABLE citas
    ADD COLUMN IF NOT EXISTS estado ENUM(
        'pendiente','confirmada','cancelada','completada'
    ) NOT NULL DEFAULT 'pendiente';

-- Columna precio en servicios (si no existe)
ALTER TABLE servicios
    ADD COLUMN IF NOT EXISTS precio DECIMAL(10,2) NOT NULL DEFAULT 0.00;

-- Columna duracion en servicios (si no existe)
ALTER TABLE servicios
    ADD COLUMN IF NOT EXISTS duracion INT NOT NULL DEFAULT 30;

-- Columna nombre en usuarios (si no existe; se guarda para mostrar en sidebar)
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS nombre VARCHAR(100) NULL;

-- Columna para forzar cambio de contraseña tras restablecerla
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS force_password_change TINYINT(1) NOT NULL DEFAULT 0;

-- ============================================================
-- Verificar estructura resultante
-- ============================================================
-- DESCRIBE citas;
-- DESCRIBE servicios;
-- DESCRIBE usuarios;
