<?php

declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/layout.php';

handleActions();

$user = currentUser();
$flashes = consumeFlash();
$lessons = lessonCatalog();
$resources = resourceLibrary();
$healthIssues = healthIssuesForGirls();
$safetyGuide = sexualAbuseSafetyGuide();
$firstGynGuide = firstGynExamGuide();

renderPageStart(
    'Resources',
    'Learning Library',
    'Explore all educational guides in one place.',
    'Lessons, health references, safety guidance, and first-visit prep are now organized on this page.',
    $user,
    $flashes,
    'resources',
    [
        ['href' => 'guided-tour.php', 'label' => 'Open Guided Tour', 'class' => 'button-primary'],
        ['href' => 'sex-education.php', 'label' => 'Open Sex Ed Module', 'class' => 'button-secondary'],
    ]
);
?>
<section class="section dashboard-grid">
    <section class="panel">
        <p class="card-label">New</p>
        <h3>Sex Education Module</h3>
        <p class="muted-note">A dedicated module now covers consent, relationships, STI basics, contraception overview, and digital safety.</p>
        <a class="button button-primary" href="sex-education.php">Start Sex Education Module</a>
    </section>

    <section class="panel">
        <p class="card-label">Lesson Library</p>
        <div class="resource-grid">
            <?php foreach ($lessons as $lesson): ?>
                <article class="resource-card">
                    <span class="eyebrow"><?php echo h($lesson['tag']); ?></span>
                    <strong><?php echo h($lesson['title']); ?></strong>
                    <p><?php echo h($lesson['body']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <p class="card-label">Resource Library</p>
        <div class="resource-grid">
            <?php foreach ($resources as $resource): ?>
                <article class="resource-card">
                    <span class="eyebrow"><?php echo h($resource['tag']); ?></span>
                    <strong><?php echo h($resource['title']); ?></strong>
                    <p><?php echo h($resource['text']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <p class="card-label">20 Health Issues Girls May Experience</p>
        <div class="resource-grid">
            <?php foreach ($healthIssues as $issue): ?>
                <article class="resource-card">
                    <strong><?php echo h($issue['title']); ?></strong>
                    <p><?php echo h($issue['summary']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <p class="card-label">Sexual Abuse Safety Guide</p>
        <div class="resource-grid">
            <?php foreach ($safetyGuide as $item): ?>
                <article class="resource-card">
                    <strong><?php echo h($item['title']); ?></strong>
                    <p><?php echo h($item['text']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <p class="card-label">First Gynecology Exam: What To Expect</p>
        <div class="resource-grid">
            <?php foreach ($firstGynGuide as $item): ?>
                <article class="resource-card">
                    <strong><?php echo h($item['title']); ?></strong>
                    <p><?php echo h($item['text']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</section>
<?php renderPageEnd(); ?>
