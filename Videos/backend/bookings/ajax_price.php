<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$rtId      = (int)get('room_type_id');
$start     = get('start');
$end       = get('end');
$numRooms  = max(1, (int)get('num_rooms', 1));
$numGuests = max(1, (int)get('num_guests', 1));
$breakfast = (bool)(int)get('breakfast');

if (!$rtId || !validateDate($start) || !validateDate($end) || $start >= $end) {
    echo json_encode(['total' => 0, 'total_fmt' => '—']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM room_types WHERE id = ? AND status = "active"');
$stmt->execute([$rtId]);
$rt = $stmt->fetch();

if (!$rt) {
    echo json_encode(['total' => 0, 'total_fmt' => '—']);
    exit;
}

$total = calculateTotal($rt, $numRooms, $numGuests, $breakfast, $start, $end);
echo json_encode(['total' => $total, 'total_fmt' => formatMoney($total)]);
