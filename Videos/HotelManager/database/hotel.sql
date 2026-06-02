-- Hotel Management System Database
-- Hotel Manager - PDW Final 2026
-- Vivandro Kambanza - 20241805

CREATE DATABASE IF NOT EXISTS hotel_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_manager;

-- Users: clients, receptionists and managers
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role        ENUM('client','receptionist','manager') NOT NULL DEFAULT 'client',
    document_type ENUM('cc','passport','other') DEFAULT NULL,
    document_number VARCHAR(50) DEFAULT NULL,
    nif         VARCHAR(9)    DEFAULT NULL,
    phone       VARCHAR(20)   DEFAULT NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Room types: Duplo, Casal, Familiar, Suite
CREATE TABLE room_types (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    name                    VARCHAR(50)     NOT NULL,
    base_capacity           INT             NOT NULL,
    max_capacity            INT             NOT NULL,
    base_daily_rate         DECIMAL(10,2)   NOT NULL,
    breakfast_cost_per_guest DECIMAL(10,2)  NOT NULL DEFAULT 10.00,
    extra_guest_surcharge   DECIMAL(10,2)   NOT NULL DEFAULT 20.00,
    description             TEXT,
    amenities               VARCHAR(255)    DEFAULT NULL,
    status                  ENUM('active','inactive') NOT NULL DEFAULT 'active'
);

-- Individual rooms
CREATE TABLE rooms (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10)   NOT NULL UNIQUE,
    room_type_id INT          NOT NULL,
    floor       INT           NOT NULL DEFAULT 1,
    status      ENUM('available','occupied','maintenance') NOT NULL DEFAULT 'available',
    description TEXT,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

-- Reservations
CREATE TABLE reservations (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT             NOT NULL,
    room_type_id    INT             NOT NULL,
    num_rooms       INT             NOT NULL DEFAULT 1,
    num_guests      INT             NOT NULL DEFAULT 1,
    start_date      DATE            NOT NULL,
    end_date        DATE            NOT NULL,
    include_breakfast BOOLEAN       NOT NULL DEFAULT FALSE,
    nif             VARCHAR(9)      DEFAULT NULL,
    status          ENUM('pending','active','checked_in','completed','cancelled') NOT NULL DEFAULT 'pending',
    total_estimated DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    total_paid      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    notes           TEXT,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

-- Rooms assigned to reservations (at check-in)
CREATE TABLE reservation_rooms (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id  INT         NOT NULL,
    room_id         INT         NOT NULL,
    checkin_at      DATETIME    DEFAULT NULL,
    checkout_at     DATETIME    DEFAULT NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Payments (simulated)
CREATE TABLE payments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id  INT             NOT NULL,
    amount          DECIMAL(10,2)   NOT NULL,
    payment_date    DATE            NOT NULL,
    payment_type    ENUM('partial','total') NOT NULL,
    payment_method  ENUM('cash','card','transfer') NOT NULL DEFAULT 'cash',
    operator_id     INT             NOT NULL,
    notes           TEXT,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (operator_id) REFERENCES users(id)
);

-- Audit logs for critical actions
CREATE TABLE audit_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT         DEFAULT NULL,
    action      VARCHAR(80) NOT NULL,
    entity      VARCHAR(50) NOT NULL,
    entity_id   INT         DEFAULT NULL,
    details     TEXT,
    ip_address  VARCHAR(45) DEFAULT NULL,
    created_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- -------------------------------------------------------
-- SEED DATA
-- -------------------------------------------------------

-- Default manager (password: admin123)
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin Gestor', 'admin@hotel.pt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager');

-- Default receptionist (password: recep123)
INSERT INTO users (name, email, password_hash, role) VALUES
('Ana Rececionista', 'rececionista@hotel.pt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist');

-- Room types
INSERT INTO room_types (name, base_capacity, max_capacity, base_daily_rate, breakfast_cost_per_guest, extra_guest_surcharge, description, amenities) VALUES
('Duplo',    2, 4, 80.00,  10.00, 20.00, 'Quarto duplo com duas camas individuais, ideal para amigos ou colegas.', 'Wi-Fi,TV,Ar Condicionado,Casa de Banho Privada'),
('Casal',    2, 3, 90.00,  10.00, 20.00, 'Quarto de casal com cama de casal, ambiente romântico e acolhedor.',   'Wi-Fi,TV,Ar Condicionado,Casa de Banho Privada,Minibar'),
('Familiar', 4, 6, 130.00, 10.00, 15.00, 'Quarto familiar espaçoso com cama de casal e beliche, perfeito para famílias.', 'Wi-Fi,TV,Ar Condicionado,Casa de Banho Privada,Varanda'),
('Suite',    2, 5, 200.00, 10.00, 25.00, 'Suite de luxo com sala de estar separada, vista panorâmica e serviços premium.', 'Wi-Fi,TV 4K,Ar Condicionado,Casa de Banho de Luxo,Minibar,Varanda,Jacuzzi');

-- 20 rooms (5 of each type, floors 1-4)
INSERT INTO rooms (room_number, room_type_id, floor) VALUES
('101', 1, 1), ('102', 1, 1), ('201', 1, 2), ('202', 1, 2), ('301', 1, 3),
('103', 2, 1), ('104', 2, 1), ('203', 2, 2), ('204', 2, 2), ('302', 2, 3),
('105', 3, 1), ('106', 3, 1), ('205', 3, 2), ('206', 3, 2), ('303', 3, 3),
('401', 4, 4), ('402', 4, 4), ('403', 4, 4), ('404', 4, 4), ('405', 4, 4);
