-- Migración para Fase 1 del modelo SaaS
-- Ejecutar en phpMyAdmin para tener soporte de bloqueos temporales

CREATE TABLE IF NOT EXISTS bloqueos_citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    expira_en DATETIME NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    UNIQUE KEY unq_bloqueo (cliente_id, fecha, hora)
);

-- Agregar teléfono al cliente (SaaS) para WhatsApp
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS telefono VARCHAR(20) NULL;
