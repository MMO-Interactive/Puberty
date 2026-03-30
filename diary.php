<?php

declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/layout.php';

handleActions();

$user = requireCompletedOnboarding();

$flashes = consumeFlash();
$diaryEntries = diaryEntries((int) $user['id']);
$supportContacts = supportContacts((int) $user['id']);

renderPageStart(
    'Diary',
    'Private Reflection',
    'Write feelings, questions, and milestones in one place.',
    'This page keeps your private notes together with your trusted support contact list.',
    $user,
    $flashes,
    'diary',
    [
        ['href' => 'guided-tour.php', 'label' => 'Tour Then Write', 'class' => 'button-primary'],
    ]
);
?>
<section class="section diary-section">
    <div class="dashboard-columns">
        <form class="diary-form" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
            <input type="hidden" name="action" value="diary">
            <label>
                Diary title
                <input type="text" name="title" maxlength="50" placeholder="Today I noticed...">
            </label>
            <label>
                Your entry
                <textarea name="entry" rows="8" maxlength="400" placeholder="Write anything you want to remember or ask about." required></textarea>
            </label>
            <button class="button button-secondary" type="submit">Save Diary Entry</button>
        </form>

        <section class="summary-card">
            <p class="card-label">Trusted Support Team</p>
            <form method="post" class="support-form">
                <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                <input type="hidden" name="action" value="support_contact">
                <label>
                    Contact name
                    <input type="text" name="contact_name" maxlength="60" placeholder="Aunt Maya">
                </label>
                <label>
                    Relationship
                    <input type="text" name="relationship" maxlength="40" placeholder="Parent, aunt, school nurse">
                </label>
                <label>
                    How they help
                    <textarea name="contact_note" rows="3" maxlength="160" placeholder="Good person to talk to about questions or cramps."></textarea>
                </label>
                <button class="button button-primary" type="submit">Save Support Contact</button>
            </form>
            <div class="support-list">
                <?php if ($supportContacts): ?>
                    <?php foreach ($supportContacts as $contact): ?>
                        <article class="support-item">
                            <strong><?php echo h($contact['contact_name']); ?></strong>
                            <p><?php echo h($contact['relationship']); ?></p>
                            <span><?php echo h($contact['contact_note'] ?: 'Trusted support person'); ?></span>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-state">No support contacts saved yet.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <section class="panel">
        <p class="card-label">Saved Entries</p>
        <div class="diary-list">
            <?php if ($diaryEntries): ?>
                <?php foreach ($diaryEntries as $entry): ?>
                    <article class="diary-entry">
                        <h4><?php echo h($entry['title'] ?: 'Untitled entry'); ?></h4>
                        <p><?php echo h($entry['entry']); ?></p>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-state">No diary entries yet.</p>
            <?php endif; ?>
        </div>
    </section>
</section>
<?php renderPageEnd(); ?>
