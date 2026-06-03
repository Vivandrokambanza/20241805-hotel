INSERT INTO users (name, email, password_hash, role, phone) VALUES
('Joao Cliente', 'joao@teste.pt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'client', '912000001'),
('Maria Silva', 'maria@teste.pt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'client', '912000002');

INSERT INTO reservations (user_id, room_type_id, num_rooms, num_guests, start_date, end_date, include_breakfast, total_estimated, status) VALUES
(3, 1, 1, 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 0, 240.00, 'active'),
(4, 2, 1, 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 1, 230.00, 'active'),
(3, 3, 1, 3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), CURDATE(), 1, 310.00, 'checked_in'),
(4, 4, 1, 2, DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 8 DAY), 1, 750.00, 'pending');

INSERT INTO reservation_rooms (reservation_id, room_id, checkin_at)
VALUES (3, 5, DATE_SUB(NOW(), INTERVAL 2 DAY));

UPDATE rooms SET status = 'occupied' WHERE id = 5;
