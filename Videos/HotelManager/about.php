<?php
$pageTitle = 'Sobre Nós';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
include __DIR__ . '/includes/header.php';
?>

<section class="hero" style="padding:3.5rem 0">
    <div class="container">
        <h1>Sobre o Hotel Vivandro</h1>
        <p>Conheça a nossa história, os nossos valores e o que nos torna únicos.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="grid-2" style="align-items:center;gap:3rem">
            <div>
                <h2 class="section-title">A Nossa História</h2>
                <p style="color:#555;line-height:1.7;margin-bottom:1rem">
                    O Hotel Vivandro nasceu com a missão de oferecer conforto e hospitalidade de excelência
                    a todos os visitantes de Lisboa. Com 20 quartos divididos entre as categorias Duplo,
                    Casal, Familiar e Suite, garantimos uma estadia à medida de cada hóspede.
                </p>
                <p style="color:#555;line-height:1.7">
                    A nossa equipa trabalha incansavelmente para que cada momento da sua estadia seja
                    memorável — desde o momento da reserva até ao check-out.
                </p>
            </div>
            <div style="text-align:center;font-size:8rem">🏨</div>
        </div>
    </div>
</section>

<section class="section" style="background:#fff">
    <div class="container">
        <h2 class="section-title text-center">Os Nossos Valores</h2>
        <div class="grid-3" style="margin-top:1.5rem">
            <div class="card" style="padding:1.5rem;text-align:center">
                <div style="font-size:2.5rem;margin-bottom:.75rem">🤝</div>
                <h3 style="margin-bottom:.5rem">Hospitalidade</h3>
                <p style="color:#666;font-size:.9rem">Recebemos cada hóspede como parte da nossa família, com calor humano e atenção aos detalhes.</p>
            </div>
            <div class="card" style="padding:1.5rem;text-align:center">
                <div style="font-size:2.5rem;margin-bottom:.75rem">✨</div>
                <h3 style="margin-bottom:.5rem">Excelência</h3>
                <p style="color:#666;font-size:.9rem">Padrões elevados de qualidade em todos os serviços prestados, sem exceção.</p>
            </div>
            <div class="card" style="padding:1.5rem;text-align:center">
                <div style="font-size:2.5rem;margin-bottom:.75rem">🌱</div>
                <h3 style="margin-bottom:.5rem">Sustentabilidade</h3>
                <p style="color:#666;font-size:.9rem">Comprometidos com práticas responsáveis e respeito pelo meio ambiente.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title text-center">Política de Cancelamento</h2>
        <div style="max-width:700px;margin:0 auto">
            <div class="policy-box">
                <strong>⚠️ Regras de Edição e Cancelamento</strong>
                As reservas podem ser editadas ou canceladas <strong>gratuitamente até 24 horas antes</strong>
                do início da estadia. Alterações dentro das 24 horas anteriores ao check-in não são permitidas.
                Em caso de cancelamento tardio, poderá ser aplicada uma penalização correspondente a
                <strong>1 noite de estadia</strong>.
            </div>
            <div class="grid-2" style="gap:1rem;margin-top:1.5rem">
                <div class="detail-card">
                    <h3>📅 Check-in / Check-out</h3>
                    <div class="dl">
                        <dt>Check-in</dt><dd>A partir das 14h00</dd>
                        <dt>Check-out</dt><dd>Até às 12h00</dd>
                        <dt>Late Check-out</dt><dd>Mediante disponibilidade</dd>
                    </div>
                </div>
                <div class="detail-card">
                    <h3>🍳 Pequeno-Almoço</h3>
                    <div class="dl">
                        <dt>Horário</dt><dd>07h00 – 10h30</dd>
                        <dt>Custo</dt><dd>10,00 € / hóspede / noite</dd>
                        <dt>Crianças &lt; 3 anos</dt><dd>Gratuito</dd>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background:#0d1b2a;color:#fff;padding:3rem 0">
    <div class="container text-center">
        <h2 style="margin-bottom:1rem;font-size:1.75rem">Pronto para Reservar?</h2>
        <p style="color:#b0c4d8;margin-bottom:1.5rem">20 quartos, 4 categorias — encontre o ideal para si.</p>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary btn-lg">Ver Disponibilidade</a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
