<?php
$pageTitle = 'Início';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDB();

// Search params
$checkIn  = get('check_in');
$checkOut = get('check_out');
$guests   = max(1, (int)get('guests', 1));

// Available room types
$sql = 'SELECT * FROM room_types WHERE status = "active" ORDER BY base_daily_rate ASC';
$roomTypes = $pdo->query($sql)->fetchAll();

// If search params, filter by availability
$availabilities = [];
if ($checkIn && $checkOut && validateDate($checkIn) && validateDate($checkOut) && $checkIn < $checkOut) {
    foreach ($roomTypes as $rt) {
        $avail = availableRoomCount($rt['id'], $checkIn, $checkOut);
        $availabilities[$rt['id']] = $avail;
    }
    // Remove types with no rooms or insufficient capacity
    $roomTypes = array_filter($roomTypes, function ($rt) use ($guests, $availabilities) {
        return $availabilities[$rt['id']] > 0 && $rt['max_capacity'] >= $guests;
    });
}

$rtIcons = ['Duplo' => '🛏️', 'Casal' => '💑', 'Familiar' => '👨‍👩‍👧‍👦', 'Suite' => '👑'];

include __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <h1>A sua estadia perfeita começa aqui</h1>
        <p>Reserve o seu quarto no Hotel Vivandro — conforto, elegância e serviço de excelência no coração de Lisboa.</p>
        <form method="get" action="index.php" class="hero-search">
            <div class="form-group">
                <label class="form-label">Check-in</label>
                <input type="date" name="check_in" class="form-control"
                    value="<?= e($checkIn) ?>"
                    min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Check-out</label>
                <input type="date" name="check_out" class="form-control"
                    value="<?= e($checkOut) ?>"
                    min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Hóspedes</label>
                <input type="number" name="guests" class="form-control" value="<?= e($guests) ?>" min="1" max="7">
            </div>
            <div>
                <button type="submit" class="btn btn-primary btn-lg">Pesquisar</button>
            </div>
        </form>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($checkIn && $checkOut): ?>
            <h2 class="section-title">
                Resultados para <?= e(formatDate($checkIn)) ?> → <?= e(formatDate($checkOut)) ?> (<?= e($guests) ?> hóspede<?= $guests > 1 ? 's' : '' ?>)
            </h2>
            <?php if (empty($roomTypes)): ?>
                <div class="alert alert-warning">Não existem quartos disponíveis para os critérios escolhidos. Tente outras datas ou menos hóspedes.</div>
            <?php endif; ?>
        <?php else: ?>
            <h2 class="section-title">Os Nossos Quartos</h2>
            <p class="section-subtitle">Escolha o tipo de quarto ideal para a sua estadia</p>
        <?php endif; ?>

        <div class="grid-4">
        <?php foreach ($roomTypes as $rt): ?>
            <?php
            $nights = ($checkIn && $checkOut) ? nightsBetween($checkIn, $checkOut) : 1;
            $icon   = $rtIcons[$rt['name']] ?? '🏠';
            $amenitiesList = explode(',', $rt['amenities'] ?? '');
            ?>
            <div class="card">
                <div class="card-img"><?= $icon ?></div>
                <div class="card-body">
                    <div class="card-title"><?= e($rt['name']) ?></div>
                    <div class="card-badges">
                        <?php foreach (array_slice($amenitiesList, 0, 3) as $am): ?>
                            <span class="badge badge-blue"><?= e(trim($am)) ?></span>
                        <?php endforeach; ?>
                        <span class="badge badge-gray">Até <?= e($rt['max_capacity']) ?> hóspedes</span>
                    </div>
                    <p class="card-text"><?= e(mb_substr($rt['description'], 0, 90)) ?>…</p>
                    <div class="card-price">
                        <?= formatMoney($rt['base_daily_rate']) ?> <small>/ noite</small>
                    </div>
                    <?php if (isset($availabilities[$rt['id']])): ?>
                        <p style="font-size:.8rem;color:#555;margin-bottom:.5rem">
                            <?= e($availabilities[$rt['id']]) ?> quarto<?= $availabilities[$rt['id']] > 1 ? 's' : '' ?> disponível<?= $availabilities[$rt['id']] > 1 ? 'is' : '' ?>
                        </p>
                    <?php endif; ?>
                    <?php if (isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'client'): ?>
                        <a href="book.php?room_type_id=<?= $rt['id'] ?>&check_in=<?= urlencode($checkIn) ?>&check_out=<?= urlencode($checkOut) ?>&guests=<?= $guests ?>"
                            class="btn btn-primary btn-block">Reservar</a>
                    <?php elseif (!isLoggedIn()): ?>
                        <a href="login.php?redirect=<?= urlencode('book.php?room_type_id=' . $rt['id']) ?>"
                            class="btn btn-primary btn-block">Entrar para Reservar</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" style="background:#fff; padding: 3rem 0;">
    <div class="container">
        <h2 class="section-title text-center">Porquê Escolher-nos?</h2>
        <div class="grid-3" style="margin-top:1.5rem">
            <div class="text-center" style="padding:1.5rem">
                <div style="font-size:2.5rem;margin-bottom:.75rem">🌟</div>
                <h3 style="margin-bottom:.5rem">Serviço Premium</h3>
                <p style="color:#666;font-size:.95rem">Equipa dedicada 24h para garantir a melhor experiência durante a sua estadia.</p>
            </div>
            <div class="text-center" style="padding:1.5rem">
                <div style="font-size:2.5rem;margin-bottom:.75rem">📍</div>
                <h3 style="margin-bottom:.5rem">Localização Central</h3>
                <p style="color:#666;font-size:.95rem">Situado no coração de Lisboa, com fácil acesso a transportes e atrações turísticas.</p>
            </div>
            <div class="text-center" style="padding:1.5rem">
                <div style="font-size:2.5rem;margin-bottom:.75rem">🔒</div>
                <h3 style="margin-bottom:.5rem">Reserva Segura</h3>
                <p style="color:#666;font-size:.95rem">Cancelamento gratuito até 24 horas antes do check-in. Sem surpresas.</p>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
