-- ============================================================
-- Migración: Seguridad en bloqueos de citas
-- Agregar índice único para evitar dobles bloqueos por empleado
-- ============================================================

-- Eliminar índice anterior si existe (para evitar duplicados exactos)
ALTER TABLE bloqueos_citas DROP INDEX IF EXISTS unq_bloqueo_reg;

-- Agregar índice único que incluya al empleado
-- Nota: En MySQL, los valores NULL en índices UNIQUE no impiden duplicados.
-- La seguridad total para el caso "General" (empleado_id = NULL) la reforzaremos en PHP.
ALTER TABLE bloqueos_citas 
    ADD UNIQUE INDEX unq_bloqueo_empleado (cliente_id, empleado_id, fecha, hora);
