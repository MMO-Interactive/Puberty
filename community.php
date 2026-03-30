<?php

declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/layout.php';

handleActions();

$user = requireCompletedOnboarding();
$flashes = consumeFlash();
$posts = communityPosts();
$suggested = suggestedUsers((int) $user['id']);
$unreadAlerts = unreadNotificationCount((int) $user['id']);

renderPageStart(
    'Community',
    'Community Feed',
    'Share wins, questions, and encouragement.',
    'This is a social space for short updates while keeping your tracker and diary private.',
    $user,
    $flashes,
    'community',
    [
        ['href' => 'dashboard.php', 'label' => 'My Dashboard', 'class' => 'button-secondary'],
        ['href' => 'resources.php', 'label' => 'Open Resources', 'class' => 'button-primary'],
        ['href' => 'notifications.php', 'label' => 'Alerts (' . (int) $unreadAlerts . ')', 'class' => 'button-secondary'],
    ]
);
?>
<section class="section dashboard-grid">
    <div class="dashboard-columns">
        <section class="panel">
            <p class="card-label">Create A Post</p>
            <form method="post" class="diary-form">
                <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                <input type="hidden" name="action" value="community_post">
                <label>
                    Share something helpful (max 320 chars)
                    <textarea name="body" rows="4" maxlength="320" placeholder="Today I completed two lessons and feel more confident."></textarea>
                </label>
                <button class="button button-primary" type="submit">Post To Feed</button>
            </form>
        </section>

        <section class="panel">
            <p class="card-label">People To Follow</p>
            <div class="support-list">
                <?php if ($suggested): ?>
                    <?php foreach ($suggested as $person): ?>
                        <article class="support-item">
                            <strong><a href="profile.php?id=<?php echo (int) $person['id']; ?>"><?php echo h($person['display_name']); ?></a></strong>
                            <p><?php echo h($person['bio'] ?: 'Growing confidence one step at a time.'); ?></p>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                                <input type="hidden" name="action" value="follow_user">
                                <input type="hidden" name="target_user_id" value="<?php echo (int) $person['id']; ?>">
                                <button class="lesson-button" type="submit">Follow</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-state">No suggestions right now.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <section class="panel">
        <p class="card-label">Recent Community Posts</p>
        <div class="diary-list">
            <?php if ($posts): ?>
                <?php foreach ($posts as $post): ?>
                    <article class="diary-entry">
                        <h4><a href="profile.php?id=<?php echo (int) $post['user_id']; ?>"><?php echo h($post['display_name']); ?></a></h4>
                        <p><?php echo h($post['body']); ?></p>
                        <p class="muted-note"><?php echo (int) commentCountForPost((int) $post['id']); ?> comments</p>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                            <input type="hidden" name="action" value="community_like">
                            <input type="hidden" name="post_id" value="<?php echo (int) $post['id']; ?>">
                            <button class="lesson-button" type="submit">Like (<?php echo (int) $post['like_count']; ?>)</button>
                        </form>
                        <form method="post" class="support-form">
                            <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                            <input type="hidden" name="action" value="community_comment">
                            <input type="hidden" name="post_id" value="<?php echo (int) $post['id']; ?>">
                            <label>
                                Add a comment
                                <textarea name="body" rows="2" maxlength="240" placeholder="Great progress! Thanks for sharing."></textarea>
                            </label>
                            <button class="lesson-button" type="submit">Comment</button>
                        </form>
                        <?php $comments = commentsForPost((int) $post['id']); ?>
                        <?php if ($comments): ?>
                            <div class="support-list">
                                <?php foreach ($comments as $comment): ?>
                                    <article class="support-item">
                                        <strong><?php echo h($comment['display_name']); ?></strong>
                                        <span><?php echo h($comment['body']); ?></span>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-state">No posts yet. Be the first to share encouragement.</p>
            <?php endif; ?>
        </div>
    </section>
</section>
<?php renderPageEnd(); ?>
