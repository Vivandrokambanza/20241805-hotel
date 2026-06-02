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

            <div class="detail-card" style="margin-bottom:1rem">
                <h3>📋 Cenários de Cancelamento</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Situação</th>
                                <th>Antecedência</th>
                                <th>Penalização</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Cancelamento livre</td>
                                <td>Mais de 24 h antes do check-in</td>
                                <td><span class="badge badge-green">Sem custo</span></td>
                            </tr>
                            <tr>
                                <td>Cancelamento tardio</td>
                                <td>Menos de 24 h antes do check-in</td>
                                <td>Equivalente a <strong>1 noite</strong> de estadia (cobrada pelo rececionista)</td>
                            </tr>
                            <tr>
                                <td>No-show</td>
                                <td>Não comparência sem aviso</td>
                                <td>Equivalente a <strong>1 noite</strong> de estadia (cobrada pelo rececionista)</td>
                            </tr>
                            <tr>
                                <td>Saída antecipada</td>
                                <td>Check-out antes da data prevista</td>
                                <td>Cobradas as noites reservadas na íntegra</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p style="font-size:.82rem;color:#777;margin-top:.75rem">
                    As penalizações de cancelamento tardio e no-show são registadas manualmente pela receção como pagamento parcial ou total.
                    O cliente pode editar ou cancelar a reserva diretamente na área pessoal enquanto faltarem mais de 24 horas para o check-in.
                </p>
            </div>

            <div class="grid-2" style="gap:1rem;margin-top:1rem">
                <div class="detail-card">
                    <h3>📅 Check-in / Check-out</h3>
                    <div class="dl">
                        <dt>Check-in</dt><dd>A partir das 14h00</dd>
                        <dt>Check-out</dt><dd>Até às 12h00</dd>
                        <dt>Late Check-out</dt><dd>Mediante disponibilidade e acordo prévio</dd>
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

            <div class="policy-box" style="margin-top:1rem">
                <strong>Regra de 24 horas</strong><br>
                A plataforma bloqueia automaticamente qualquer edição ou cancelamento a menos de
                <strong>24 horas</strong> do início da estadia. Para situações de força maior, contacte
                diretamente a receção do hotel.
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
