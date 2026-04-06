-- ============================================================
-- Migración: Soporte de horarios por empleado
-- Agregar columna empleado_id a la tabla horarios
-- ============================================================

ALTER TABLE horarios
    ADD COLUMN empleado_id INT NULL DEFAULT NULL AFTER cliente_id,
    ADD CONSTRAINT fk_horario_empleado FOREIGN KEY (empleado_id) REFERENCES usuarios(id) ON DELETE CASCADE;

-- Si se desea evitar duplicados exactos (opcional-ish):
-- ALTER TABLE horarios ADD UNIQUE INDEX unq_horario_emp (cliente_id, empleado_id, dia_semana, hora_inicio, hora_fin);
