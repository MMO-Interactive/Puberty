<?php

declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/layout.php';

handleActions();

$user = requireCompletedOnboarding();

$flashes = consumeFlash();
$resources = resourceLibrary();
$healthIssues = healthIssuesForGirls();
$safetyGuide = sexualAbuseSafetyGuide();
$firstGynGuide = firstGynExamGuide();

if (isset($_GET['export']) && $_GET['export'] === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="growing-with-confidence-data.json"');
    echo exportUserData((int) $user['id']);
    exit;
}

renderPageStart(
    'Settings',
    'Privacy And Support',
    'Manage account details and keep useful resources close.',
    'Use this page to update your password, download your data, remove your account, or reread help resources.',
    $user,
    $flashes,
    'settings'
);
?>
<section class="section dashboard-grid">
    <div class="dashboard-columns">
        <section class="panel">
            <p class="card-label">Profile Settings</p>
            <form method="post" class="profile-form">
                <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                <input type="hidden" name="action" value="profile">
                <label>
                    Display name
                    <input type="text" name="display_name" value="<?php echo h($user['display_name']); ?>" required>
                </label>
                <label>
                    Age range
                    <select name="age_band">
                        <option value="">Choose one</option>
                        <option value="8-10" <?php echo $user['age_band'] === '8-10' ? 'selected' : ''; ?>>8-10</option>
                        <option value="10-12" <?php echo $user['age_band'] === '10-12' ? 'selected' : ''; ?>>10-12</option>
                        <option value="12-14" <?php echo $user['age_band'] === '12-14' ? 'selected' : ''; ?>>12-14</option>
                    </select>
                </label>
                <label>
                    Avatar color
                    <select name="avatar_color">
                        <option value="sunrise" <?php echo $user['avatar_color'] === 'sunrise' ? 'selected' : ''; ?>>Sunrise</option>
                        <option value="mint" <?php echo $user['avatar_color'] === 'mint' ? 'selected' : ''; ?>>Mint</option>
                        <option value="sky" <?php echo $user['avatar_color'] === 'sky' ? 'selected' : ''; ?>>Sky</option>
                    </select>
                </label>
                <label>
                    About me
                    <textarea name="bio" rows="4" maxlength="200"><?php echo h($user['bio']); ?></textarea>
                </label>
                <button class="button button-secondary" type="submit">Save Profile</button>
            </form>
        </section>

        <section class="panel">
            <p class="card-label">Account Settings</p>
            <form method="post" class="settings-form">
                <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                <input type="hidden" name="action" value="settings">
                <label>
                    Current password
                    <input type="password" name="current_password" minlength="8" placeholder="Required to change password">
                </label>
                <label>
                    New password
                    <input type="password" name="password" minlength="8" placeholder="Leave blank to keep current password">
                </label>
                <label>
                    Confirm new password
                    <input type="password" name="confirm_password" minlength="8">
                </label>
                <button class="button button-primary" type="submit">Save Password</button>
            </form>

            <div class="privacy-actions">
                <a class="button button-secondary" href="settings.php?export=json">Download My Data</a>
                <form method="post" class="danger-form">
                    <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                    <input type="hidden" name="action" value="delete_account">
                    <label>
                        Type DELETE to remove account
                        <input type="text" name="confirm_text" placeholder="DELETE">
                    </label>
                    <button class="nav-button danger-button" type="submit">Delete Account</button>
                </form>
            </div>
        </section>
    </div>

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
        <p class="muted-note">This education list is not a diagnosis tool. Ask a healthcare professional if symptoms worry you.</p>
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
        <p class="muted-note">Immediate danger: call 911. Sexual abuse is never the child’s fault.</p>
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
        <p class="muted-note">This guide helps girls prepare questions and understand common exam steps before a first visit.</p>
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
