<?php

declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/layout.php';

handleActions();

$user = requireCompletedOnboarding();
$flashes = consumeFlash();
$notifications = notificationsForUser((int) $user['id']);
$unreadCount = unreadNotificationCount((int) $user['id']);

renderPageStart(
    'Notifications',
    'Activity Alerts',
    'See who interacted with you.',
    'Track follows, likes, and comments from the community.',
    $user,
    $flashes,
    'community',
    [
        ['href' => 'community.php', 'label' => 'Back To Community', 'class' => 'button-secondary'],
    ]
);
?>
<section class="section dashboard-grid">
    <section class="panel">
        <p class="card-label">Unread Alerts</p>
        <h3><?php echo (int) $unreadCount; ?> unread</h3>
        <form method="post" class="inline-form">
            <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
            <input type="hidden" name="action" value="notifications_read">
            <button class="button button-primary" type="submit">Mark All As Read</button>
        </form>
    </section>

    <section class="panel">
        <p class="card-label">Recent Notifications</p>
        <div class="diary-list">
            <?php if ($notifications): ?>
                <?php foreach ($notifications as $item): ?>
                    <article class="diary-entry">
                        <h4><?php echo h($item['actor_name']); ?> <?php echo h($item['message']); ?></h4>
                        <p class="muted-note"><?php echo h(date('M j, Y g:i A', strtotime($item['created_at']))); ?><?php echo (int) $item['is_read'] === 0 ? ' • Unread' : ''; ?></p>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-state">No notifications yet.</p>
            <?php endif; ?>
        </div>
    </section>
</section>
<?php renderPageEnd(); ?>
