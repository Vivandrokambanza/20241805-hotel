-- Hotel Management System - Useful Queries
-- Hotel Manager - PDW Final 2026
-- Vivandro Kambanza - 20241805

USE hotel_manager;

-- 1. List all rooms with their type name and current status
SELECT r.room_number, rt.name AS room_type, r.floor, r.status
FROM rooms r
INNER JOIN room_types rt ON r.room_type_id = rt.id
ORDER BY r.floor, r.room_number;

-- 2. List all pending reservations with client name and room type
SELECT res.id, u.name AS client, rt.name AS room_type,
       res.start_date, res.end_date, res.num_guests, res.total_estimated, res.status
FROM reservations res
INNER JOIN users u ON res.user_id = u.id
INNER JOIN room_types rt ON res.room_type_id = rt.id
WHERE res.status = 'pending'
ORDER BY res.start_date;

-- 3. Check room availability for a given period
SELECT rt.name AS room_type, rt.base_daily_rate,
       COUNT(r.id) AS total_rooms,
       COUNT(r.id) - COUNT(rr.id) AS available_rooms
FROM room_types rt
INNER JOIN rooms r ON r.room_type_id = rt.id
LEFT JOIN reservation_rooms rr ON rr.room_id = r.id
LEFT JOIN reservations res ON res.id = rr.reservation_id
    AND res.status IN ('active','checked_in')
    AND res.start_date < '2026-06-10'
    AND res.end_date   > '2026-06-07'
WHERE rt.status = 'active'
GROUP BY rt.id, rt.name, rt.base_daily_rate;

-- 4. Monthly revenue report
SELECT YEAR(p.payment_date)  AS year,
       MONTH(p.payment_date) AS month,
       SUM(p.amount)         AS total_revenue,
       COUNT(p.id)           AS num_payments
FROM payments p
GROUP BY YEAR(p.payment_date), MONTH(p.payment_date)
ORDER BY year DESC, month DESC;

-- 5. Top 10 guests by number of stays
SELECT u.name, u.email, COUNT(res.id) AS num_stays,
       SUM(res.total_paid) AS total_spent
FROM reservations res
INNER JOIN users u ON res.user_id = u.id
WHERE res.status = 'completed'
GROUP BY u.id, u.name, u.email
ORDER BY num_stays DESC
LIMIT 10;

-- 6. Today's check-ins (reservations that should check in today)
SELECT res.id, u.name AS client, rt.name AS room_type,
       res.num_guests, res.include_breakfast, res.total_estimated
FROM reservations res
INNER JOIN users u ON res.user_id = u.id
INNER JOIN room_types rt ON res.room_type_id = rt.id
WHERE res.start_date = CURDATE()
  AND res.status = 'active';

-- 7. Today's check-outs (guests currently checked in who leave today)
SELECT res.id, u.name AS client, rt.name AS room_type,
       res.start_date, res.end_date,
       res.total_estimated, res.total_paid,
       (res.total_estimated - res.total_paid) AS remaining
FROM reservations res
INNER JOIN users u ON res.user_id = u.id
INNER JOIN room_types rt ON res.room_type_id = rt.id
WHERE res.end_date = CURDATE()
  AND res.status = 'checked_in';

-- 8. Occupancy rate by room type this month
SELECT rt.name AS room_type,
       COUNT(DISTINCT r.id) AS total_rooms,
       COUNT(DISTINCT rr.room_id) AS occupied_rooms,
       ROUND(COUNT(DISTINCT rr.room_id) / COUNT(DISTINCT r.id) * 100, 1) AS occupancy_pct
FROM room_types rt
INNER JOIN rooms r ON r.room_type_id = rt.id
LEFT JOIN reservation_rooms rr ON rr.room_id = r.id
LEFT JOIN reservations res ON res.id = rr.reservation_id
    AND res.status IN ('checked_in','completed')
    AND MONTH(res.start_date) = MONTH(CURDATE())
    AND YEAR(res.start_date)  = YEAR(CURDATE())
GROUP BY rt.id, rt.name;

-- 9. Payments made by a specific receptionist/operator
SELECT p.id, u_client.name AS client, p.amount,
       p.payment_date, p.payment_type, p.payment_method,
       u_op.name AS operator
FROM payments p
INNER JOIN reservations res ON p.reservation_id = res.id
INNER JOIN users u_client ON res.user_id = u_client.id
INNER JOIN users u_op ON p.operator_id = u_op.id
ORDER BY p.payment_date DESC;

-- 10. Full audit log with user names
SELECT al.created_at, u.name AS performed_by, al.action,
       al.entity, al.entity_id, al.details, al.ip_address
FROM audit_logs al
LEFT JOIN users u ON al.user_id = u.id
ORDER BY al.created_at DESC
LIMIT 100;
