<?php

declare(strict_types=1);

session_start();

const DB_PATH = __DIR__ . '/../data/app.sqlite';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    initializeDatabase($pdo);

    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            display_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            age_band TEXT DEFAULT "",
            avatar_color TEXT DEFAULT "sunrise",
            bio TEXT DEFAULT "",
            xp INTEGER NOT NULL DEFAULT 0,
            level INTEGER NOT NULL DEFAULT 1,
            streak INTEGER NOT NULL DEFAULT 0,
            last_activity_date TEXT DEFAULT "",
            created_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS tracker_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            start_date TEXT NOT NULL,
            cycle_length INTEGER NOT NULL,
            mood TEXT NOT NULL,
            notes TEXT DEFAULT "",
            created_at TEXT NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS diary_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT DEFAULT "",
            entry TEXT NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS lesson_completions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            lesson_key TEXT NOT NULL,
            completed_at TEXT NOT NULL,
            UNIQUE(user_id, lesson_key),
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS badges (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            badge_key TEXT NOT NULL,
            earned_at TEXT NOT NULL,
            UNIQUE(user_id, badge_key),
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS goals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT "active",
            created_at TEXT NOT NULL,
            completed_at TEXT DEFAULT "",
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS support_contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            contact_name TEXT NOT NULL,
            relationship TEXT NOT NULL,
            contact_note TEXT DEFAULT "",
            created_at TEXT NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS onboarding_tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            task_key TEXT NOT NULL,
            completed_at TEXT NOT NULL,
            UNIQUE(user_id, task_key),
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS community_posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            body TEXT NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS community_likes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            post_id INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            UNIQUE(user_id, post_id),
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(post_id) REFERENCES community_posts(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS user_follows (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            follower_id INTEGER NOT NULL,
            following_id INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            UNIQUE(follower_id, following_id),
            FOREIGN KEY(follower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(following_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS community_comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            post_id INTEGER NOT NULL,
            body TEXT NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(post_id) REFERENCES community_posts(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            actor_user_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL,
            ip_address TEXT NOT NULL,
            failed_count INTEGER NOT NULL DEFAULT 0,
            blocked_until INTEGER NOT NULL DEFAULT 0,
            updated_at TEXT NOT NULL,
            UNIQUE(email, ip_address)
        )'
    );

    $cleanup = $pdo->prepare('DELETE FROM login_attempts WHERE blocked_until > 0 AND blocked_until < :now');
    $cleanup->execute(['now' => time()]);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function today(): string
{
    return date('Y-m-d');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        throw new RuntimeException('Your session check failed. Please try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consumeFlash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

function redirect(string $path = 'index.php'): never
{
    header('Location: ' . $path);
    exit;
}

function redirectForUser(?array $user, string $fallback = 'index.php'): never
{
    if (!$user) {
        redirect($fallback);
    }

    if (needsOnboarding($user)) {
        redirect('onboarding.php');
    }

    redirect('dashboard.php');
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fetchScalar(string $sql, array $params = [], mixed $default = null): mixed
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $value = $statement->fetchColumn();

    return $value === false ? $default : $value;
}

function fetchUserById(int $userId): ?array
{
    $statement = db()->prepare('SELECT * FROM users WHERE id = :id');
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();

    return $user ?: null;
}

function currentUser(): ?array
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        return null;
    }

    return fetchUserById((int) $userId);
}

function needsOnboarding(array $user): bool
{
    $progress = onboardingProgress((int) $user['id']);

    return $progress['completed'] < $progress['total'];
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    unset($_SESSION['login_failures'], $_SESSION['login_blocked_until']);
}

function logoutUser(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    unset($_SESSION['user_id']);
}

function loginGuard(): void
{
    $blockedUntil = (int) ($_SESSION['login_blocked_until'] ?? 0);
    if ($blockedUntil > time()) {
        throw new RuntimeException('Too many login attempts. Please wait a minute and try again.');
    }
}

function registerLoginFailure(): void
{
    $failures = (int) ($_SESSION['login_failures'] ?? 0) + 1;
    $_SESSION['login_failures'] = $failures;

    if ($failures >= 5) {
        $_SESSION['login_blocked_until'] = time() + 60;
        $_SESSION['login_failures'] = 0;
    }
}

function levelForXp(int $xp): int
{
    return max(1, (int) floor($xp / 40) + 1);
}

function syncLevel(int $userId): void
{
    $xp = (int) fetchScalar('SELECT xp FROM users WHERE id = :id', ['id' => $userId], 0);
    $statement = db()->prepare('UPDATE users SET level = :level WHERE id = :id');
    $statement->execute([
        'level' => levelForXp($xp),
        'id' => $userId,
    ]);
}

function updateStreak(int $userId): void
{
    $user = fetchUserById($userId);
    if (!$user) {
        return;
    }

    $lastDate = $user['last_activity_date'] ?: null;
    $todayDate = today();
    if ($lastDate === $todayDate) {
        return;
    }

    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $streak = $lastDate === $yesterday ? (int) $user['streak'] + 1 : 1;

    $statement = db()->prepare(
        'UPDATE users
         SET streak = :streak,
             last_activity_date = :today
         WHERE id = :id'
    );
    $statement->execute([
        'streak' => $streak,
        'today' => $todayDate,
        'id' => $userId,
    ]);
}

function addXp(int $userId, int $amount): void
{
    $statement = db()->prepare('UPDATE users SET xp = xp + :amount WHERE id = :id');
    $statement->execute([
        'amount' => $amount,
        'id' => $userId,
    ]);

    updateStreak($userId);
    syncLevel($userId);
}

function awardBadge(int $userId, string $badgeKey): bool
{
    $statement = db()->prepare(
        'INSERT OR IGNORE INTO badges (user_id, badge_key, earned_at)
         VALUES (:user_id, :badge_key, :earned_at)'
    );
    $statement->execute([
        'user_id' => $userId,
        'badge_key' => $badgeKey,
        'earned_at' => now(),
    ]);

    return $statement->rowCount() > 0;
}

function badgeCatalog(): array
{
    return [
        'first-step' => ['title' => 'First Step', 'description' => 'Created an account'],
        'profile-star' => ['title' => 'Profile Star', 'description' => 'Finished profile basics'],
        'cycle-captain' => ['title' => 'Cycle Captain', 'description' => 'Logged a tracker entry'],
        'journal-spark' => ['title' => 'Journal Spark', 'description' => 'Wrote a diary entry'],
        'curious-mind' => ['title' => 'Curious Mind', 'description' => 'Completed 3 body tour lessons'],
        'body-guide' => ['title' => 'Body Guide', 'description' => 'Completed every body tour lesson'],
        'streak-three' => ['title' => 'Glow Streak', 'description' => 'Stayed active for 3 days'],
        'streak-seven' => ['title' => 'Shining Week', 'description' => 'Stayed active for 7 days'],
        'lesson-sprinter' => ['title' => 'Lesson Sprinter', 'description' => 'Completed 2 lessons in one day'],
        'quest-master' => ['title' => 'Quest Master', 'description' => 'Completed every weekly quest'],
        'goal-getter' => ['title' => 'Goal Getter', 'description' => 'Completed a confidence goal'],
        'support-circle' => ['title' => 'Support Circle', 'description' => 'Added a trusted support contact'],
    ];
}

function userBadges(int $userId): array
{
    $statement = db()->prepare('SELECT badge_key, earned_at FROM badges WHERE user_id = :user_id ORDER BY earned_at DESC');
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchAll();
}

function lessonCatalog(): array
{
    return [
        'height' => ['tag' => 'Growth', 'title' => 'Height & shape', 'body' => 'Bodies can grow fast, hips may widen, and clothes may fit differently for a while.', 'tip' => 'Growth spurts can make you feel awkward for a bit. That is common.'],
        'skin' => ['tag' => 'Skin', 'title' => 'Pimples & sweat', 'body' => 'Oil, sweat, and stronger body odor can increase during puberty.', 'tip' => 'Gentle skin care helps. Pimples do not mean you are dirty.'],
        'breasts' => ['tag' => 'Chest', 'title' => 'Breast development', 'body' => 'Breasts often grow in stages and one side can develop faster than the other.', 'tip' => 'Tenderness can happen. Different timing is normal.'],
        'hair' => ['tag' => 'Hair', 'title' => 'New body hair', 'body' => 'Hair can grow under the arms and around the vulva, and its texture can vary.', 'tip' => 'Keeping it or grooming it is a personal choice.'],
        'periods' => ['tag' => 'Cycle', 'title' => 'Periods & discharge', 'body' => 'Discharge can begin before periods. Early cycles can be irregular.', 'tip' => 'Tracking dates helps you prepare and notice patterns.'],
        'feelings' => ['tag' => 'Mind', 'title' => 'Moods & emotions', 'body' => 'Hormone changes can affect feelings, energy, and reactions.', 'tip' => 'Talking to a trusted adult can make changes easier to handle.'],
        'sleep' => ['tag' => 'Rest', 'title' => 'Sleep & energy', 'body' => 'Your sleep schedule can shift during puberty, and you may feel extra tired after growth spurts.', 'tip' => 'Aim for a bedtime routine and enough sleep to support mood and growth.'],
        'nutrition' => ['tag' => 'Fuel', 'title' => 'Food & hydration', 'body' => 'Growing bodies need regular meals, water, and iron-rich foods, especially after periods begin.', 'tip' => 'Balanced snacks and water can help with focus, energy, and cramps.'],
        'boundaries' => ['tag' => 'Safety', 'title' => 'Boundaries & consent', 'body' => 'As your body changes, personal boundaries matter even more at home, school, and online.', 'tip' => 'You can say no, ask for space, and tell a trusted adult if something feels wrong.'],
    ];
}

function completedLessons(int $userId): array
{
    $statement = db()->prepare('SELECT lesson_key FROM lesson_completions WHERE user_id = :user_id');
    $statement->execute(['user_id' => $userId]);

    return array_column($statement->fetchAll(), 'lesson_key');
}

function completeLesson(int $userId, string $lessonKey): bool
{
    $statement = db()->prepare(
        'INSERT OR IGNORE INTO lesson_completions (user_id, lesson_key, completed_at)
         VALUES (:user_id, :lesson_key, :completed_at)'
    );
    $statement->execute([
        'user_id' => $userId,
        'lesson_key' => $lessonKey,
        'completed_at' => now(),
    ]);

    return $statement->rowCount() > 0;
}

function trackerEntries(int $userId): array
{
    $statement = db()->prepare('SELECT * FROM tracker_entries WHERE user_id = :user_id ORDER BY start_date DESC, id DESC LIMIT 6');
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchAll();
}

function diaryEntries(int $userId): array
{
    $statement = db()->prepare('SELECT * FROM diary_entries WHERE user_id = :user_id ORDER BY id DESC LIMIT 6');
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchAll();
}

function goals(int $userId): array
{
    $statement = db()->prepare('SELECT * FROM goals WHERE user_id = :user_id ORDER BY status ASC, id DESC LIMIT 8');
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchAll();
}

function supportContacts(int $userId): array
{
    $statement = db()->prepare('SELECT * FROM support_contacts WHERE user_id = :user_id ORDER BY id DESC LIMIT 5');
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchAll();
}

function communityPosts(int $limit = 20): array
{
    $statement = db()->prepare(
        'SELECT p.id,
                p.user_id,
                p.body,
                p.created_at,
                u.display_name,
                COUNT(l.id) AS like_count
         FROM community_posts p
         INNER JOIN users u ON u.id = p.user_id
         LEFT JOIN community_likes l ON l.post_id = p.id
         GROUP BY p.id
         ORDER BY p.id DESC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function createCommunityPost(int $userId, string $body): void
{
    $statement = db()->prepare(
        'INSERT INTO community_posts (user_id, body, created_at)
         VALUES (:user_id, :body, :created_at)'
    );
    $statement->execute([
        'user_id' => $userId,
        'body' => $body,
        'created_at' => now(),
    ]);
}

function likeCommunityPost(int $userId, int $postId): bool
{
    $statement = db()->prepare(
        'INSERT OR IGNORE INTO community_likes (user_id, post_id, created_at)
         VALUES (:user_id, :post_id, :created_at)'
    );
    $statement->execute([
        'user_id' => $userId,
        'post_id' => $postId,
        'created_at' => now(),
    ]);

    return $statement->rowCount() > 0;
}

function followUser(int $followerId, int $followingId): bool
{
    if ($followerId === $followingId) {
        return false;
    }

    $statement = db()->prepare(
        'INSERT OR IGNORE INTO user_follows (follower_id, following_id, created_at)
         VALUES (:follower_id, :following_id, :created_at)'
    );
    $statement->execute([
        'follower_id' => $followerId,
        'following_id' => $followingId,
        'created_at' => now(),
    ]);

    return $statement->rowCount() > 0;
}

function unfollowUser(int $followerId, int $followingId): bool
{
    $statement = db()->prepare(
        'DELETE FROM user_follows WHERE follower_id = :follower_id AND following_id = :following_id'
    );
    $statement->execute([
        'follower_id' => $followerId,
        'following_id' => $followingId,
    ]);

    return $statement->rowCount() > 0;
}

function isFollowing(int $followerId, int $followingId): bool
{
    return (int) fetchScalar(
        'SELECT COUNT(*) FROM user_follows WHERE follower_id = :follower_id AND following_id = :following_id',
        ['follower_id' => $followerId, 'following_id' => $followingId],
        0
    ) > 0;
}

function followerCount(int $userId): int
{
    return (int) fetchScalar(
        'SELECT COUNT(*) FROM user_follows WHERE following_id = :user_id',
        ['user_id' => $userId],
        0
    );
}

function followingCount(int $userId): int
{
    return (int) fetchScalar(
        'SELECT COUNT(*) FROM user_follows WHERE follower_id = :user_id',
        ['user_id' => $userId],
        0
    );
}

function suggestedUsers(int $userId, int $limit = 8): array
{
    $statement = db()->prepare(
        'SELECT u.id, u.display_name, u.bio
         FROM users u
         WHERE u.id != :user_id
           AND u.id NOT IN (
               SELECT following_id FROM user_follows WHERE follower_id = :user_id
           )
         ORDER BY u.id DESC
         LIMIT :limit'
    );
    $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function postsByUser(int $userId, int $limit = 20): array
{
    $statement = db()->prepare(
        'SELECT p.id,
                p.user_id,
                p.body,
                p.created_at,
                u.display_name,
                COUNT(l.id) AS like_count
         FROM community_posts p
         INNER JOIN users u ON u.id = p.user_id
         LEFT JOIN community_likes l ON l.post_id = p.id
         WHERE p.user_id = :user_id
         GROUP BY p.id
         ORDER BY p.id DESC
         LIMIT :limit'
    );
    $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function createNotification(int $userId, int $actorUserId, string $type, string $message): void
{
    if ($userId === $actorUserId) {
        return;
    }

    $statement = db()->prepare(
        'INSERT INTO notifications (user_id, actor_user_id, type, message, created_at)
         VALUES (:user_id, :actor_user_id, :type, :message, :created_at)'
    );
    $statement->execute([
        'user_id' => $userId,
        'actor_user_id' => $actorUserId,
        'type' => $type,
        'message' => $message,
        'created_at' => now(),
    ]);
}

function notificationsForUser(int $userId, int $limit = 40): array
{
    $statement = db()->prepare(
        'SELECT n.id, n.user_id, n.actor_user_id, n.type, n.message, n.is_read, n.created_at, u.display_name AS actor_name
         FROM notifications n
         INNER JOIN users u ON u.id = n.actor_user_id
         WHERE n.user_id = :user_id
         ORDER BY n.id DESC
         LIMIT :limit'
    );
    $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function unreadNotificationCount(int $userId): int
{
    return (int) fetchScalar(
        'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0',
        ['user_id' => $userId],
        0
    );
}

function markNotificationsRead(int $userId): void
{
    $statement = db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0');
    $statement->execute(['user_id' => $userId]);
}

function commentsForPost(int $postId, int $limit = 6): array
{
    $statement = db()->prepare(
        'SELECT c.id, c.user_id, c.post_id, c.body, c.created_at, u.display_name
         FROM community_comments c
         INNER JOIN users u ON u.id = c.user_id
         WHERE c.post_id = :post_id
         ORDER BY c.id DESC
         LIMIT :limit'
    );
    $statement->bindValue(':post_id', $postId, PDO::PARAM_INT);
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function commentCountForPost(int $postId): int
{
    return (int) fetchScalar(
        'SELECT COUNT(*) FROM community_comments WHERE post_id = :post_id',
        ['post_id' => $postId],
        0
    );
}

function createCommunityComment(int $userId, int $postId, string $body): void
{
    $statement = db()->prepare(
        'INSERT INTO community_comments (user_id, post_id, body, created_at)
         VALUES (:user_id, :post_id, :body, :created_at)'
    );
    $statement->execute([
        'user_id' => $userId,
        'post_id' => $postId,
        'body' => $body,
        'created_at' => now(),
    ]);
}

function questCatalog(): array
{
    return [
        [
            'title' => 'Complete 1 lesson',
            'description' => 'Explore one body topic and mark it complete.',
            'done' => static fn (int $userId): bool => count(completedLessons($userId)) >= 1,
        ],
        [
            'title' => 'Log 2 cycle check-ins',
            'description' => 'Use the tracker twice to build a pattern.',
            'done' => static fn (int $userId): bool => (int) fetchScalar('SELECT COUNT(*) FROM tracker_entries WHERE user_id = :user_id', ['user_id' => $userId], 0) >= 2,
        ],
        [
            'title' => 'Write 2 diary entries',
            'description' => 'Reflect on how you are feeling in your private diary.',
            'done' => static fn (int $userId): bool => (int) fetchScalar('SELECT COUNT(*) FROM diary_entries WHERE user_id = :user_id', ['user_id' => $userId], 0) >= 2,
        ],
        [
            'title' => 'Add a support contact',
            'description' => 'Save one trusted adult you can talk to.',
            'done' => static fn (int $userId): bool => (int) fetchScalar('SELECT COUNT(*) FROM support_contacts WHERE user_id = :user_id', ['user_id' => $userId], 0) >= 1,
        ],
    ];
}

function questProgress(int $userId): array
{
    $quests = questCatalog();
    $completed = 0;

    foreach ($quests as $index => $quest) {
        $done = $quest['done']($userId);
        $quests[$index]['done'] = $done;
        if ($done) {
            $completed++;
        }
    }

    return [
        'items' => $quests,
        'completed' => $completed,
        'total' => count($quests),
    ];
}

function cycleCalendar(int $userId): array
{
    $statement = db()->prepare('SELECT start_date, cycle_length, mood FROM tracker_entries WHERE user_id = :user_id ORDER BY start_date DESC LIMIT 3');
    $statement->execute(['user_id' => $userId]);
    $entries = $statement->fetchAll();

    $days = [];
    for ($offset = 0; $offset < 30; $offset++) {
        $date = date('Y-m-d', strtotime('+' . $offset . ' days'));
        $label = date('M j', strtotime($date));
        $type = 'normal';
        $note = 'No cycle event predicted.';

        foreach ($entries as $entry) {
            $start = $entry['start_date'];
            $cycleLength = (int) $entry['cycle_length'];
            $nextStart = date('Y-m-d', strtotime($start . ' +' . $cycleLength . ' days'));
            if ($date === $start) {
                $type = 'logged';
                $note = 'Logged period start (' . $entry['mood'] . ').';
                break;
            }
            if ($date === $nextStart) {
                $type = 'predicted';
                $note = 'Predicted next cycle based on your latest logs.';
                break;
            }
        }

        $days[] = [
            'date' => $date,
            'label' => $label,
            'type' => $type,
            'note' => $note,
        ];
    }

    return $days;
}

function maybeAwardStreakBadge(int $userId): void
{
    $streak = (int) fetchScalar('SELECT streak FROM users WHERE id = :id', ['id' => $userId], 0);
    if ($streak >= 3) {
        awardBadge($userId, 'streak-three');
    }
    if ($streak >= 7) {
        awardBadge($userId, 'streak-seven');
    }
}

function maybeAwardLessonSprinter(int $userId): void
{
    $todayLessons = (int) fetchScalar(
        'SELECT COUNT(*) FROM lesson_completions WHERE user_id = :user_id AND date(completed_at) = date("now")',
        ['user_id' => $userId],
        0
    );

    if ($todayLessons >= 2) {
        awardBadge($userId, 'lesson-sprinter');
    }
}

function maybeAwardQuestMaster(int $userId): void
{
    $progress = questProgress($userId);
    if ($progress['total'] > 0 && $progress['completed'] >= $progress['total']) {
        awardBadge($userId, 'quest-master');
    }
}

function createUser(string $displayName, string $email, string $password): int
{
    $statement = db()->prepare('INSERT INTO users (display_name, email, password_hash, created_at) VALUES (:display_name, :email, :password_hash, :created_at)');
    $statement->execute([
        'display_name' => $displayName,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => now(),
    ]);

    return (int) db()->lastInsertId();
}

function requireAuth(): void
{
    if (!currentUser()) {
        throw new RuntimeException('Please log in to continue.');
    }
}

function requireGuestOnly(): void
{
    $user = currentUser();
    if ($user) {
        redirectForUser($user);
    }
}

function requireCompletedOnboarding(): array
{
    $user = currentUser();
    if (!$user) {
        flash('error', 'Please log in to continue.');
        redirect('index.php#login');
    }

    if (needsOnboarding($user)) {
        flash('error', 'Finish onboarding before using the rest of the app.');
        redirect('onboarding.php');
    }

    return $user;
}

function onboardingTaskCatalog(): array
{
    return [
        'profile' => [
            'title' => 'Complete your profile',
            'description' => 'Add your age range, avatar color, and a short bio.',
        ],
        'goal' => [
            'title' => 'Choose your first goal',
            'description' => 'Set one small confidence or learning goal.',
        ],
        'tour' => [
            'title' => 'Finish one guided lesson',
            'description' => 'Mark one body-tour step complete after exploring it.',
        ],
        'support' => [
            'title' => 'Add one support contact',
            'description' => 'Save one trusted adult you could talk to.',
        ],
    ];
}

function completedOnboardingTasks(int $userId): array
{
    $statement = db()->prepare('SELECT task_key FROM onboarding_tasks WHERE user_id = :user_id');
    $statement->execute(['user_id' => $userId]);

    return array_column($statement->fetchAll(), 'task_key');
}

function markOnboardingTaskComplete(int $userId, string $taskKey): void
{
    $statement = db()->prepare('INSERT OR IGNORE INTO onboarding_tasks (user_id, task_key, completed_at) VALUES (:user_id, :task_key, :completed_at)');
    $statement->execute([
        'user_id' => $userId,
        'task_key' => $taskKey,
        'completed_at' => now(),
    ]);
}

function onboardingProgress(int $userId): array
{
    $catalog = onboardingTaskCatalog();
    $completed = completedOnboardingTasks($userId);
    $items = [];
    $doneCount = 0;

    foreach ($catalog as $key => $task) {
        $isDone = in_array($key, $completed, true);
        $items[] = [
            'key' => $key,
            'title' => $task['title'],
            'description' => $task['description'],
            'done' => $isDone,
        ];
        if ($isDone) {
            $doneCount++;
        }
    }

    return [
        'items' => $items,
        'completed' => $doneCount,
        'total' => count($catalog),
        'percent' => count($catalog) ? (int) round(($doneCount / count($catalog)) * 100) : 0,
    ];
}

function handleRegister(): void
{
    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($displayName === '' || $email === '' || $password === '') {
        throw new RuntimeException('Display name, email, and password are required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Please enter a valid email address.');
    }

    if (strlen($password) < 8) {
        throw new RuntimeException('Password must be at least 8 characters.');
    }

    $userId = createUser($displayName, $email, $password);
    $user = fetchUserById($userId);
    if (!$user) {
        throw new RuntimeException('Account creation failed.');
    }

    loginUser($user);
    addXp($userId, 15);
    awardBadge($userId, 'first-step');
    flash('success', 'Account created. Your first badge has been unlocked.');
    redirect('onboarding.php');
}

function handleLogin(): void
{
    loginGuard();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $ipAddress = clientIpAddress();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Email or password was incorrect.');
    }

    loginGuard($email, $ipAddress);

    $statement = db()->prepare('SELECT * FROM users WHERE email = :email');
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        registerLoginFailure();
        throw new RuntimeException('Email or password was incorrect.');
    }

    clearLoginFailures($email, $ipAddress);
    loginUser($user);
    updateStreak((int) $user['id']);
    flash('success', 'Welcome back.');
    redirectForUser(fetchUserById((int) $user['id']));
}

function handleLogout(): void
{
    logoutUser();
    flash('success', 'You have been logged out.');
}

function handleProfile(): void
{
    $user = currentUser();
    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $ageBand = trim((string) ($_POST['age_band'] ?? ''));
    $avatarColor = trim((string) ($_POST['avatar_color'] ?? 'sunrise'));
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $validAgeBands = ['', '8-10', '10-12', '12-14'];
    $validAvatarColors = ['sunrise', 'mint', 'sky'];

    if ($displayName === '') {
        throw new RuntimeException('Display name is required.');
    }
    if (!in_array($ageBand, $validAgeBands, true)) {
        throw new RuntimeException('Please choose a valid age range.');
    }
    if (!in_array($avatarColor, $validAvatarColors, true)) {
        throw new RuntimeException('Please choose a valid avatar color.');
    }

    $statement = db()->prepare('UPDATE users SET display_name = :display_name, age_band = :age_band, avatar_color = :avatar_color, bio = :bio WHERE id = :id');
    $statement->execute([
        'display_name' => $displayName,
        'age_band' => $ageBand,
        'avatar_color' => $avatarColor,
        'bio' => $bio,
        'id' => $user['id'],
    ]);

    if ($bio !== '' && $ageBand !== '' && awardBadge((int) $user['id'], 'profile-star')) {
        addXp((int) $user['id'], 10);
        markOnboardingTaskComplete((int) $user['id'], 'profile');
        flash('success', 'Profile updated. Badge earned: Profile Star.');
        return;
    }

    if ($bio !== '' && $ageBand !== '') {
        markOnboardingTaskComplete((int) $user['id'], 'profile');
    }

    flash('success', 'Profile updated.');
}

function handleOnboarding(): void
{
    $user = currentUser();
    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $ageBand = trim((string) ($_POST['age_band'] ?? ''));
    $avatarColor = trim((string) ($_POST['avatar_color'] ?? 'sunrise'));
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $firstGoal = trim((string) ($_POST['first_goal'] ?? ''));
    $validAgeBands = ['8-10', '10-12', '12-14'];
    $validAvatarColors = ['sunrise', 'mint', 'sky'];

    if ($displayName === '' || $ageBand === '' || $bio === '') {
        throw new RuntimeException('Display name, age range, and about me are required.');
    }
    if (!in_array($ageBand, $validAgeBands, true)) {
        throw new RuntimeException('Please choose a valid age range.');
    }
    if (!in_array($avatarColor, $validAvatarColors, true)) {
        throw new RuntimeException('Please choose a valid avatar color.');
    }

    $statement = db()->prepare('UPDATE users SET display_name = :display_name, age_band = :age_band, avatar_color = :avatar_color, bio = :bio WHERE id = :id');
    $statement->execute([
        'display_name' => $displayName,
        'age_band' => $ageBand,
        'avatar_color' => $avatarColor,
        'bio' => $bio,
        'id' => $user['id'],
    ]);

    if ($firstGoal !== '') {
        $goalStatement = db()->prepare('INSERT INTO goals (user_id, title, created_at) VALUES (:user_id, :title, :created_at)');
        $goalStatement->execute([
            'user_id' => $user['id'],
            'title' => $firstGoal,
            'created_at' => now(),
        ]);
        markOnboardingTaskComplete((int) $user['id'], 'goal');
    }

    markOnboardingTaskComplete((int) $user['id'], 'profile');

    if (awardBadge((int) $user['id'], 'profile-star')) {
        addXp((int) $user['id'], 10);
    }

    $freshUser = fetchUserById((int) $user['id']);
    if ($freshUser && !needsOnboarding($freshUser)) {
        flash('success', 'Onboarding complete. Your dashboard is ready.');
        redirect('dashboard.php');
    }

    flash('success', 'Great start. Finish the checklist to unlock the full app.');
    redirect('onboarding.php');
}

function handleTracker(): void
{
    $user = currentUser();
    $startDate = trim((string) ($_POST['start_date'] ?? ''));
    $cycleLength = (int) ($_POST['cycle_length'] ?? 28);
    $mood = trim((string) ($_POST['mood'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $validMoods = ['Happy', 'Okay', 'Tired', 'Crampy', 'Emotional'];

    if ($startDate === '' || $mood === '') {
        throw new RuntimeException('Start date and mood are required.');
    }
    $parsedDate = DateTime::createFromFormat('Y-m-d', $startDate);
    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $startDate) {
        throw new RuntimeException('Please enter a valid start date.');
    }
    if ($cycleLength < 20 || $cycleLength > 45) {
        throw new RuntimeException('Cycle length should be between 20 and 45 days.');
    }

    $statement = db()->prepare('INSERT INTO tracker_entries (user_id, start_date, cycle_length, mood, notes, created_at) VALUES (:user_id, :start_date, :cycle_length, :mood, :notes, :created_at)');
    $statement->execute([
        'user_id' => $user['id'],
        'start_date' => $startDate,
        'cycle_length' => $cycleLength,
        'mood' => $mood,
        'notes' => $notes,
        'created_at' => now(),
    ]);

    addXp((int) $user['id'], 8);
    maybeAwardStreakBadge((int) $user['id']);

    if (awardBadge((int) $user['id'], 'cycle-captain')) {
        maybeAwardQuestMaster((int) $user['id']);
        flash('success', 'Tracker entry saved. Badge earned: Cycle Captain.');
        return;
    }

    maybeAwardQuestMaster((int) $user['id']);
    flash('success', 'Tracker entry saved.');
}

function handleDiary(): void
{
    $user = currentUser();
    $title = trim((string) ($_POST['title'] ?? ''));
    $entry = trim((string) ($_POST['entry'] ?? ''));

    if ($entry === '') {
        throw new RuntimeException('Diary entry cannot be empty.');
    }

    $statement = db()->prepare('INSERT INTO diary_entries (user_id, title, entry, created_at) VALUES (:user_id, :title, :entry, :created_at)');
    $statement->execute([
        'user_id' => $user['id'],
        'title' => $title,
        'entry' => $entry,
        'created_at' => now(),
    ]);

    addXp((int) $user['id'], 6);
    maybeAwardStreakBadge((int) $user['id']);

    if (awardBadge((int) $user['id'], 'journal-spark')) {
        maybeAwardQuestMaster((int) $user['id']);
        flash('success', 'Diary saved. Badge earned: Journal Spark.');
        return;
    }

    maybeAwardQuestMaster((int) $user['id']);
    flash('success', 'Diary saved.');
}

function handleLesson(): void
{
    $user = currentUser();
    $lessonKey = trim((string) ($_POST['lesson_key'] ?? ''));
    $lessons = lessonCatalog();

    if (!isset($lessons[$lessonKey])) {
        throw new RuntimeException('Lesson not found.');
    }

    if (!completeLesson((int) $user['id'], $lessonKey)) {
        flash('success', 'Lesson already completed.');
        return;
    }

    markOnboardingTaskComplete((int) $user['id'], 'tour');
    addXp((int) $user['id'], 5);
    maybeAwardStreakBadge((int) $user['id']);

    $completedCount = (int) fetchScalar('SELECT COUNT(*) FROM lesson_completions WHERE user_id = :user_id', ['user_id' => $user['id']], 0);
    if ($completedCount >= 3) {
        awardBadge((int) $user['id'], 'curious-mind');
    }
    if ($completedCount >= count($lessons)) {
        awardBadge((int) $user['id'], 'body-guide');
    }
    maybeAwardLessonSprinter((int) $user['id']);
    maybeAwardQuestMaster((int) $user['id']);

    flash('success', 'Lesson completed. Progress updated.');
}

function nextPeriodDate(array $entries): ?string
{
    if (!$entries) {
        return null;
    }

    $latest = $entries[0];
    $timestamp = strtotime($latest['start_date'] . ' +' . (int) $latest['cycle_length'] . ' days');

    return $timestamp ? date('F j, Y', $timestamp) : null;
}

function handleActions(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    try {
        verifyCsrf();

        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'register':
                handleRegister();
                break;
            case 'login':
                handleLogin();
                break;
            case 'logout':
                handleLogout();
                break;
            case 'profile':
                requireAuth();
                handleProfile();
                break;
            case 'onboarding':
                requireAuth();
                handleOnboarding();
                break;
            case 'tracker':
                requireAuth();
                handleTracker();
                break;
            case 'diary':
                requireAuth();
                handleDiary();
                break;
            case 'lesson':
                requireAuth();
                handleLesson();
                break;
            case 'goal':
                requireAuth();
                handleGoal();
                break;
            case 'goal_complete':
                requireAuth();
                handleGoalComplete();
                break;
            case 'support_contact':
                requireAuth();
                handleSupportContact();
                break;
            case 'community_post':
                requireAuth();
                handleCommunityPost();
                break;
            case 'community_like':
                requireAuth();
                handleCommunityLike();
                break;
            case 'community_comment':
                requireAuth();
                handleCommunityComment();
                break;
            case 'follow_user':
                requireAuth();
                handleFollowUser();
                break;
            case 'unfollow_user':
                requireAuth();
                handleUnfollowUser();
                break;
            case 'notifications_read':
                requireAuth();
                handleNotificationsRead();
                break;
            case 'settings':
                requireAuth();
                handleSettings();
                break;
            case 'delete_account':
                requireAuth();
                handleDeleteAccount();
                break;
            default:
                flash('error', 'That action was not recognized.');
        }
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect();
}

function handleSettings(): void
{
    $user = currentUser();
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($password === '') {
        flash('success', 'Settings saved.');
        return;
    }
    if ($currentPassword === '' || !password_verify($currentPassword, (string) $user['password_hash'])) {
        throw new RuntimeException('Current password is incorrect.');
    }

    if (strlen($password) < 8) {
        throw new RuntimeException('New password must be at least 8 characters.');
    }

    if ($password !== $confirmPassword) {
        throw new RuntimeException('Password confirmation did not match.');
    }

    $statement = db()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
    $statement->execute([
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'id' => $user['id'],
    ]);

    flash('success', 'Password updated.');
}

function handleDeleteAccount(): void
{
    $user = currentUser();
    $confirm = trim((string) ($_POST['confirm_text'] ?? ''));

    if ($confirm !== 'DELETE') {
        throw new RuntimeException('Type DELETE to confirm account removal.');
    }

    $statement = db()->prepare('DELETE FROM users WHERE id = :id');
    $statement->execute(['id' => $user['id']]);
    logoutUser();
    flash('success', 'Your account and saved data were deleted.');
}

function handleGoal(): void
{
    $user = currentUser();
    $title = trim((string) ($_POST['title'] ?? ''));

    if ($title === '') {
        throw new RuntimeException('Goal title is required.');
    }

    $statement = db()->prepare('INSERT INTO goals (user_id, title, created_at) VALUES (:user_id, :title, :created_at)');
    $statement->execute([
        'user_id' => $user['id'],
        'title' => $title,
        'created_at' => now(),
    ]);

    addXp((int) $user['id'], 4);
    maybeAwardStreakBadge((int) $user['id']);
    flash('success', 'Goal added to your dashboard.');
}

function handleGoalComplete(): void
{
    $user = currentUser();
    $goalId = (int) ($_POST['goal_id'] ?? 0);

    $statement = db()->prepare('UPDATE goals SET status = "completed", completed_at = :completed_at WHERE id = :id AND user_id = :user_id AND status != "completed"');
    $statement->execute([
        'completed_at' => now(),
        'id' => $goalId,
        'user_id' => $user['id'],
    ]);

    if ($statement->rowCount() === 0) {
        throw new RuntimeException('Goal could not be completed.');
    }

    addXp((int) $user['id'], 12);
    maybeAwardStreakBadge((int) $user['id']);
    awardBadge((int) $user['id'], 'goal-getter');
    flash('success', 'Goal completed. XP added to your profile.');
}

function handleSupportContact(): void
{
    $user = currentUser();
    $contactName = trim((string) ($_POST['contact_name'] ?? ''));
    $relationship = trim((string) ($_POST['relationship'] ?? ''));
    $contactNote = trim((string) ($_POST['contact_note'] ?? ''));

    if ($contactName === '' || $relationship === '') {
        throw new RuntimeException('Contact name and relationship are required.');
    }

    $statement = db()->prepare('INSERT INTO support_contacts (user_id, contact_name, relationship, contact_note, created_at) VALUES (:user_id, :contact_name, :relationship, :contact_note, :created_at)');
    $statement->execute([
        'user_id' => $user['id'],
        'contact_name' => $contactName,
        'relationship' => $relationship,
        'contact_note' => $contactNote,
        'created_at' => now(),
    ]);

    addXp((int) $user['id'], 5);
    maybeAwardStreakBadge((int) $user['id']);
    awardBadge((int) $user['id'], 'support-circle');
    maybeAwardQuestMaster((int) $user['id']);
    markOnboardingTaskComplete((int) $user['id'], 'support');
    flash('success', 'Trusted support contact added.');
}

function handleCommunityPost(): void
{
    $user = currentUser();
    $body = trim((string) ($_POST['body'] ?? ''));

    if ($body === '') {
        throw new RuntimeException('Post text cannot be empty.');
    }
    if (mb_strlen($body) > 320) {
        throw new RuntimeException('Posts must be 320 characters or fewer.');
    }

    createCommunityPost((int) $user['id'], $body);
    addXp((int) $user['id'], 2);
    maybeAwardStreakBadge((int) $user['id']);
    flash('success', 'Post shared with the community feed.');
}

function handleCommunityLike(): void
{
    $user = currentUser();
    $postId = (int) ($_POST['post_id'] ?? 0);
    if ($postId <= 0) {
        throw new RuntimeException('Post not found.');
    }

    if (!likeCommunityPost((int) $user['id'], $postId)) {
        flash('success', 'You already liked this post.');
        return;
    }

    $postOwnerId = (int) fetchScalar(
        'SELECT user_id FROM community_posts WHERE id = :post_id',
        ['post_id' => $postId],
        0
    );
    if ($postOwnerId > 0) {
        createNotification($postOwnerId, (int) $user['id'], 'like', 'liked your post.');
    }

    addXp((int) $user['id'], 1);
    maybeAwardStreakBadge((int) $user['id']);
    flash('success', 'Post liked.');
}

function handleFollowUser(): void
{
    $user = currentUser();
    $targetUserId = (int) ($_POST['target_user_id'] ?? 0);
    if ($targetUserId <= 0) {
        throw new RuntimeException('User not found.');
    }

    if (!followUser((int) $user['id'], $targetUserId)) {
        flash('success', 'Already following this user.');
        return;
    }

    createNotification($targetUserId, (int) $user['id'], 'follow', 'started following you.');
    addXp((int) $user['id'], 1);
    flash('success', 'Now following this user.');
}

function handleUnfollowUser(): void
{
    $user = currentUser();
    $targetUserId = (int) ($_POST['target_user_id'] ?? 0);
    if ($targetUserId <= 0) {
        throw new RuntimeException('User not found.');
    }

    if (!unfollowUser((int) $user['id'], $targetUserId)) {
        flash('success', 'You were not following this user.');
        return;
    }

    flash('success', 'Unfollowed user.');
}

function handleCommunityComment(): void
{
    $user = currentUser();
    $postId = (int) ($_POST['post_id'] ?? 0);
    $body = trim((string) ($_POST['body'] ?? ''));

    if ($postId <= 0) {
        throw new RuntimeException('Post not found.');
    }
    if ($body === '') {
        throw new RuntimeException('Comment cannot be empty.');
    }
    if (mb_strlen($body) > 240) {
        throw new RuntimeException('Comments must be 240 characters or fewer.');
    }

    createCommunityComment((int) $user['id'], $postId, $body);
    $postOwnerId = (int) fetchScalar(
        'SELECT user_id FROM community_posts WHERE id = :post_id',
        ['post_id' => $postId],
        0
    );
    if ($postOwnerId > 0) {
        createNotification($postOwnerId, (int) $user['id'], 'comment', 'commented on your post.');
    }

    addXp((int) $user['id'], 1);
    maybeAwardStreakBadge((int) $user['id']);
    flash('success', 'Comment posted.');
}

function handleNotificationsRead(): void
{
    $user = currentUser();
    markNotificationsRead((int) $user['id']);
    flash('success', 'Notifications marked as read.');
}

function exportUserData(int $userId): string
{
    $data = [
        'user' => fetchUserById($userId),
        'tracker_entries' => trackerEntriesExport($userId),
        'diary_entries' => diaryEntriesExport($userId),
        'goals' => goals($userId),
        'support_contacts' => supportContacts($userId),
        'badges' => userBadges($userId),
        'completed_lessons' => completedLessons($userId),
        'exported_at' => now(),
    ];

    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
}

function trackerEntriesExport(int $userId): array
{
    $statement = db()->prepare('SELECT * FROM tracker_entries WHERE user_id = :user_id ORDER BY id DESC');
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchAll();
}

function diaryEntriesExport(int $userId): array
{
    $statement = db()->prepare('SELECT * FROM diary_entries WHERE user_id = :user_id ORDER BY id DESC');
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchAll();
}

function resourceLibrary(): array
{
    return [
        [
            'title' => 'What to pack for your first period kit',
            'tag' => 'Prep',
            'text' => 'A small pouch with pads, clean underwear, wipes, and a note for yourself can lower stress.',
        ],
        [
            'title' => 'Questions you can ask a trusted adult',
            'tag' => 'Support',
            'text' => 'Try asking what changes they noticed at your age, what helped with cramps, or when to see a doctor.',
        ],
        [
            'title' => 'Body changes that vary a lot',
            'tag' => 'Learning',
            'text' => 'Height, breast growth, acne, and period timing all vary. Different timelines are normal.',
        ],
        [
            'title' => 'When to ask for medical advice',
            'tag' => 'Health',
            'text' => 'Severe pain, very heavy bleeding, dizziness, or strong worry are good reasons to talk with a doctor.',
        ],
    ];
}

function healthIssuesForGirls(): array
{
    return [
        ['title' => 'Acne', 'summary' => 'Pimples from hormone-related oil changes, often on face, chest, or back.'],
        ['title' => 'Painful periods (dysmenorrhea)', 'summary' => 'Cramping that can affect school, sleep, or sports during periods.'],
        ['title' => 'Heavy bleeding (menorrhagia)', 'summary' => 'Periods with very heavy flow, large clots, or soaking products quickly.'],
        ['title' => 'Irregular periods', 'summary' => 'Cycles that are hard to predict, especially in early puberty or with stress changes.'],
        ['title' => 'Premenstrual syndrome (PMS)', 'summary' => 'Mood and body symptoms before periods, like bloating or irritability.'],
        ['title' => 'Premenstrual dysphoric disorder (PMDD)', 'summary' => 'Severe mood symptoms before periods that disrupt daily life.'],
        ['title' => 'Anemia', 'summary' => 'Low iron, sometimes linked to heavy periods; may cause fatigue or dizziness.'],
        ['title' => 'Polycystic ovary syndrome (PCOS)', 'summary' => 'Can include irregular cycles, acne, hair changes, and insulin-related symptoms.'],
        ['title' => 'Thyroid disorders', 'summary' => 'Thyroid hormone imbalance can affect energy, mood, and period regularity.'],
        ['title' => 'Urinary tract infection (UTI)', 'summary' => 'Burning with urination, frequent urges, or lower belly discomfort.'],
        ['title' => 'Yeast infection', 'summary' => 'Itching, irritation, and thick discharge caused by yeast overgrowth.'],
        ['title' => 'Bacterial vaginosis', 'summary' => 'Vaginal imbalance that can cause unusual odor or discharge changes.'],
        ['title' => 'Endometriosis', 'summary' => 'Tissue similar to uterine lining grows outside uterus, causing pain symptoms.'],
        ['title' => 'Ovarian cysts', 'summary' => 'Fluid-filled sacs on ovaries that may cause pelvic pain or bloating.'],
        ['title' => 'Fibroids', 'summary' => 'Non-cancerous uterine growths that may increase bleeding or pressure.'],
        ['title' => 'Migraine headaches', 'summary' => 'Severe headaches that can be linked to cycle hormone fluctuations.'],
        ['title' => 'Anxiety disorders', 'summary' => 'Persistent worry, panic, or physical stress symptoms affecting routines.'],
        ['title' => 'Depression', 'summary' => 'Low mood, low energy, or loss of interest lasting more than two weeks.'],
        ['title' => 'Eating disorders', 'summary' => 'Unhealthy eating patterns and body-image distress needing early support.'],
        ['title' => 'Sleep disorders', 'summary' => 'Trouble falling/staying asleep that impacts mood, focus, and recovery.'],
    ];
}

function sexualAbuseSafetyGuide(): array
{
    return [
        [
            'title' => 'What sexual abuse means',
            'text' => 'Sexual abuse is any sexual touch, request, message, photo, or activity that is forced, pressured, secret, or not age-appropriate. It is never the child’s fault.',
        ],
        [
            'title' => 'Common warning signs',
            'text' => 'Warning signs can include fear of a specific person, sudden behavior changes, sleep problems, school changes, unexplained injuries, or sexual language beyond age level.',
        ],
        [
            'title' => 'Body boundaries you can use',
            'text' => 'Your body belongs to you. You can say: “No.”, “Stop.”, “I am not comfortable.”, and leave to find a trusted adult.',
        ],
        [
            'title' => 'Online safety counts too',
            'text' => 'Abuse can happen online through grooming, pressure for photos, secret chats, or threats. Do not share private images and tell a trusted adult immediately if someone pressures you.',
        ],
        [
            'title' => 'What to do right away',
            'text' => 'If you feel unsafe: get to a safer place, call emergency services if needed, and tell a trusted adult (parent, guardian, school counselor, nurse, teacher, or coach) as soon as possible.',
        ],
        [
            'title' => 'Hotlines and reporting (U.S.)',
            'text' => 'National Sexual Assault Hotline (RAINN): 800-656-HOPE (4673). Childhelp National Child Abuse Hotline: 800-4-A-CHILD (800-422-4453). If immediate danger, call 911.',
        ],
        [
            'title' => 'If someone tells you they were abused',
            'text' => 'Believe them, stay calm, thank them for telling you, and report to appropriate authorities or child protection services according to local law and school policy.',
        ],
        [
            'title' => 'Healing and support',
            'text' => 'Support can include trauma-informed counseling, medical care, and trusted adults who help with safety planning. Recovery takes time and help is available.',
        ],
    ];
}

function firstGynExamGuide(): array
{
    return [
        [
            'title' => 'When a first gynecology visit may happen',
            'text' => 'Many girls have a first gynecology-focused visit in the teen years, or earlier if they have severe cramps, period concerns, pain, or other symptoms.',
        ],
        [
            'title' => 'You can bring support',
            'text' => 'You can bring a parent, guardian, or trusted adult. You can also ask to speak privately with the clinician for part of the visit.',
        ],
        [
            'title' => 'What the appointment usually includes',
            'text' => 'Most first visits focus on talking: period history, pain, discharge, mood, sexual health questions, and general body changes.',
        ],
        [
            'title' => 'Physical exam is often simple',
            'text' => 'Many first visits do not require an internal exam. The clinician may check height, weight, blood pressure, and possibly a brief external exam only if needed.',
        ],
        [
            'title' => 'You can ask for explanations',
            'text' => 'Before any exam step, you can ask what will happen, why it is needed, and if there are options. Consent and comfort matter.',
        ],
        [
            'title' => 'Questions to ask',
            'text' => 'Examples: “Are my cycles normal?”, “What helps cramps?”, “When should I worry about heavy bleeding?”, and “How do I manage discharge safely?”',
        ],
        [
            'title' => 'What to bring',
            'text' => 'Bring period dates if tracked, symptom notes, medicine list, allergies, and any questions written down so you do not forget them.',
        ],
        [
            'title' => 'Privacy and confidentiality',
            'text' => 'Rules vary by state and clinic, but many places offer confidential time for teens to discuss sensitive topics safely.',
        ],
        [
            'title' => 'After the visit',
            'text' => 'You may receive a care plan (symptom tracking, medication guidance, labs, or follow-up). Ask when to return and what warning signs need urgent care.',
        ],
    ];
}

function sexEducationModules(): array
{
    return [
        ['title' => 'Puberty and body changes', 'text' => 'Puberty includes changes in hormones, growth, skin, body hair, periods, erections, and emotions. Timing differs for everyone.'],
        ['title' => 'Reproductive anatomy basics', 'text' => 'Learn the names and functions of body parts (vulva, vagina, uterus, ovaries, penis, testes) using respectful, correct language.'],
        ['title' => 'Menstrual cycle basics', 'text' => 'Cycles can be irregular at first. Tracking dates, symptoms, and flow can help you prepare and notice patterns.'],
        ['title' => 'Consent and boundaries', 'text' => 'Consent means clear, ongoing agreement. You can say no at any time, and pressure or threats are never consent.'],
        ['title' => 'Healthy relationships', 'text' => 'Healthy relationships include respect, honesty, safety, and communication. Control, fear, and isolation are warning signs.'],
        ['title' => 'Digital safety and sexting pressure', 'text' => 'Private images can spread quickly. If pressured to send photos, say no and tell a trusted adult immediately.'],
        ['title' => 'STIs and prevention', 'text' => 'STIs are infections that can spread through sexual contact. Testing, condoms, and medical care reduce risk and complications.'],
        ['title' => 'Pregnancy basics', 'text' => 'Pregnancy can happen when sperm meets egg. Learning about fertility and contraception helps informed decision-making later.'],
        ['title' => 'Contraception overview', 'text' => 'Contraception methods include condoms, pills, implants, IUDs, and others. A clinician can explain benefits and side effects.'],
        ['title' => 'Sexual orientation and identity', 'text' => 'People may discover orientation and identity over time. Respect, privacy, and kindness are essential for everyone.'],
        ['title' => 'Myths vs facts', 'text' => 'Social media can spread misinformation. Use trusted sources (clinicians, health educators, official health orgs).'],
        ['title' => 'When to ask for help', 'text' => 'Get help for severe pain, heavy bleeding, coercion, assault, STI concerns, pregnancy concerns, or strong emotional distress.'],
    ];
}
