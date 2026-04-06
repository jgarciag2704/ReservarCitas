-- ============================================================
-- Migración: Soporte de empleados por cliente (multi-usuario)
-- Ejecutar una sola vez en phpMyAdmin o CLI de MySQL.
-- ============================================================

-- 1. Agregar columna teléfono a usuarios (si no existe)
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS telefono VARCHAR(20) NULL;

-- 2. Agregar columna activo a usuarios (1 = activo, 0 = inactivo)
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1;

-- ============================================================
-- Verificar estructura resultante:
-- DESCRIBE usuarios;
-- ============================================================
