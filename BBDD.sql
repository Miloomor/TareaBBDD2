-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS bdzeropressure DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE bdzeropressure;

-- Eliminar tablas si existen (para reiniciar)
DROP TABLE IF EXISTS Asignacion_Error;
DROP TABLE IF EXISTS Asignacion_Funcionalidad;
DROP TABLE IF EXISTS Criterios_Funcionalidad;
DROP TABLE IF EXISTS Ingeniero_Topico;
DROP TABLE IF EXISTS Solicitud_Error;
DROP TABLE IF EXISTS Solicitud_Funcionalidad;
DROP TABLE IF EXISTS Ingeniero;
DROP TABLE IF EXISTS Usuario;
DROP TABLE IF EXISTS Topico;

-- Tabla de Tópicos
CREATE TABLE Topico (
    id_topico INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

-- Tabla de Usuarios
CREATE TABLE Usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    rut VARCHAR(12) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Tabla de Ingenieros
CREATE TABLE Ingeniero (
    id_ingeniero INT AUTO_INCREMENT PRIMARY KEY,
    rut VARCHAR(12) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Tabla de Solicitudes de Funcionalidad
CREATE TABLE Solicitud_Funcionalidad (
    id_funcionalidad INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    ambiente ENUM('Web', 'Movil') NOT NULL,
    resumen TEXT NOT NULL,
    id_usuario INT NOT NULL,
    id_topico INT NOT NULL,
    fecha_publicacion DATE NOT NULL,
    fecha_resolucion DATE NULL,
    estado ENUM('Abierto', 'En Progreso', 'Resuelto', 'Cerrado', 'Archivado') NOT NULL DEFAULT 'Abierto',
    observaciones TEXT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_topico) REFERENCES Topico(id_topico)
);

-- Tabla de Criterios de Funcionalidad (normalización)
CREATE TABLE Criterios_Funcionalidad (
    id_criterio INT AUTO_INCREMENT PRIMARY KEY,
    id_funcionalidad INT NOT NULL,
    descripcion TEXT NOT NULL,
    orden INT NOT NULL DEFAULT 1,
    FOREIGN KEY (id_funcionalidad) REFERENCES Solicitud_Funcionalidad(id_funcionalidad) ON DELETE CASCADE,
    INDEX idx_funcionalidad (id_funcionalidad)
);

-- Tabla de Solicitudes de Error
CREATE TABLE Solicitud_Error (
    id_error INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    id_usuario INT NOT NULL,
    id_topico INT NOT NULL,
    fecha_publicacion DATE NOT NULL,
    fecha_resolucion DATE NULL,
    estado ENUM('Abierto', 'En Progreso', 'Resuelto', 'Cerrado', 'Archivado') NOT NULL DEFAULT 'Abierto',
    observaciones TEXT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_topico) REFERENCES Topico(id_topico)
);

-- Tabla de relación Ingeniero-Tópico (especialidades)
CREATE TABLE Ingeniero_Topico (
    id_ingeniero INT NOT NULL,
    id_topico INT NOT NULL,
    PRIMARY KEY (id_ingeniero, id_topico),
    FOREIGN KEY (id_ingeniero) REFERENCES Ingeniero(id_ingeniero),
    FOREIGN KEY (id_topico) REFERENCES Topico(id_topico)
);

-- Tabla de asignación de solicitudes de funcionalidad
CREATE TABLE Asignacion_Funcionalidad (
    id_funcionalidad INT NOT NULL,
    id_ingeniero INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_funcionalidad, id_ingeniero),
    FOREIGN KEY (id_funcionalidad) REFERENCES Solicitud_Funcionalidad(id_funcionalidad),
    FOREIGN KEY (id_ingeniero) REFERENCES Ingeniero(id_ingeniero)
);

-- Tabla de asignación de solicitudes de error
CREATE TABLE Asignacion_Error (
    id_error INT NOT NULL,
    id_ingeniero INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_error, id_ingeniero),
    FOREIGN KEY (id_error) REFERENCES Solicitud_Error(id_error),
    FOREIGN KEY (id_ingeniero) REFERENCES Ingeniero(id_ingeniero)
);

-- ========== PROCEDIMIENTOS ALMACENADOS ==========

-- Procedimiento para asignar ingenieros a una solicitud de funcionalidad
DELIMITER $$
CREATE PROCEDURE asignar_ing_funcionalidad(IN p_id_funcionalidad INT)
asignar: BEGIN
    DECLARE v_id_topico INT;
    DECLARE v_count INT DEFAULT 0;
    
    -- Obtenemos el tópico de la funcionalidad
    SELECT id_topico INTO v_id_topico 
    FROM Solicitud_Funcionalidad 
    WHERE id_funcionalidad = p_id_funcionalidad;
    
    -- Verificamos si ya hay asignaciones para esta funcionalidad
    SELECT COUNT(*) INTO v_count 
    FROM Asignacion_Funcionalidad 
    WHERE id_funcionalidad = p_id_funcionalidad;
    
    -- Si ya hay asignaciones, no hacemos nada
    IF v_count > 0 THEN
        LEAVE asignar;
    END IF;
    
    -- Asignamos hasta 3 ingenieros con esa especialidad y menor carga
    INSERT INTO Asignacion_Funcionalidad (id_funcionalidad, id_ingeniero)
    SELECT p_id_funcionalidad, it.id_ingeniero
    FROM Ingeniero_Topico it
    JOIN (
        SELECT id_ingeniero, 
               (SELECT COUNT(*) FROM Asignacion_Funcionalidad WHERE id_ingeniero = i.id_ingeniero) +
               (SELECT COUNT(*) FROM Asignacion_Error WHERE id_ingeniero = i.id_ingeniero) AS carga
        FROM Ingeniero i
    ) carga ON it.id_ingeniero = carga.id_ingeniero
    WHERE it.id_topico = v_id_topico
    AND carga.carga < 20 -- Límite de 20 asignaciones por ingeniero
    ORDER BY carga ASC
    LIMIT 3;
    
    -- Si se asignaron 3 ingenieros, cambiar estado a 'En Progreso'
    SELECT COUNT(*) INTO v_count 
    FROM Asignacion_Funcionalidad 
    WHERE id_funcionalidad = p_id_funcionalidad;
    
    IF v_count = 3 THEN
        UPDATE Solicitud_Funcionalidad
        SET estado = 'En Progreso'
        WHERE id_funcionalidad = p_id_funcionalidad
        AND estado = 'Abierto';
    END IF;
END$$
DELIMITER ;

-- Procedimiento para asignar ingenieros a una solicitud de error
DELIMITER $$
CREATE PROCEDURE asignar_ing_error(IN p_id_error INT)
asignar: BEGIN
    DECLARE v_id_topico INT;
    DECLARE v_count INT DEFAULT 0;
    
    -- Obtenemos el tópico del error
    SELECT id_topico INTO v_id_topico 
    FROM Solicitud_Error 
    WHERE id_error = p_id_error;
    
    -- Verificamos si ya hay asignaciones para este error
    SELECT COUNT(*) INTO v_count 
    FROM Asignacion_Error 
    WHERE id_error = p_id_error;
    
    -- Si ya hay asignaciones, no hacemos nada
    IF v_count > 0 THEN
        LEAVE asignar;
    END IF;
    
    -- Asignamos hasta 3 ingenieros con esa especialidad y menor carga
    INSERT INTO Asignacion_Error (id_error, id_ingeniero)
    SELECT p_id_error, it.id_ingeniero
    FROM Ingeniero_Topico it
    JOIN (
        SELECT id_ingeniero, 
               (SELECT COUNT(*) FROM Asignacion_Funcionalidad WHERE id_ingeniero = i.id_ingeniero) +
               (SELECT COUNT(*) FROM Asignacion_Error WHERE id_ingeniero = i.id_ingeniero) AS carga
        FROM Ingeniero i
    ) carga ON it.id_ingeniero = carga.id_ingeniero
    WHERE it.id_topico = v_id_topico
    AND carga.carga < 20 -- Límite de 20 asignaciones por ingeniero
    ORDER BY carga ASC
    LIMIT 3;
    
    -- Si se asignaron 3 ingenieros, cambiar estado a 'En Progreso'
    SELECT COUNT(*) INTO v_count 
    FROM Asignacion_Error 
    WHERE id_error = p_id_error;
    
    IF v_count = 3 THEN
        UPDATE Solicitud_Error
        SET estado = 'En Progreso'
        WHERE id_error = p_id_error
        AND estado = 'Abierto';
    END IF;
END$$
DELIMITER ;

-- Procedimiento para ejecutar asignación automática para todas las solicitudes pendientes
DELIMITER $$
CREATE PROCEDURE ejecutar_asignacion_automatica()
BEGIN
    -- Declarar todas las variables al inicio
    DECLARE func_id INT;
    DECLARE err_id INT;
    DECLARE done BOOLEAN DEFAULT FALSE;
    
    -- Declarar cursores
    DECLARE cur_func CURSOR FOR 
        SELECT id_funcionalidad 
        FROM Solicitud_Funcionalidad 
        WHERE estado = 'Abierto';
        
    DECLARE cur_err CURSOR FOR 
        SELECT id_error 
        FROM Solicitud_Error 
        WHERE estado = 'Abierto';
        
    -- Declarar handler (uno solo que servirá para ambos cursores)
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Para las funcionalidades
    OPEN cur_func;
    SET done = FALSE;  -- Resetear el flag
    
    read_func: LOOP
        FETCH cur_func INTO func_id;
        IF done THEN
            LEAVE read_func;
        END IF;
        
        CALL asignar_ing_funcionalidad(func_id);
    END LOOP;
    
    CLOSE cur_func;
    
    -- Para los errores
    OPEN cur_err;
    SET done = FALSE;  -- Resetear el flag
    
    read_err: LOOP
        FETCH cur_err INTO err_id;
        IF done THEN
            LEAVE read_err;
        END IF;
        
        CALL asignar_ing_error(err_id);
    END LOOP;
    
    CLOSE cur_err;
END$$
DELIMITER ;

-- Trigger para verificar el límite de solicitudes por día (errores)
DELIMITER $$
CREATE TRIGGER tr_limite_solicitudes_error_por_dia
BEFORE INSERT ON Solicitud_Error
FOR EACH ROW
BEGIN
    DECLARE solicitudes_hoy INT;
    
    SELECT COUNT(*) INTO solicitudes_hoy
    FROM Solicitud_Error
    WHERE id_usuario = NEW.id_usuario
    AND DATE(fecha_publicacion) = CURDATE();
    
    IF solicitudes_hoy >= 25 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Límite de 25 solicitudes de error por día alcanzado';
    END IF;
END$$
DELIMITER ;

-- Trigger para verificar que el título de funcionalidad tenga al menos 20 caracteres
DELIMITER $$
CREATE TRIGGER tr_verificar_titulo_funcionalidad
BEFORE INSERT ON Solicitud_Funcionalidad
FOR EACH ROW
BEGIN
    IF CHAR_LENGTH(NEW.titulo) < 20 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'El título de la funcionalidad debe tener al menos 20 caracteres';
    END IF;
END$$
DELIMITER ;

-- Trigger para verificar que haya al menos 3 criterios de aceptación
DELIMITER $$
CREATE TRIGGER tr_verificar_criterios_funcionalidad
BEFORE INSERT ON Solicitud_Funcionalidad
FOR EACH ROW
BEGIN
    DECLARE num_criterios INT DEFAULT 0;
    
    -- Contar criterios asociados a esta funcionalidad
    -- Nota: Este trigger se ejecuta antes del INSERT, por lo que debemos verificar
    -- después de la inserción usando otro mecanismo o asumiendo validación en aplicación
    -- Por ahora, deshabilitamos la verificación automática aquí
    -- y la manejamos a nivel de aplicación
END$$
DELIMITER ;

-- Trigger para limitar a 2 especialidades por ingeniero
DELIMITER $$
CREATE TRIGGER tr_limite_especialidades_ingeniero
BEFORE INSERT ON Ingeniero_Topico
FOR EACH ROW
BEGIN
    DECLARE especialidades INT;
    
    SELECT COUNT(*) INTO especialidades
    FROM Ingeniero_Topico
    WHERE id_ingeniero = NEW.id_ingeniero;
    
    IF especialidades >= 2 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Un ingeniero no puede tener más de 2 especialidades';
    END IF;
END$$
DELIMITER ;

-- ========== FUNCIONES ==========

-- Función para contar el total de asignaciones de un ingeniero
DELIMITER $$
CREATE FUNCTION f_total_asignaciones_ingeniero(p_id_ingeniero INT) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE total INT;
    
    SELECT 
        (SELECT COUNT(*) FROM Asignacion_Funcionalidad WHERE id_ingeniero = p_id_ingeniero) +
        (SELECT COUNT(*) FROM Asignacion_Error WHERE id_ingeniero = p_id_ingeniero)
    INTO total;
    
    RETURN total;
END$$
DELIMITER ;

-- Función para obtener la carga de trabajo promedio de los ingenieros
DELIMITER $$
CREATE FUNCTION f_carga_promedio_ingenieros() RETURNS DECIMAL(5,2)
DETERMINISTIC
BEGIN
    DECLARE promedio DECIMAL(5,2);
    
    SELECT AVG(total_asignaciones) INTO promedio
    FROM (
        SELECT 
            i.id_ingeniero,
            (SELECT COUNT(*) FROM Asignacion_Funcionalidad WHERE id_ingeniero = i.id_ingeniero) +
            (SELECT COUNT(*) FROM Asignacion_Error WHERE id_ingeniero = i.id_ingeniero) AS total_asignaciones
        FROM 
            Ingeniero i
    ) AS cargas;
    
    RETURN promedio;
END$$
DELIMITER ;

-- ========== VISTAS ==========

-- Vista para obtener todas las solicitudes con sus detalles
CREATE VIEW v_solicitudes AS
SELECT 
    'Funcionalidad' AS tipo,
    f.id_funcionalidad AS id,
    f.titulo,
    f.resumen AS descripcion,
    f.ambiente,
    f.estado,
    f.fecha_publicacion,
    u.nombre AS nombre_usuario,
    t.nombre AS nombre_topico,
    f.id_usuario,
    f.id_topico
FROM 
    Solicitud_Funcionalidad f
    JOIN Usuario u ON f.id_usuario = u.id_usuario
    JOIN Topico t ON f.id_topico = t.id_topico

UNION ALL

SELECT 
    'Error' AS tipo,
    e.id_error AS id,
    e.titulo,
    e.descripcion,
    NULL AS ambiente,
    e.estado,
    e.fecha_publicacion,
    u.nombre AS nombre_usuario,
    t.nombre AS nombre_topico,
    e.id_usuario,
    e.id_topico
FROM 
    Solicitud_Error e
    JOIN Usuario u ON e.id_usuario = u.id_usuario
    JOIN Topico t ON e.id_topico = t.id_topico;

-- Vista para obtener todas las asignaciones con sus detalles
CREATE VIEW v_asignaciones AS
SELECT 
    'Funcionalidad' AS tipo,
    af.id_funcionalidad AS id_solicitud,
    f.titulo,
    f.estado,
    f.fecha_publicacion,
    af.id_ingeniero,
    i.nombre AS nombre_ingeniero,
    t.nombre AS nombre_topico,
    u.nombre AS nombre_usuario,
    f.id_usuario
FROM 
    Asignacion_Funcionalidad af
    JOIN Solicitud_Funcionalidad f ON af.id_funcionalidad = f.id_funcionalidad
    JOIN Ingeniero i ON af.id_ingeniero = i.id_ingeniero
    JOIN Topico t ON f.id_topico = t.id_topico
    JOIN Usuario u ON f.id_usuario = u.id_usuario

UNION ALL

SELECT 
    'Error' AS tipo,
    ae.id_error AS id_solicitud,
    e.titulo,
    e.estado,
    e.fecha_publicacion,
    ae.id_ingeniero,
    i.nombre AS nombre_ingeniero,
    t.nombre AS nombre_topico,
    u.nombre AS nombre_usuario,
    e.id_usuario
FROM 
    Asignacion_Error ae
    JOIN Solicitud_Error e ON ae.id_error = e.id_error
    JOIN Ingeniero i ON ae.id_ingeniero = i.id_ingeniero
    JOIN Topico t ON e.id_topico = t.id_topico
    JOIN Usuario u ON e.id_usuario = u.id_usuario;

-- Vista para obtener estadísticas de carga de trabajo de ingenieros
CREATE VIEW v_carga_ingenieros AS
SELECT 
    i.id_ingeniero,
    i.nombre,
    i.email,
    GROUP_CONCAT(DISTINCT t.nombre ORDER BY t.nombre SEPARATOR ', ') AS especialidades,
    COUNT(DISTINCT af.id_funcionalidad) AS funcionalidades_asignadas,
    COUNT(DISTINCT ae.id_error) AS errores_asignados,
    COUNT(DISTINCT af.id_funcionalidad) + COUNT(DISTINCT ae.id_error) AS total_asignaciones
FROM 
    Ingeniero i
    LEFT JOIN Ingeniero_Topico it ON i.id_ingeniero = it.id_ingeniero
    LEFT JOIN Topico t ON it.id_topico = t.id_topico
    LEFT JOIN Asignacion_Funcionalidad af ON i.id_ingeniero = af.id_ingeniero
    LEFT JOIN Asignacion_Error ae ON i.id_ingeniero = ae.id_ingeniero
GROUP BY 
    i.id_ingeniero, i.nombre, i.email
ORDER BY 
    total_asignaciones DESC;

-- ========== DATOS INICIALES ==========

Pegar lo que salga de generar_datos.php
| | | | | | | | | | | | | | | | |
v v v v v v v v v v v v v v v v v 
-- =======================================================
-- INSERTS PARA LA TABLA 'Topico'
-- =======================================================
INSERT INTO Topico (nombre) VALUES
('Backend'),
('Seguridad'),
('UX/UI'),
('Frontend'),
('Mobile'),
('DevOps'),
('Cloud'),
('Infraestructura'),
('Base de Datos'),
('Testing');

-- =======================================================
-- INSERTS PARA LA TABLA 'usuario' (20 registros)
-- =======================================================
INSERT INTO usuario (rut, nombre, email, password) VALUES ('8.008.002-6', 'Ms. Britney Conn', 'icruickshank@example.net', 'YGllu3KSc851');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('21.472.831-1', 'Elouise Bode', 'koch.rosendo@example.org', '7RqThDfQ7p6z');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('14.341.422-1', 'Fiona Gottlieb', 'elias.kunde@example.net', 'yxqTMNjys0J5');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('20.585.186-0', 'William Boyer', 'montana.moen@example.com', 'MCIjih0Y5zrC');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('22.602.593-6', 'Arden Grant MD', 'brenda56@example.org', 'edePZMibsTeD');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('3.946.346-6', 'Carole Daugherty', 'cartwright.llewellyn@example.org', 'jtCtUBQIa3BU');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('12.108.981-5', 'Aniya Schmitt', 'hamill.ludwig@example.org', 'SuTFC08WBIzK');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('18.114.649-4', 'Elian Turner I', 'terdman@example.com', 'grbj8tDyac1e');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('23.565.488-1', 'Marcia Lockman DDS', 'sbarton@example.com', '1l4NNbk1W4Z5');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('21.337.845-7', 'Ova Wuckert', 'eohara@example.org', 'hlU3yAgWcq3b');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('20.924.358-K', 'Ms. Marjory Considine Sr.', 'hheaney@example.com', 'LcswxFIc9GYD');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('23.049.572-6', 'Joanny Green Sr.', 'dwilliamson@example.com', 'JxsQ33vnyt1k');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('23.735.000-6', 'Dr. Aleen O\'Connell', 'erippin@example.net', 'vCwO8mYXE61B');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('4.058.977-5', 'Karl Keebler PhD', 'tbrown@example.net', 'WhtD7YqYPBIX');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('13.492.413-6', 'Danielle Stiedemann', 'sporer.caterina@example.net', 'wllBIMYPXQL6');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('23.972.326-8', 'Prof. Annalise Padberg I', 'agutmann@example.org', 'zyoQyAHqdr7Y');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('19.620.289-7', 'Elouise Gorczany', 'clementina47@example.org', 'lMvlMkR1HgNF');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('23.965.959-4', 'Miss Magnolia Schowalter DVM', 'fisher.nya@example.com', 'DBbjC7IK7pyh');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('1.798.954-5', 'Prof. Americo Mayert Sr.', 'evans.vonrueden@example.com', 'CAmDb7YFYtMk');
INSERT INTO usuario (rut, nombre, email, password) VALUES ('2.520.006-3', 'Kiel Tremblay', 'dprice@example.net', 'nu6RAAkjEW3q');

-- =======================================================
-- INSERTS PARA LA TABLA 'ingeniero' (8 registros)
-- =======================================================
INSERT INTO ingeniero (rut, nombre, email, password) VALUES ('11.377.986-1', 'Lew Schowalter', 'clementina39@example.org', 'yzewGM6PEIu9');
INSERT INTO ingeniero (rut, nombre, email, password) VALUES ('14.581.950-4', 'Nikita Smitham', 'janae10@example.com', 'yOyGgLFEBwtm');
INSERT INTO ingeniero (rut, nombre, email, password) VALUES ('20.769.117-8', 'Alexie Leuschke', 'vboyle@example.com', 'mJKJxbtVmiuV');
INSERT INTO ingeniero (rut, nombre, email, password) VALUES ('5.042.054-K', 'Johathan Abernathy', 'charlene55@example.org', 'IhAoLj2R2uUx');
INSERT INTO ingeniero (rut, nombre, email, password) VALUES ('20.232.977-2', 'Miss Mia O\'Conner', 'hansen.charlene@example.net', 'JI8NaQO400Ge');
INSERT INTO ingeniero (rut, nombre, email, password) VALUES ('11.560.145-8', 'Jacynthe Von', 'dorcas61@example.com', 'F05IP2JwzQtp');
INSERT INTO ingeniero (rut, nombre, email, password) VALUES ('12.195.237-8', 'Quinten Wolff DDS', 'gunner07@example.net', 'AN4DcVf7IEct');
INSERT INTO ingeniero (rut, nombre, email, password) VALUES ('12.133.110-1', 'Mrs. Betty Aufderhar IV', 'dframi@example.org', 'ian6iUQ8qPwb');

-- =======================================================
-- INSERTS PARA LA TABLA DE RELACIÓN 'ingeniero_topico'
-- =======================================================
INSERT INTO ingeniero_topico (id_ingeniero, id_topico) VALUES (1, 9);
INSERT INTO ingeniero_topico (id_ingeniero, id_topico) VALUES (1, 7);
INSERT INTO ingeniero_topico (id_ingeniero, id_topico) VALUES (2, 3);
INSERT INTO ingeniero_topico (id_ingeniero, id_topico) VALUES (3, 7);
INSERT INTO ingeniero_topico (id_ingeniero, id_topico) VALUES (4, 3);
INSERT INTO ingeniero_topico (id_ingeniero, id_topico) VALUES (4, 8);
INSERT INTO ingeniero_topico (id_ingeniero, id_topico) VALUES (5, 8);
INSERT INTO ingeniero_topico (id_ingeniero, id_topico) VALUES (5, 9);
INSERT INTO ingeniero_topico (id_ingeniero, id_topico) VALUES (6, 7);
INSERT INTO ingeniero_topico (id_ingeniero, id_topico) VALUES (6, 9);
INSERT INTO ingeniero_topico (id_ingeniero, id_topico) VALUES (7, 4);
INSERT INTO ingeniero_topico (id_ingeniero, id_topico) VALUES (8, 3);

-- =======================================================
-- INSERTS Y ASIGNACIONES PARA 'solicitud_funcionalidad' (15 registros)
-- =======================================================
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Soluta aperiam et quia facere et molestias velit dolor qui error et quo magnam dicta ipsam qui non tempora quia ut.', 'Web', 'Pariatur nihil qui quo amet est sapiente debitis. Ut id dolorem expedita id ut tenetur.', 17, 10);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (1, 'Aperiam quisquam quia autem quo reprehenderit.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (1, 'Voluptatem porro rerum ea a in rem.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (1, 'Veritatis quod atque at aut deleniti.', 3);
CALL asignar_ing_funcionalidad(1);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Nobis et sit hic quae ex consequatur ut non dolorum corporis ut quidem et atque quisquam est et quos quo consequatur.', 'Web', 'Qui at aut eum unde aliquid dolorem. Sint cupiditate sed dicta quia amet esse qui.', 10, 9);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (2, 'Architecto et suscipit consequatur nemo dolor.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (2, 'Et aliquid reprehenderit impedit fugit delectus.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (2, 'Eligendi et et ut minima sit dignissimos accusantium.', 3);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (2, 'Voluptas consectetur sed hic quis.', 4);
CALL asignar_ing_funcionalidad(2);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Voluptatem facilis fugiat dolores quas itaque qui quia sequi corrupti magni voluptas doloremque illo laboriosam est qui officia alias commodi vel.', 'Web', 'Qui rerum qui enim distinctio. Non deserunt veritatis vitae quia maiores.', 7, 5);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (3, 'Quod earum et eligendi dolor doloribus dolor.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (3, 'Quae assumenda voluptatem et consequatur sapiente repellendus.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (3, 'Reiciendis autem consequatur eos expedita consequatur impedit.', 3);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (3, 'Occaecati vero numquam eaque non et illum.', 4);
CALL asignar_ing_funcionalidad(3);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Repellendus non dolores praesentium culpa qui quo sint architecto quibusdam non optio sit architecto quasi doloribus voluptatem reiciendis magnam laborum occaecati.', 'Web', 'Necessitatibus laboriosam iusto officia cupiditate rerum. Consequatur in tempora a eos dolore porro.', 20, 6);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (4, 'Aliquid quis atque consequuntur quibusdam assumenda.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (4, 'Praesentium nemo voluptatem asperiores sapiente alias.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (4, 'Sed molestias aut rerum exercitationem et a.', 3);
CALL asignar_ing_funcionalidad(4);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Consequatur hic expedita ad quia qui nihil itaque quia ea voluptas rerum dignissimos est ut reprehenderit natus nihil sit quo ea.', 'Móvil', 'Omnis qui libero quo asperiores id. Eos voluptatem occaecati quaerat aut consequatur.', 7, 7);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (5, 'Iure iusto in voluptatum animi quaerat.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (5, 'Sunt laboriosam sunt omnis tenetur consectetur ea.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (5, 'Explicabo dolorem assumenda reprehenderit.', 3);
CALL asignar_ing_funcionalidad(5);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Consequatur nesciunt eum libero suscipit consequatur non quis exercitationem aut omnis magnam qui dolor facere corporis delectus fugiat nihil dolorem quia.', 'Móvil', 'Est et eius quia similique et. Autem natus aliquam in explicabo deleniti fuga cumque a. Facilis omnis optio quis est fugit.', 10, 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (6, 'Perferendis veritatis velit impedit non.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (6, 'Est odit veniam nihil vitae.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (6, 'Ea recusandae qui rerum officiis.', 3);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (6, 'Mollitia quis eaque tempora.', 4);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (6, 'Fugit ratione cumque quia aliquid sequi velit omnis a.', 5);
CALL asignar_ing_funcionalidad(6);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Quis qui quam molestiae molestias pariatur alias blanditiis blanditiis repudiandae sint corporis voluptatem non omnis quidem voluptatum suscipit blanditiis ut quod.', 'Móvil', 'Illum doloremque repudiandae ex nam nostrum quis. Omnis sunt dolores libero ut voluptatum.', 3, 5);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (7, 'Provident et dolorum saepe eum laudantium.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (7, 'Nihil id molestiae magnam quod et.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (7, 'Molestias aut temporibus nulla assumenda repudiandae.', 3);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (7, 'Velit rem quos neque vitae aut totam.', 4);
CALL asignar_ing_funcionalidad(7);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Quod voluptatem sint aut voluptas ut alias nesciunt cum cumque nam quasi nemo possimus et voluptatem eos facilis libero accusantium aut.', 'Móvil', 'Consequuntur quaerat iste odio et praesentium vitae exercitationem corporis. Quis expedita quidem consequatur facilis occaecati.', 8, 5);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (8, 'Illo molestiae cumque ut aut ut voluptatibus architecto.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (8, 'Vitae itaque ad maiores at.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (8, 'Expedita odio et eum nulla et.', 3);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (8, 'Omnis cumque sapiente et.', 4);
CALL asignar_ing_funcionalidad(8);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Assumenda iure rerum explicabo quo officiis velit maiores modi illo dolor architecto iure eos culpa aperiam qui pariatur eum est eum.', 'Móvil', 'Possimus illo id amet et minima cum. Labore ut incidunt quis sint sit. In nam delectus sunt vero est laboriosam minima.', 1, 5);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (9, 'Aspernatur qui nulla vel nemo non placeat perspiciatis.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (9, 'Accusamus voluptatem et ut alias sit.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (9, 'Eos quo autem vel aut enim.', 3);
CALL asignar_ing_funcionalidad(9);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Vitae ut dicta quo ut voluptatem beatae in et odit rerum consequatur ea unde soluta ducimus voluptates hic magni eligendi dolorum.', 'Móvil', 'Dolore voluptatem veniam nisi. Unde qui vero illum ad. Quia vel asperiores mollitia autem sint.', 4, 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (10, 'Excepturi dicta voluptates inventore quod.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (10, 'Accusantium reprehenderit id et.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (10, 'Delectus blanditiis molestiae delectus nobis eligendi corrupti nulla.', 3);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (10, 'Qui unde reprehenderit perspiciatis ipsa placeat consequuntur eius.', 4); 
CALL asignar_ing_funcionalidad(10);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Error perferendis nobis quia et ea eligendi reprehenderit voluptatem id qui odio quasi et et ut nostrum rerum sint sed sint.', 'Móvil', 'Velit porro incidunt ut sunt et. Voluptas optio magni et accusamus et ad.', 20, 10);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (11, 'Tenetur omnis ex aliquam.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (11, 'Voluptates dignissimos vitae quo ut.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (11, 'Repudiandae fuga quis corrupti aut doloremque non.', 3);
CALL asignar_ing_funcionalidad(11);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Delectus optio consequatur facere aut id ducimus neque quaerat et exercitationem nisi nulla est autem sed ut non nemo repellendus et.', 'Web', 'Earum quae non quaerat maxime alias ex. Aperiam reprehenderit reiciendis consequuntur mollitia sequi veniam. Eligendi pariatur qui molestias dolore.', 3, 9);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (12, 'Quidem necessitatibus perspiciatis ipsam officiis.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (12, 'In quasi voluptatem rem dignissimos qui non tempore.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (12, 'Error ea aut quos ut et.', 3);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (12, 'Accusamus dolorem nisi cum delectus consequatur quas.', 4);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (12, 'Atque quaerat harum eligendi soluta at quaerat.', 5);
CALL asignar_ing_funcionalidad(12);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Quia iure illum aut laudantium et soluta nisi deleniti et et incidunt deleniti nam tenetur est dignissimos porro odit reprehenderit pariatur.', 'Web', 'Nemo error suscipit explicabo cumque autem. Possimus nihil vitae doloribus et ad repudiandae. Ullam quis quo magni ea.', 11, 6);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (13, 'Quia magni aperiam illo quae perspiciatis velit incidunt et.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (13, 'Rerum voluptate iusto est culpa ea dolore.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (13, 'Ut sit iste rerum dignissimos.', 3);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (13, 'Dicta aperiam natus totam possimus.', 4);
CALL asignar_ing_funcionalidad(13);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Non consequatur doloribus illum sequi ipsum veritatis accusamus dolor quia eum occaecati excepturi necessitatibus harum unde dicta necessitatibus in in quia.', 'Móvil', 'Et voluptate non enim inventore est. Est omnis minus eos eum non consectetur.', 8, 10);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (14, 'Eius quod iure sit cum doloribus similique aut.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (14, 'Atque laborum reiciendis pariatur dolores eligendi pariatur.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (14, 'Ut quibusdam exercitationem recusandae totam rerum quisquam.', 3);
CALL asignar_ing_funcionalidad(14);
INSERT INTO solicitud_funcionalidad (titulo, ambiente, resumen, id_usuario, id_topico) VALUES ('Quibusdam incidunt autem sequi tempore ab iure minus facere maiores et necessitatibus et quo dolor iusto recusandae recusandae fuga sapiente nemo.', 'Web', 'Pariatur harum cupiditate libero odit fuga et. Doloribus deleniti sint ut sed sapiente veniam. Id illo voluptatibus quia.', 5, 9);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (15, 'Quia officiis architecto voluptatem dolorem.', 1);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (15, 'Dignissimos voluptas in minima sint.', 2);
INSERT INTO criterios_funcionalidad (id_funcionalidad, descripcion, orden) VALUES (15, 'Doloremque debitis ipsam dolores et odio sint molestias rerum.', 3);
CALL asignar_ing_funcionalidad(15);

-- =======================================================
-- INSERTS Y ASIGNACIONES PARA 'solicitud_error' (10 registros)
-- =======================================================
INSERT INTO solicitud_error (titulo, descripcion, id_usuario, id_topico) VALUES ('Error en quis autem omnis', 'Temporibus consectetur esse voluptas aliquam. Nulla magni in dolores quos aut est qui. Atque voluptatem omnis ut alias aperiam veniam rerum. Necessitatibus eos vero autem minus in quibusdam quos facere.', 1, 8);
CALL asignar_ing_error(1);
INSERT INTO solicitud_error (titulo, descripcion, id_usuario, id_topico) VALUES ('Error en rerum magnam sed', 'Quis ducimus quaerat qui suscipit alias voluptas. Necessitatibus fuga architecto odit alias ea. Laborum natus dolor sit minima eveniet optio quo. Quibusdam sint non voluptas nulla quaerat rerum. Illum magni ea fugiat ut corrupti.', 12, 8);    
CALL asignar_ing_error(2);
INSERT INTO solicitud_error (titulo, descripcion, id_usuario, id_topico) VALUES ('Error en hic odit suscipit', 'Quasi qui aut voluptas ducimus cumque ut. Et repellat sequi rerum architecto quia. Rerum culpa animi est nihil ut.', 9, 7); 
CALL asignar_ing_error(3);
INSERT INTO solicitud_error (titulo, descripcion, id_usuario, id_topico) VALUES ('Error en eveniet id veritatis', 'Odio rem nesciunt nulla nostrum sint corrupti hic non. Est necessitatibus consectetur est ipsam. Quia repellat facilis nihil autem dolore. Qui illum voluptates consequatur molestias omnis nam quas.', 5, 2);
CALL asignar_ing_error(4);
INSERT INTO solicitud_error (titulo, descripcion, id_usuario, id_topico) VALUES ('Error en delectus ea cum', 'Non adipisci ullam et sunt ipsa quia totam. Quis vitae iste omnis possimus aut beatae. Dolor enim non eum in. Odit sed ut dolor asperiores non fugit ut voluptatem.', 7, 10);
CALL asignar_ing_error(5);
INSERT INTO solicitud_error (titulo, descripcion, id_usuario, id_topico) VALUES ('Error en asperiores facilis ducimus', 'Sit ut doloribus ut pariatur amet atque. Sed deserunt molestiae magni ipsam quos.', 12, 6);
CALL asignar_ing_error(6);
INSERT INTO solicitud_error (titulo, descripcion, id_usuario, id_topico) VALUES ('Error en eligendi ipsa aperiam', 'Deserunt voluptatibus libero ullam alias maxime cum repellat. Dolorem dolor quidem nihil nihil. Laudantium libero qui voluptas veniam ad eligendi. Minus facilis eaque corrupti corporis maxime dolores a.', 18, 3);
CALL asignar_ing_error(7);
INSERT INTO solicitud_error (titulo, descripcion, id_usuario, id_topico) VALUES ('Error en recusandae ut architecto', 'Non veniam impedit consequuntur aut nostrum nesciunt. Et aut nisi cumque voluptatem. Velit delectus eius perspiciatis et aspernatur eum voluptatem.', 20, 2);
CALL asignar_ing_error(8);
INSERT INTO solicitud_error (titulo, descripcion, id_usuario, id_topico) VALUES ('Error en rem eligendi necessitatibus', 'Dolore non praesentium nobis culpa eius quos. Sunt ratione quibusdam molestiae excepturi ipsam voluptatum itaque repellendus. Quasi dolores explicabo quo eligendi adipisci.', 16, 9);
CALL asignar_ing_error(9);
INSERT INTO solicitud_error (titulo, descripcion, id_usuario, id_topico) VALUES ('Error en quo voluptatem inventore', 'Non dolores aperiam rem. Quis officia veritatis tenetur optio maxime temporibus deserunt.', 13, 5);
CALL asignar_ing_error(10);