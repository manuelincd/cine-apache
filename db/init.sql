SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('visitante','cliente','admin') DEFAULT 'cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS peliculas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    genero VARCHAR(100),
    duracion INT,
    imagen VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS salas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    capacidad INT DEFAULT 80,
    filas INT DEFAULT 8,
    columnas INT DEFAULT 10
);

CREATE TABLE IF NOT EXISTS funciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pelicula_id INT NOT NULL,
    sala_id INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pelicula_id) REFERENCES peliculas(id),
    FOREIGN KEY (sala_id) REFERENCES salas(id)
);

CREATE TABLE IF NOT EXISTS cupones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    descuento_pct INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    usos_max INT NOT NULL,
    usos_actual INT DEFAULT 0,
    hora_limite TIME DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    funcion_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    cupon_id INT DEFAULT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (funcion_id) REFERENCES funciones(id),
    FOREIGN KEY (cupon_id) REFERENCES cupones(id)
);

CREATE TABLE IF NOT EXISTS asientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    funcion_id INT NOT NULL,
    fila CHAR(1) NOT NULL,
    columna INT NOT NULL,
    estado ENUM('libre','ocupado') DEFAULT 'libre',
    FOREIGN KEY (funcion_id) REFERENCES funciones(id)
);

CREATE TABLE IF NOT EXISTS detalle_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    asiento_id INT NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id),
    FOREIGN KEY (asiento_id) REFERENCES asientos(id)
);

INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@sendera.com', '$2y$10$a9gOP4ojmrjxn1UkWLfD9.PyrND/ZKOSrz0mM39ycJcsqdjbTe7sa', 'admin');

INSERT INTO peliculas (titulo, genero, duracion, imagen) VALUES
('El Diablo Viste a la Moda 2', 'Comedia, Drama', 119, 'diablo-viste-a-la-moda.jpg'),
('Star Wars: The Mandalorian & Grogu', 'Ciencia Ficción, Aventura', 132, 'mandalorian-grogu.jpg'),
('Michael', 'Biopic, Drama, Música', 127, 'michael-jackson.jpg'),
('Obsesión', 'Terror', 108, 'obsesion.jpg'),
('El Pasajero del Diablo', 'Thriller, Suspenso', 95, 'pasajero-diablo.jpg'),
('The Super Mario Galaxy Movie', 'Animación, Aventura', 98, 'super-mario-galaxy.jpg');

INSERT INTO salas (nombre, capacidad, filas, columnas) VALUES
('Sala Principal', 80, 8, 10);

INSERT INTO funciones (pelicula_id, sala_id, fecha_hora, precio) VALUES
(1, 1, DATE_ADD(NOW(), INTERVAL 1 DAY), 75.00),
(2, 1, DATE_ADD(NOW(), INTERVAL 2 DAY), 75.00),
(3, 1, DATE_ADD(NOW(), INTERVAL 3 DAY), 75.00),
(4, 1, DATE_ADD(NOW(), INTERVAL 4 DAY), 75.00),
(5, 1, DATE_ADD(NOW(), INTERVAL 5 DAY), 75.00),
(6, 1, DATE_ADD(NOW(), INTERVAL 6 DAY), 75.00);

INSERT INTO cupones (codigo, descuento_pct, activo, usos_max, usos_actual) VALUES
('SENDERA10', 10, 1, 100, 0),
('PROMO20', 20, 1, 50, 0),
('MATINE50', 50, 1, 1000, 0, '12:00:00');

INSERT INTO asientos (funcion_id, fila, columna, estado)
SELECT f.id, filas.fila, cols.col, 'libre'
FROM funciones f
CROSS JOIN (
    SELECT 'A' AS fila UNION SELECT 'B' UNION SELECT 'C' UNION SELECT 'D'
    UNION SELECT 'E' UNION SELECT 'F' UNION SELECT 'G' UNION SELECT 'H'
) filas
CROSS JOIN (
    SELECT 1 AS col UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
) cols;
