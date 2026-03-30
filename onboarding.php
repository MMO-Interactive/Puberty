<?php

declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/layout.php';

handleActions();

$user = currentUser();
if (!$user) {
    flash('error', 'Create an account or log in to continue.');
    redirect('index.php#join');
}

if (!needsOnboarding($user)) {
    redirect('dashboard.php');
}

$flashes = consumeFlash();
$progress = onboardingProgress((int) $user['id']);

renderPageStart(
    'Welcome',
    'First-Time Setup',
    'Let’s set up your space before you start.',
    'Pick a few basics so your dashboard, tracker, and reminders feel personal from the beginning.',
    $user,
    $flashes,
    'onboarding'
);
?>
<section class="section onboarding-shell">
    <div class="dashboard-columns onboarding-columns">
        <section class="panel">
            <p class="card-label">Step 1</p>
            <h3>Tell us a little about you</h3>
            <p class="muted-note">These details stay in your account and help personalize the experience.</p>
            <form method="post" class="profile-form">
                <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                <input type="hidden" name="action" value="onboarding">
                <label>
                    Display name
                    <input type="text" name="display_name" value="<?php echo h($user['display_name']); ?>" required>
                </label>
                <label>
                    Age range
                    <select name="age_band" required>
                        <option value="">Choose one</option>
                        <option value="8-10">8-10</option>
                        <option value="10-12">10-12</option>
                        <option value="12-14">12-14</option>
                    </select>
                </label>
                <label>
                    Avatar color
                    <select name="avatar_color">
                        <option value="sunrise">Sunrise</option>
                        <option value="mint">Mint</option>
                        <option value="sky">Sky</option>
                    </select>
                </label>
                <label>
                    About me
                    <textarea name="bio" rows="5" maxlength="200" placeholder="What helps you feel calm, strong, or confident?" required></textarea>
                </label>
                <label>
                    First confidence goal
                    <input type="text" name="first_goal" maxlength="80" placeholder="Learn what discharge is">
                </label>
                <button class="button button-primary" type="submit">Finish Setup</button>
            </form>
        </section>

        <section class="panel">
            <p class="card-label">Checklist Progress</p>
            <h3><?php echo (int) $progress['completed']; ?>/<?php echo (int) $progress['total']; ?> steps done</h3>
            <div class="progress-bar">
                <span style="width: <?php echo (int) $progress['percent']; ?>%"></span>
            </div>
            <div class="quest-list">
                <?php foreach ($progress['items'] as $task): ?>
                    <article class="quest-item <?php echo $task['done'] ? 'is-done' : ''; ?>">
                        <strong><?php echo h($task['title']); ?></strong>
                        <p><?php echo h($task['description']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="resource-grid onboarding-grid">
                <article class="resource-card">
                    <strong>Next stop: Guided Tour</strong>
                    <p>After saving your profile, complete one tour lesson to check off another onboarding step.</p>
                    <a class="button button-secondary" href="guided-tour.php">Open Tour</a>
                </article>
                <article class="resource-card">
                    <strong>Then add support</strong>
                    <p>Once the diary opens, save one trusted adult who can help with questions or worries.</p>
                    <a class="button button-secondary" href="index.php#login">Stay On Checklist</a>
                </article>
            </div>
        </section>
    </div>
</section>
<?php renderPageEnd(); ?>
