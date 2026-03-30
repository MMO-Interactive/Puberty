<?php

declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/layout.php';

handleActions();

$viewer = requireCompletedOnboarding();
$profileId = (int) ($_GET['id'] ?? 0);
$profileUser = $profileId > 0 ? fetchUserById($profileId) : null;
if (!$profileUser) {
    flash('error', 'Profile not found.');
    redirect('community.php');
}

$flashes = consumeFlash();
$posts = postsByUser((int) $profileUser['id']);
$isOwner = (int) $viewer['id'] === (int) $profileUser['id'];
$following = !$isOwner ? isFollowing((int) $viewer['id'], (int) $profileUser['id']) : false;
$followers = followerCount((int) $profileUser['id']);
$followingCountValue = followingCount((int) $profileUser['id']);

renderPageStart(
    'Profile',
    'Community Profile',
    $profileUser['display_name'] . '\'s profile',
    'View public community posts and basic profile stats.',
    $viewer,
    $flashes,
    'community',
    [
        ['href' => 'community.php', 'label' => 'Back To Community', 'class' => 'button-secondary'],
    ]
);
?>
<section class="section dashboard-grid">
    <div class="dashboard-columns">
        <section class="panel">
            <p class="card-label">Profile Snapshot</p>
            <h3><?php echo h($profileUser['display_name']); ?></h3>
            <p class="muted-note"><?php echo h($profileUser['bio'] ?: 'No bio added yet.'); ?></p>
            <div class="stats-grid">
                <div class="stat-card"><strong><?php echo (int) $profileUser['level']; ?></strong><span>Level</span></div>
                <div class="stat-card"><strong><?php echo (int) $profileUser['xp']; ?></strong><span>XP</span></div>
                <div class="stat-card"><strong><?php echo (int) $followers; ?></strong><span>Followers</span></div>
                <div class="stat-card"><strong><?php echo (int) $followingCountValue; ?></strong><span>Following</span></div>
            </div>
            <?php if (!$isOwner): ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                    <input type="hidden" name="target_user_id" value="<?php echo (int) $profileUser['id']; ?>">
                    <input type="hidden" name="action" value="<?php echo $following ? 'unfollow_user' : 'follow_user'; ?>">
                    <button class="button button-primary" type="submit"><?php echo $following ? 'Unfollow' : 'Follow'; ?></button>
                </form>
            <?php endif; ?>
        </section>
    </div>

    <section class="panel">
        <p class="card-label">Recent Posts</p>
        <div class="diary-list">
            <?php if ($posts): ?>
                <?php foreach ($posts as $post): ?>
                    <article class="diary-entry">
                        <h4><?php echo h(date('M j, Y', strtotime($post['created_at']))); ?></h4>
                        <p><?php echo h($post['body']); ?></p>
                        <span class="muted-note"><?php echo (int) $post['like_count']; ?> likes · <?php echo (int) commentCountForPost((int) $post['id']); ?> comments</span>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-state">No posts yet.</p>
            <?php endif; ?>
        </div>
    </section>
</section>
<?php renderPageEnd(); ?>
