-- ============================================================
-- Migración: Asignación de empleados por servicio
-- Ejecutar UNA SOLA VEZ en phpMyAdmin o CLI MySQL.
-- ============================================================

-- 1. Tabla pivot: qué empleados pueden realizar cada servicio
CREATE TABLE IF NOT EXISTS servicio_empleados (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    servicio_id INT NOT NULL,
    empleado_id INT NOT NULL,
    UNIQUE KEY unq_asignacion (servicio_id, empleado_id),
    FOREIGN KEY (servicio_id) REFERENCES servicios(id)  ON DELETE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES usuarios(id)   ON DELETE CASCADE
);

-- 2. Columna empleado_id en citas (quién realizará el servicio)
ALTER TABLE citas
    ADD COLUMN IF NOT EXISTS empleado_id INT NULL;

-- 3. Columna empleado_id en bloqueos (bloqueo específico por empleado)
ALTER TABLE bloqueos_citas
    ADD COLUMN IF NOT EXISTS empleado_id INT NULL;

-- 4. Reemplazar el UNIQUE key de bloqueos para incluir empleado_id
--    (DROP puede fallar si el índice ya fue renombrado; ignora ese error)
ALTER TABLE bloqueos_citas DROP INDEX IF EXISTS unq_bloqueo;

-- ============================================================
-- Verificar:
-- DESCRIBE citas;
-- DESCRIBE bloqueos_citas;
-- SHOW TABLES;
-- ============================================================
