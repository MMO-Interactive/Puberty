<?php

declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/layout.php';

handleActions();

$user = requireCompletedOnboarding();

$flashes = consumeFlash();
$lessons = lessonCatalog();
$completed = completedLessons((int) $user['id']);
$badges = userBadges((int) $user['id']);
$badgeCatalog = badgeCatalog();
$questProgress = questProgress((int) $user['id']);
$goals = goals((int) $user['id']);
$completionPercent = (int) round((count($completed) / max(count($lessons), 1)) * 100);
$nextLevelXp = ((int) $user['level'] * 40);
$xpToNextLevel = max(0, $nextLevelXp - (int) $user['xp']);
$nextQuest = null;
foreach ($questProgress['items'] as $questItem) {
    if (!$questItem['done']) {
        $nextQuest = $questItem;
        break;
    }
}

renderPageStart(
    'Dashboard',
    'Your Progress Hub',
    'See how your learning habits are growing.',
    'This page pulls together your profile progress, quests, goals, and lesson completion in one place.',
    $user,
    $flashes,
    'dashboard',
    [
        ['href' => 'community.php', 'label' => 'Open Community', 'class' => 'button-primary'],
        ['href' => 'resources.php', 'label' => 'Open Resources', 'class' => 'button-secondary'],
        ['href' => 'guided-tour.php', 'label' => 'Open Guided Tour', 'class' => 'button-primary'],
        ['href' => 'tracker.php', 'label' => 'Open Tracker', 'class' => 'button-secondary'],
    ]
);
?>
<section class="section dashboard-grid">
    <div class="section-heading">
        <p class="eyebrow">Overview</p>
        <h2>Progress And Rewards</h2>
        <p>You earn progress by learning, journaling, checking in, and completing goals.</p>
    </div>

    <div class="dashboard-columns">
        <section class="panel">
            <p class="card-label">Profile Snapshot</p>
            <div class="panel-head">
                <div class="avatar avatar-<?php echo h($user['avatar_color']); ?>">
                    <?php echo h(strtoupper(substr($user['display_name'], 0, 1))); ?>
                </div>
                <div>
                    <h3><?php echo h($user['display_name']); ?></h3>
                    <p class="muted-note"><?php echo h($user['bio'] ?: 'Add a short bio from your settings or profile area.'); ?></p>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <strong><?php echo (int) $user['level']; ?></strong>
                    <span>Level</span>
                </div>
                <div class="stat-card">
                    <strong><?php echo (int) $user['xp']; ?></strong>
                    <span>XP</span>
                </div>
                <div class="stat-card">
                    <strong><?php echo (int) $user['streak']; ?></strong>
                    <span>Day Streak</span>
                </div>
                <div class="stat-card">
                    <strong><?php echo count($badges); ?></strong>
                    <span>Badges</span>
                </div>
            </div>
        </section>

        <section class="panel">
            <p class="card-label">Lesson Progress</p>
            <h3><?php echo count($completed); ?> of <?php echo count($lessons); ?> lessons done</h3>
            <div class="progress-bar">
                <span style="width: <?php echo $completionPercent; ?>%"></span>
            </div>
            <div class="badge-grid">
                <?php foreach ($lessons as $lessonKey => $lesson): ?>
                    <article class="badge-card">
                        <strong><?php echo h($lesson['title']); ?></strong>
                        <p><?php echo in_array($lessonKey, $completed, true) ? 'Completed' : 'Not completed yet'; ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="dashboard-columns">
        <section class="panel">
            <p class="card-label">Daily Game Plan</p>
            <div class="quest-list">
                <article class="quest-item">
                    <strong>XP To Next Level: <?php echo (int) $xpToNextLevel; ?></strong>
                    <p><?php echo (int) $user['xp']; ?> / <?php echo (int) $nextLevelXp; ?> XP toward level <?php echo (int) $user['level'] + 1; ?>.</p>
                </article>
                <article class="quest-item <?php echo $nextQuest ? '' : 'is-done'; ?>">
                    <strong><?php echo $nextQuest ? h($nextQuest['title']) : 'All weekly quests complete'; ?></strong>
                    <p><?php echo $nextQuest ? h($nextQuest['description']) : 'You unlocked everything this week. Keep your streak going!'; ?></p>
                </article>
            </div>
        </section>

        <section class="panel">
            <p class="card-label">Weekly Quests</p>
            <div class="quest-head">
                <h3><?php echo (int) $questProgress['completed']; ?>/<?php echo (int) $questProgress['total']; ?> completed</h3>
                <div class="progress-bar">
                    <span style="width: <?php echo $questProgress['total'] ? (int) round(($questProgress['completed'] / $questProgress['total']) * 100) : 0; ?>%"></span>
                </div>
            </div>
            <div class="quest-list">
                <?php foreach ($questProgress['items'] as $quest): ?>
                    <article class="quest-item <?php echo $quest['done'] ? 'is-done' : ''; ?>">
                        <strong><?php echo h($quest['title']); ?></strong>
                        <p><?php echo h($quest['description']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel">
            <p class="card-label">Confidence Goals</p>
            <form method="post" class="goal-form">
                <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                <input type="hidden" name="action" value="goal">
                <label>
                    Add a goal
                    <input type="text" name="title" maxlength="80" placeholder="Ask one question at my next checkup">
                </label>
                <button class="button button-secondary" type="submit">Add Goal</button>
            </form>
            <div class="goal-list">
                <?php if ($goals): ?>
                    <?php foreach ($goals as $goal): ?>
                        <article class="goal-item <?php echo $goal['status'] === 'completed' ? 'is-done' : ''; ?>">
                            <div>
                                <strong><?php echo h($goal['title']); ?></strong>
                                <p><?php echo $goal['status'] === 'completed' ? 'Completed' : 'Active goal'; ?></p>
                            </div>
                            <?php if ($goal['status'] !== 'completed'): ?>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                                    <input type="hidden" name="action" value="goal_complete">
                                    <input type="hidden" name="goal_id" value="<?php echo (int) $goal['id']; ?>">
                                    <button class="lesson-button" type="submit">Finish</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-state">No goals yet. Add one to start building momentum.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <section class="panel">
        <p class="card-label">Earned Badges</p>
        <div class="badge-grid">
            <?php if ($badges): ?>
                <?php foreach ($badges as $badge): ?>
                    <article class="badge-card">
                        <strong><?php echo h($badgeCatalog[$badge['badge_key']]['title'] ?? $badge['badge_key']); ?></strong>
                        <p><?php echo h($badgeCatalog[$badge['badge_key']]['description'] ?? 'Unlocked'); ?></p>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-state">No badges yet. Start with a lesson or tracker entry.</p>
            <?php endif; ?>
        </div>
    </section>
</section>
<?php renderPageEnd(); ?>
