-- Hotel Management System - Database Structure
-- Hotel Manager - PDW Final 2026
-- Vivandro Kambanza - 20241805

CREATE DATABASE IF NOT EXISTS hotel_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_manager;

-- Users: clients, receptionists and managers
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM('client','receptionist','manager') NOT NULL DEFAULT 'client',
    document_type ENUM('cc','passport','other') DEFAULT NULL,
    document_number VARCHAR(50) DEFAULT NULL,
    nif           VARCHAR(9)    DEFAULT NULL,
    phone         VARCHAR(20)   DEFAULT NULL,
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Room types: Duplo, Casal, Familiar, Suite
CREATE TABLE room_types (
    id                       INT AUTO_INCREMENT PRIMARY KEY,
    name                     VARCHAR(50)    NOT NULL,
    base_capacity            INT            NOT NULL,
    max_capacity             INT            NOT NULL,
    base_daily_rate          DECIMAL(10,2)  NOT NULL,
    breakfast_cost_per_guest DECIMAL(10,2)  NOT NULL DEFAULT 10.00,
    extra_guest_surcharge    DECIMAL(10,2)  NOT NULL DEFAULT 20.00,
    description              TEXT,
    amenities                VARCHAR(255)   DEFAULT NULL,
    status                   ENUM('active','inactive') NOT NULL DEFAULT 'active'
);

-- Individual rooms
CREATE TABLE rooms (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    room_number  VARCHAR(10)  NOT NULL UNIQUE,
    room_type_id INT          NOT NULL,
    floor        INT          NOT NULL DEFAULT 1,
    status       ENUM('available','occupied','maintenance') NOT NULL DEFAULT 'available',
    description  TEXT,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

-- Reservations
CREATE TABLE reservations (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT            NOT NULL,
    room_type_id      INT            NOT NULL,
    num_rooms         INT            NOT NULL DEFAULT 1,
    num_guests        INT            NOT NULL DEFAULT 1,
    num_children      INT            NOT NULL DEFAULT 0,
    start_date        DATE           NOT NULL,
    end_date          DATE           NOT NULL,
    include_breakfast BOOLEAN        NOT NULL DEFAULT FALSE,
    nif               VARCHAR(9)     DEFAULT NULL,
    guest_registered  BOOLEAN        NOT NULL DEFAULT FALSE,
    status            ENUM('pending','active','checked_in','completed','cancelled') NOT NULL DEFAULT 'pending',
    total_estimated   DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    total_paid        DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    notes             TEXT,
    created_at        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

-- Rooms assigned to reservations (assigned at check-in)
CREATE TABLE reservation_rooms (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT       NOT NULL,
    room_id        INT       NOT NULL,
    checkin_at     DATETIME  DEFAULT NULL,
    checkout_at    DATETIME  DEFAULT NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Payments (simulated)
CREATE TABLE payments (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT            NOT NULL,
    amount         DECIMAL(10,2)  NOT NULL,
    payment_date   DATE           NOT NULL,
    payment_type   ENUM('partial','total') NOT NULL,
    payment_method ENUM('cash','card','transfer') NOT NULL DEFAULT 'cash',
    operator_id    INT            NOT NULL,
    notes          TEXT,
    created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (operator_id) REFERENCES users(id)
);

-- Audit logs for critical actions
CREATE TABLE audit_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT         DEFAULT NULL,
    action     VARCHAR(80) NOT NULL,
    entity     VARCHAR(50) NOT NULL,
    entity_id  INT         DEFAULT NULL,
    details    TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
