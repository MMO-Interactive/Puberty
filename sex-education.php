<?php

declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/layout.php';

handleActions();

$user = currentUser();
$flashes = consumeFlash();
$modules = sexEducationModules();

renderPageStart(
    'Sex Education',
    'Sex Education Module',
    'Age-appropriate sex education, clearly explained.',
    'Use these modules to learn consent, relationships, body literacy, safety, and when to get help.',
    $user,
    $flashes,
    'resources',
    [
        ['href' => 'resources.php', 'label' => 'Back To Resources', 'class' => 'button-secondary'],
        ['href' => 'guided-tour.php', 'label' => 'Open Guided Tour', 'class' => 'button-primary'],
    ]
);
?>
<section class="section dashboard-grid">
    <section class="panel">
        <p class="card-label">Core Sex Education Modules</p>
        <div class="resource-grid">
            <?php foreach ($modules as $module): ?>
                <article class="resource-card">
                    <strong><?php echo h($module['title']); ?></strong>
                    <p><?php echo h($module['text']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</section>
<?php renderPageEnd(); ?>
