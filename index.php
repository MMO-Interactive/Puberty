<?php

declare(strict_types=1);

require __DIR__ . '/includes/app.php';

handleActions();

$siteTitle = 'Growing With Confidence';
$today = date('F j, Y');
$user = currentUser();
$onboardingNeeded = $user ? needsOnboarding($user) : false;
$onboardingProgress = $user ? onboardingProgress((int) $user['id']) : ['completed' => 0, 'total' => 0, 'percent' => 0];
$flashes = consumeFlash();
$lessons = lessonCatalog();
$completed = $user ? completedLessons((int) $user['id']) : [];
$trackerEntries = $user ? trackerEntries((int) $user['id']) : [];
$diaryEntries = $user ? diaryEntries((int) $user['id']) : [];
$badges = $user ? userBadges((int) $user['id']) : [];
$badgeCatalog = badgeCatalog();
$nextPeriod = $user ? nextPeriodDate($trackerEntries) : null;
$completionPercent = $user ? (int) round((count($completed) / max(count($lessons), 1)) * 100) : 0;
$goals = $user ? goals((int) $user['id']) : [];
$supportContacts = $user ? supportContacts((int) $user['id']) : [];
$questProgress = $user ? questProgress((int) $user['id']) : ['items' => [], 'completed' => 0, 'total' => 0];
$calendarDays = $user ? cycleCalendar((int) $user['id']) : [];
$resources = resourceLibrary();

if ($user && isset($_GET['export']) && $_GET['export'] === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="growing-with-confidence-data.json"');
    echo exportUserData((int) $user['id']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($siteTitle); ?></title>
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
                        <p class="eyebrow">Body Changes, Explained Kindly</p>
                        <h1><?php echo h($siteTitle); ?></h1>
                    </div>
                </div>
                <div class="topbar-links">
                    <a href="guided-tour.php">Body Tour</a>
                    <?php if ($user): ?>
                        <?php if ($onboardingNeeded): ?>
                            <a href="onboarding.php">Welcome</a>
                        <?php else: ?>
                            <a href="dashboard.php">Dashboard</a>
                            <a href="tracker.php">Tracker</a>
                            <a href="diary.php">Diary</a>
                            <a href="settings.php">Settings</a>
                        <?php endif; ?>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="nav-button">Log Out</button>
                        </form>
                    <?php else: ?>
                        <a href="#join">Join</a>
                        <a href="#login">Log In</a>
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
                    <p class="date-pill">Today: <?php echo h($today); ?></p>
                    <?php if ($user): ?>
                        <h2>Hi <?php echo h($user['display_name']); ?>. Your body learning hub is ready.</h2>
                        <p>Keep building your streak with lessons, tracker check-ins, and diary reflections. Your progress now follows your account.</p>
                    <?php else: ?>
                        <h2>A bright, safe place for learning about puberty.</h2>
                        <p>Explore body changes, understand periods, and keep private notes in a space designed for curious preteens.</p>
                    <?php endif; ?>
                    <div class="hero-actions">
                        <a class="button button-primary" href="guided-tour.php">Start The Tour</a>
                        <?php if ($user): ?>
                            <a class="button button-secondary" href="<?php echo $onboardingNeeded ? 'onboarding.php' : 'dashboard.php'; ?>">
                                <?php echo $onboardingNeeded ? 'Finish Setup' : 'Open Dashboard'; ?>
                            </a>
                        <?php else: ?>
                            <a class="button button-secondary" href="#join">Create Account</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="hero-card">
                    <?php if ($user): ?>
                        <p class="card-label">Your Snapshot</p>
                        <ul>
                            <li>Level <?php echo (int) $user['level']; ?> explorer with <?php echo (int) $user['xp']; ?> XP</li>
                            <li><?php echo count($badges); ?> badges earned and <?php echo count($completed); ?>/<?php echo count($lessons); ?> lessons completed</li>
                            <li><?php echo (int) $user['streak']; ?> day streak<?php echo (int) $user['streak'] === 1 ? '' : 's'; ?></li>
                        </ul>
                        <?php if ($onboardingNeeded): ?>
                            <p class="card-label onboarding-mini-label">Setup Progress</p>
                            <p><?php echo (int) $onboardingProgress['completed']; ?>/<?php echo (int) $onboardingProgress['total']; ?> onboarding steps done</p>
                            <div class="progress-bar">
                                <span style="width: <?php echo (int) $onboardingProgress['percent']; ?>%"></span>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="card-label">Inside This Site</p>
                        <ul>
                            <li>Guided body tour with friendly explanations</li>
                            <li>Account-based tracker, profile, and diary tools</li>
                            <li>Progress points, badges, and learning streaks</li>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>
        </header>

        <main>
            <section class="section feature-grid">
                <?php if ($onboardingNeeded): ?>
                    <article class="feature-card">
                        <span class="feature-icon">00</span>
                        <h3>Finish Setup</h3>
                        <p>Complete your checklist to unlock the dashboard, tracker, diary, and settings pages.</p>
                    </article>
                <?php endif; ?>
                <article class="feature-card">
                    <span class="feature-icon">01</span>
                    <h3>Learn At Your Pace</h3>
                    <p>Every explanation is short, calm, and written for kids who want straight answers without scary language.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon">02</span>
                    <h3>Build A Profile</h3>
                    <p>Create an account, pick a profile style, and keep your own progress, habits, and reflections in one place.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon">03</span>
                    <h3>Earn Rewards</h3>
                    <p>Gain points and badges for learning lessons, logging cycles, and checking in with your feelings.</p>
                </article>
            </section>

            <section class="section tour-section" id="body-tour">
                <div class="section-heading">
                    <p class="eyebrow">Guided Tour</p>
                    <h2>Your Changing Body</h2>
                    <p>Tap each card for a quick preview, then open the full guided mirror tour for step-by-step explanations and diagrams.</p>
                </div>

                <div class="tour-preview-cta">
                    <a class="button button-primary" href="guided-tour.php">Open Full Guided Tour</a>
                    <?php if ($user): ?>
                        <a class="button button-secondary" href="<?php echo $onboardingNeeded ? 'onboarding.php' : 'dashboard.php'; ?>">
                            <?php echo $onboardingNeeded ? 'Finish Setup' : 'Go To Dashboard'; ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="tour-grid">
                    <?php foreach ($lessons as $lessonKey => $lesson): ?>
                        <div class="tour-card-wrap">
                            <button class="tour-card <?php echo $lessonKey === 'height' ? 'is-active' : ''; ?>" data-tour="<?php echo h($lessonKey); ?>">
                                <span><?php echo h($lesson['tag']); ?></span>
                                <strong><?php echo h($lesson['title']); ?></strong>
                            </button>
                            <?php if ($user): ?>
                                <form method="post" class="lesson-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                                    <input type="hidden" name="action" value="lesson">
                                    <input type="hidden" name="lesson_key" value="<?php echo h($lessonKey); ?>">
                                    <button type="submit" class="lesson-button" <?php echo in_array($lessonKey, $completed, true) ? 'disabled' : ''; ?>>
                                        <?php echo in_array($lessonKey, $completed, true) ? 'Completed' : 'Mark Complete'; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="tour-detail">
                    <div class="tour-copy" id="tour-copy">
                        <h3><?php echo h($lessons['height']['title']); ?> can change quickly.</h3>
                        <p><?php echo h($lessons['height']['body']); ?></p>
                        <p class="tour-tip"><?php echo h($lessons['height']['tip']); ?></p>
                    </div>
                    <aside class="tour-side">
                        <?php if ($user): ?>
                            <p class="card-label">Lesson Progress</p>
                            <p><?php echo count($completed); ?> of <?php echo count($lessons); ?> lessons done.</p>
                            <div class="progress-bar">
                                <span style="width: <?php echo $completionPercent; ?>%"></span>
                            </div>
                            <p class="muted-note">Complete lessons to earn XP and unlock badges.</p>
                        <?php else: ?>
                            <p class="card-label">Remember</p>
                            <p>No two people start puberty at exactly the same time. Differences are expected.</p>
                        <?php endif; ?>
                    </aside>
                </div>
            </section>

            <?php if ($user): ?>
                <section class="section dashboard-grid" id="dashboard">
                    <div class="section-heading">
                        <p class="eyebrow">Your Dashboard</p>
                        <h2>Profile, Tracker, Diary, And Rewards</h2>
                        <p>Your account keeps progress, reflections, and cycle notes in one place.</p>
                    </div>

                    <div class="dashboard-columns">
                        <section class="profile-panel panel">
                            <div class="panel-head">
                                <div class="avatar avatar-<?php echo h($user['avatar_color']); ?>">
                                    <?php echo h(strtoupper(substr($user['display_name'], 0, 1))); ?>
                                </div>
                                <div>
                                    <p class="card-label">Profile</p>
                                    <h3><?php echo h($user['display_name']); ?></h3>
                                    <p class="muted-note"><?php echo h($user['age_band'] ?: 'Choose an age range to personalize your account.'); ?></p>
                                </div>
                            </div>
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
                                    <textarea name="bio" rows="4" maxlength="200" placeholder="What helps you feel confident or calm?"><?php echo h($user['bio']); ?></textarea>
                                </label>
                                <button class="button button-secondary" type="submit">Save Profile</button>
                            </form>
                        </section>

                        <section class="progress-panel panel">
                            <p class="card-label">Level Progress</p>
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
                    </div>

                    <div class="dashboard-columns">
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
                </section>

                <section class="section tracker-section" id="tracker">
                    <div class="section-heading">
                        <p class="eyebrow">Cycle Tracker</p>
                        <h2>Check In With Your Body</h2>
                        <p>Log cycle starts, moods, and notes. Your account now keeps that history.</p>
                    </div>

                    <div class="tracker-layout">
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
                            <div class="summary-card">
                                <p class="card-label">30-Day Cycle Map</p>
                                <div class="calendar-grid">
                                    <?php foreach ($calendarDays as $day): ?>
                                        <article class="calendar-day is-<?php echo h($day['type']); ?>">
                                            <strong><?php echo h($day['label']); ?></strong>
                                            <span><?php echo h($day['note']); ?></span>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="section diary-section" id="diary">
                    <div class="section-heading">
                        <p class="eyebrow">Private Diary</p>
                        <h2>Write What You Are Feeling</h2>
                        <p>Save questions, feelings, or milestones to your account diary.</p>
                    </div>

                    <div class="diary-layout">
                        <form class="diary-form" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                            <input type="hidden" name="action" value="diary">
                            <label>
                                Diary title
                                <input type="text" name="title" maxlength="50" placeholder="Today I noticed...">
                            </label>
                            <label>
                                Your entry
                                <textarea name="entry" rows="6" maxlength="400" placeholder="Write anything you want to remember or ask about." required></textarea>
                            </label>
                            <button class="button button-secondary" type="submit">Save Diary Entry</button>
                        </form>

                        <div class="diary-feed">
                            <div class="summary-card">
                                <p class="card-label">Saved Entries</p>
                                <div class="diary-list">
                                    <?php if ($diaryEntries): ?>
                                        <?php foreach ($diaryEntries as $entry): ?>
                                            <article class="diary-entry">
                                                <h4><?php echo h($entry['title'] ?: 'Untitled entry'); ?></h4>
                                                <p><?php echo h($entry['entry']); ?></p>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="empty-state">No diary entries yet.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="summary-card">
                                <p class="card-label">Trusted Support Team</p>
                                <form method="post" class="support-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                                    <input type="hidden" name="action" value="support_contact">
                                    <label>
                                        Contact name
                                        <input type="text" name="contact_name" maxlength="60" placeholder="Aunt Maya">
                                    </label>
                                    <label>
                                        Relationship
                                        <input type="text" name="relationship" maxlength="40" placeholder="Parent, aunt, school nurse">
                                    </label>
                                    <label>
                                        How they help
                                        <textarea name="contact_note" rows="3" maxlength="160" placeholder="Good person to talk to about questions or cramps."></textarea>
                                    </label>
                                    <button class="button button-primary" type="submit">Save Support Contact</button>
                                </form>
                                <div class="support-list">
                                    <?php if ($supportContacts): ?>
                                        <?php foreach ($supportContacts as $contact): ?>
                                            <article class="support-item">
                                                <strong><?php echo h($contact['contact_name']); ?></strong>
                                                <p><?php echo h($contact['relationship']); ?></p>
                                                <span><?php echo h($contact['contact_note'] ?: 'Trusted support person'); ?></span>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="empty-state">No support contacts saved yet.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="section dashboard-grid" id="resources">
                    <div class="section-heading">
                        <p class="eyebrow">Support And Settings</p>
                        <h2>Helpful Guides And Privacy Controls</h2>
                        <p>Keep support information nearby and manage what happens to your account data.</p>
                    </div>

                    <div class="dashboard-columns">
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
                            <p class="card-label">Account Settings</p>
                            <form method="post" class="settings-form">
                                <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                                <input type="hidden" name="action" value="settings">
                                <label>
                                    New password
                                    <input type="password" name="password" minlength="8" placeholder="Leave blank to keep current password">
                                </label>
                                <label>
                                    Confirm new password
                                    <input type="password" name="confirm_password" minlength="8">
                                </label>
                                <button class="button button-secondary" type="submit">Save Settings</button>
                            </form>

                            <div class="privacy-actions">
                                <a class="button button-primary" href="?export=json">Download My Data</a>
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
                </section>
            <?php else: ?>
                <section class="section auth-grid" id="join">
                    <div class="section-heading">
                        <p class="eyebrow">Join The Club</p>
                        <h2>Create Your Account</h2>
                        <p>Set up a private space with a profile, saved tools, and progress rewards.</p>
                    </div>

                    <div class="dashboard-columns">
                        <form method="post" class="panel auth-form">
                            <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                            <input type="hidden" name="action" value="register">
                            <label>
                                Display name
                                <input type="text" name="display_name" required>
                            </label>
                            <label>
                                Email
                                <input type="email" name="email" required>
                            </label>
                            <label>
                                Password
                                <input type="password" name="password" minlength="8" required>
                            </label>
                            <button class="button button-primary" type="submit">Create Account</button>
                        </form>

                        <div class="panel auth-benefits">
                            <p class="card-label">Member Features</p>
                            <ul>
                                <li>Saved tracker and diary history</li>
                                <li>Profile customization and progress level</li>
                                <li>Badges for learning and healthy habits</li>
                                <li>Streaks that reward regular check-ins</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section class="section auth-grid" id="login">
                    <div class="section-heading">
                        <p class="eyebrow">Welcome Back</p>
                        <h2>Log In</h2>
                        <p>Sign in to continue your tracker, diary, and badge progress.</p>
                    </div>

                    <form method="post" class="panel auth-form auth-form-single">
                        <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                        <input type="hidden" name="action" value="login">
                        <label>
                            Email
                            <input type="email" name="email" required>
                        </label>
                        <label>
                            Password
                            <input type="password" name="password" required>
                        </label>
                        <button class="button button-secondary" type="submit">Log In</button>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
