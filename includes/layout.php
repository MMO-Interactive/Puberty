<?php

declare(strict_types=1);

function appNavLinks(?array $user, string $active = ''): array
{
    $links = [
        ['href' => 'index.php', 'label' => 'Home', 'key' => 'home'],
    ];

    if ($user) {
        if (needsOnboarding($user)) {
            $links[] = ['href' => 'onboarding.php', 'label' => 'Welcome', 'key' => 'onboarding'];
        } else {
            $links[] = ['href' => 'guided-tour.php', 'label' => 'Body Tour', 'key' => 'tour'];
            $links[] = ['href' => 'dashboard.php', 'label' => 'Dashboard', 'key' => 'dashboard'];
            $links[] = ['href' => 'tracker.php', 'label' => 'Tracker', 'key' => 'tracker'];
            $links[] = ['href' => 'diary.php', 'label' => 'Diary', 'key' => 'diary'];
            $links[] = ['href' => 'settings.php', 'label' => 'Settings', 'key' => 'settings'];
        }
    } else {
        $links[] = ['href' => 'guided-tour.php', 'label' => 'Body Tour', 'key' => 'tour'];
        $links[] = ['href' => 'index.php#join', 'label' => 'Join', 'key' => 'join'];
        $links[] = ['href' => 'index.php#login', 'label' => 'Log In', 'key' => 'login'];
    }

    foreach ($links as $index => $link) {
        $links[$index]['active'] = $link['key'] === $active;
    }

    return $links;
}

function renderPageStart(string $title, string $eyebrow, string $heroTitle, string $heroText, ?array $user, array $flashes, string $active = '', array $actions = []): void
{
    $navLinks = appNavLinks($user, $active);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;700;800&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="page-shell">
        <header class="hero">
            <nav class="topbar">
                <div class="brand">
                    <span class="brand-mark">GW</span>
                    <div>
                        <p class="eyebrow"><?php echo h($eyebrow); ?></p>
                        <h1><?php echo h($title); ?></h1>
                    </div>
                </div>
                <div class="topbar-links">
                    <?php foreach ($navLinks as $link): ?>
                        <a href="<?php echo h($link['href']); ?>" class="<?php echo $link['active'] ? 'is-current' : ''; ?>"><?php echo h($link['label']); ?></a>
                    <?php endforeach; ?>
                    <?php if ($user): ?>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="nav-button">Log Out</button>
                        </form>
                    <?php endif; ?>
                </div>
            </nav>

            <?php if ($flashes): ?>
                <div class="flash-stack">
                    <?php foreach ($flashes as $flash): ?>
                        <div class="flash flash-<?php echo h($flash['type']); ?>"><?php echo h($flash['message']); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="hero-panel">
                <div class="hero-copy">
                    <p class="date-pill"><?php echo $user ? 'Signed in as ' . h($user['display_name']) : 'Growing With Confidence'; ?></p>
                    <h2><?php echo h($heroTitle); ?></h2>
                    <p><?php echo h($heroText); ?></p>
                    <?php if ($actions): ?>
                        <div class="hero-actions">
                            <?php foreach ($actions as $action): ?>
                                <a class="button <?php echo h($action['class']); ?>" href="<?php echo h($action['href']); ?>"><?php echo h($action['label']); ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="hero-card">
                    <?php if ($user): ?>
                        <p class="card-label">Quick Snapshot</p>
                        <ul>
                            <li>Level <?php echo (int) $user['level']; ?> with <?php echo (int) $user['xp']; ?> XP</li>
                            <li><?php echo (int) $user['streak']; ?> day streak</li>
                        </ul>
                    <?php else: ?>
                        <p class="card-label">Inside This Site</p>
                        <ul>
                            <li>Guided body tours</li>
                            <li>Cycle tracking and diary tools</li>
                            <li>Progress points, quests, and badges</li>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>
        </header>
        <main>
    <?php
}

function renderPageEnd(): void
{
    ?>
        </main>
    </div>
    <script src="assets/js/app.js"></script>
</body>
</html>
    <?php
}
