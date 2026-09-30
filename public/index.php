<?php
require_once __DIR__ . '/../includes/public_layout.php';

$clinic = clinic_info();
$services = db()->query('SELECT name, description FROM services ORDER BY name COLLATE NOCASE')->fetchAll();
$hasContact = !empty($clinic['phone']) || !empty($clinic['email']) || !empty($clinic['address']);

public_header('', excerpt($clinic['description'] ?? '', 155), 'home');
?>
        <section class="hero">
            <div class="wrap">
                <h1><?= e(clinic_name()) ?></h1>
<?php if (!empty($clinic['description'])): ?>
                <div class="hero-text"><?= text_paragraphs($clinic['description']) ?></div>
<?php elseif (!$clinic): ?>
                <p class="hero-text"><?= e(t('home.coming_soon')) ?></p>
<?php endif; ?>
                <div class="hero-actions">
                    <a class="button" href="staff.php"><?= e(t('home.meet_team')) ?></a>
<?php if (!empty($clinic['phone'])): ?>
                    <a class="button button-secondary" href="<?= e(phone_href($clinic['phone'])) ?>"><?= e(t('home.call', ['phone' => $clinic['phone']])) ?></a>
<?php endif; ?>
                </div>
            </div>
        </section>

        <section class="section" aria-labelledby="services-heading">
            <div class="wrap">
                <h2 id="services-heading"><?= e(t('home.services')) ?></h2>
<?php if (!$services): ?>
                <p class="muted"><?= e(t('home.services_empty')) ?></p>
<?php else: ?>
                <ul class="service-grid">
<?php foreach ($services as $service): ?>
                    <li class="service">
                        <h3><?= e($service['name']) ?></h3>
                        <?= text_paragraphs($service['description']) ?>

                    </li>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
            </div>
        </section>

<?php if ($hasContact): ?>
        <section class="section section-alt" aria-labelledby="contact-heading">
            <div class="wrap">
                <h2 id="contact-heading"><?= e(t('home.contact')) ?></h2>
                <dl class="contact-list">
<?php if (!empty($clinic['phone'])): ?>
                    <div>
                        <dt><?= e(t('home.phone')) ?></dt>
                        <dd><a href="<?= e(phone_href($clinic['phone'])) ?>"><?= e($clinic['phone']) ?></a></dd>
                    </div>
<?php endif; ?>
<?php if (!empty($clinic['email'])): ?>
                    <div>
                        <dt><?= e(t('home.email')) ?></dt>
                        <dd><a href="mailto:<?= e($clinic['email']) ?>"><?= e($clinic['email']) ?></a></dd>
                    </div>
<?php endif; ?>
<?php if (!empty($clinic['address'])): ?>
                    <div>
                        <dt><?= e(t('home.address')) ?></dt>
                        <dd>
                            <address><?= nl2br(e($clinic['address']), false) ?></address>
                            <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode(preg_replace('/\s+/', ' ', $clinic['address']))) ?>" target="_blank" rel="noopener">
                                <?= e(t('home.directions')) ?><span class="visually-hidden"> <?= e(t('site.new_tab')) ?></span>
                            </a>
                        </dd>
                    </div>
<?php endif; ?>
                </dl>
            </div>
        </section>
<?php endif; ?>
<?php
public_footer();
