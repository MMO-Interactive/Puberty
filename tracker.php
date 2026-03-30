<?php

declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/layout.php';

handleActions();

$user = requireCompletedOnboarding();

$flashes = consumeFlash();
$trackerEntries = trackerEntries((int) $user['id']);
$nextPeriod = nextPeriodDate($trackerEntries);
$calendarDays = cycleCalendar((int) $user['id']);

renderPageStart(
    'Tracker',
    'Cycle Check-In',
    'Track dates, moods, and patterns over time.',
    'Use this page to save cycle starts and see a simple map of what you have logged and what the app predicts next.',
    $user,
    $flashes,
    'tracker',
    [
        ['href' => 'dashboard.php', 'label' => 'Back To Dashboard', 'class' => 'button-secondary'],
    ]
);
?>
<section class="section tracker-section">
    <div class="dashboard-columns">
        <form class="tracker-form" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
            <input type="hidden" name="action" value="tracker">
            <label>
                Period start date
                <input type="date" name="start_date" required>
            </label>
            <label>
                Average cycle length
                <input type="number" name="cycle_length" min="20" max="45" value="28" required>
            </label>
            <label>
                Mood today
                <select name="mood">
                    <option value="Happy">Happy</option>
                    <option value="Okay">Okay</option>
                    <option value="Tired">Tired</option>
                    <option value="Crampy">Crampy</option>
                    <option value="Emotional">Emotional</option>
                </select>
            </label>
            <label>
                Notes
                <textarea name="notes" rows="4" placeholder="Energy level, cramps, or anything else..."></textarea>
            </label>
            <button class="button button-primary" type="submit">Save Tracker Entry</button>
        </form>

        <div class="tracker-summary">
            <div class="summary-card">
                <p class="card-label">Next Estimated Period</p>
                <strong><?php echo h($nextPeriod ?: 'Not calculated yet'); ?></strong>
            </div>
            <div class="summary-card">
                <p class="card-label">Latest Entries</p>
                <?php if ($trackerEntries): ?>
                    <div class="mini-feed">
                        <?php foreach ($trackerEntries as $entry): ?>
                            <article class="mini-item">
                                <strong><?php echo h(date('M j, Y', strtotime($entry['start_date']))); ?></strong>
                                <span><?php echo h($entry['mood']); ?> | <?php echo (int) $entry['cycle_length']; ?> day cycle</span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No tracker entries yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <section class="panel">
        <p class="card-label">30-Day Cycle Map</p>
        <div class="calendar-grid">
            <?php foreach ($calendarDays as $day): ?>
                <article class="calendar-day is-<?php echo h($day['type']); ?>">
                    <strong><?php echo h($day['label']); ?></strong>
                    <span><?php echo h($day['note']); ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</section>
<?php renderPageEnd(); ?>
