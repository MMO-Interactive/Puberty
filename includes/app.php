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
    $_SESSION['user_id'] = (int) $user['id'];
}

function logoutUser(): void
{
    unset($_SESSION['user_id']);
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
        'body-guide' => ['title' => 'Body Guide', 'description' => 'Completed all 6 body tour lessons'],
        'streak-three' => ['title' => 'Glow Streak', 'description' => 'Stayed active for 3 days'],
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
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    $statement = db()->prepare('SELECT * FROM users WHERE email = :email');
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        throw new RuntimeException('Email or password was incorrect.');
    }

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

    if ($displayName === '') {
        throw new RuntimeException('Display name is required.');
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

    if ($displayName === '' || $ageBand === '' || $bio === '') {
        throw new RuntimeException('Display name, age range, and about me are required.');
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

    if ($startDate === '' || $mood === '') {
        throw new RuntimeException('Start date and mood are required.');
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
        flash('success', 'Tracker entry saved. Badge earned: Cycle Captain.');
        return;
    }

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
        flash('success', 'Diary saved. Badge earned: Journal Spark.');
        return;
    }

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
    if ($completedCount >= 6) {
        awardBadge((int) $user['id'], 'body-guide');
    }

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
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($password === '') {
        flash('success', 'Settings saved.');
        return;
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
    markOnboardingTaskComplete((int) $user['id'], 'support');
    flash('success', 'Trusted support contact added.');
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
