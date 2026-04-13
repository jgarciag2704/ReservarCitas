-- ============================================================
-- Solo las tablas que faltan (clientes y usuarios ya existen)
-- Sin UNSIGNED para que coincida con el tipo de clientes.id
-- ============================================================

CREATE TABLE IF NOT EXISTS servicios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nombre     VARCHAR(150) NOT NULL,
    duracion   INT NOT NULL DEFAULT 30,
    precio     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    creado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS horarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id  INT NOT NULL,
    dia_semana  ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin    TIME NOT NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS citas (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id     INT NOT NULL,
    servicio_id    INT NULL,
    nombre_cliente VARCHAR(150) NOT NULL,
    telefono       VARCHAR(20)  NOT NULL,
    fecha          DATE         NOT NULL,
    hora           TIME         NOT NULL,
    estado         ENUM('pendiente','confirmada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
    creado_en      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id)  REFERENCES clientes(id)  ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE SET NULL
);

-- Agregar columna nombre a usuarios si no existe
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS nombre VARCHAR(100) NULL;

-- Agregar columna para forzar cambio de contraseña
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS force_password_change TINYINT(1) NOT NULL DEFAULT 0;
