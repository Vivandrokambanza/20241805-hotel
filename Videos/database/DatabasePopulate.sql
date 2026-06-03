-- Hotel Management System - Seed Data
-- Hotel Manager - PDW Final 2026
-- Vivandro Kambanza - 20241805

USE hotel_manager;

-- Default manager account (password: admin123)
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin Gestor', 'admin@hotel.pt', '$2y$10$dKLLJ.NV70mY/2hbbnha3exaw98D2djjU75jah.G3Sc2YgWxTeN7y', 'manager');

-- Default receptionist account (password: recep123)
INSERT INTO users (name, email, password_hash, role) VALUES
('Ana Rececionista', 'rececionista@hotel.pt', '$2y$10$NVai1SlbBB7JvStu2OucCO.cSzSiv35IeUU/2WftMQsJXgandz6Ya', 'receptionist');

-- Room types
-- Duplo:    base 2 guests, max 5, 80€/night
-- Casal:    base 2 guests, max 3, 90€/night
-- Familiar: base 4 guests, max 7, 130€/night
-- Suite:    base 2 guests, max 5, 200€/night
INSERT INTO room_types (name, base_capacity, max_capacity, base_daily_rate, breakfast_cost_per_guest, extra_guest_surcharge, description, amenities) VALUES
('Duplo',    2, 5, 80.00,  10.00, 20.00,
 'Quarto duplo com duas camas individuais, ideal para amigos ou colegas.',
 'Wi-Fi,TV,Ar Condicionado,Casa de Banho Privada'),

('Casal',    2, 3, 90.00,  10.00, 20.00,
 'Quarto de casal com cama de casal, ambiente romantico e acolhedor.',
 'Wi-Fi,TV,Ar Condicionado,Casa de Banho Privada,Minibar'),

('Familiar', 4, 7, 130.00, 10.00, 15.00,
 'Quarto familiar espacoso com cama de casal e beliche, perfeito para familias.',
 'Wi-Fi,TV,Ar Condicionado,Casa de Banho Privada,Varanda'),

('Solteiro-Suite', 2, 5, 200.00, 10.00, 25.00,
 'Suite de luxo com sala de estar separada, vista panoramica e servicos premium.',
 'Wi-Fi,TV 4K,Ar Condicionado,Casa de Banho de Luxo,Minibar,Varanda,Jacuzzi');

-- 20 rooms: 5 of each type across floors 1-4
-- Duplo rooms (type 1)
INSERT INTO rooms (room_number, room_type_id, floor) VALUES
('101', 1, 1), ('102', 1, 1), ('201', 1, 2), ('202', 1, 2), ('301', 1, 3);

-- Casal rooms (type 2)
INSERT INTO rooms (room_number, room_type_id, floor) VALUES
('103', 2, 1), ('104', 2, 1), ('203', 2, 2), ('204', 2, 2), ('302', 2, 3);

-- Familiar rooms (type 3)
INSERT INTO rooms (room_number, room_type_id, floor) VALUES
('105', 3, 1), ('106', 3, 1), ('205', 3, 2), ('206', 3, 2), ('303', 3, 3);

-- Solteiro-Suite rooms (type 4) - all on floor 4
INSERT INTO rooms (room_number, room_type_id, floor) VALUES
('401', 4, 4), ('402', 4, 4), ('403', 4, 4), ('404', 4, 4), ('405', 4, 4);
