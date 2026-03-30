<?php

declare(strict_types=1);

require __DIR__ . '/../includes/app.php';

$lessons = lessonCatalog();
$requiredLessonKeys = [
    'height',
    'skin',
    'breasts',
    'hair',
    'periods',
    'feelings',
    'sleep',
    'nutrition',
    'boundaries',
];

foreach ($requiredLessonKeys as $key) {
    if (!isset($lessons[$key])) {
        fwrite(STDERR, "Missing lesson key: {$key}\n");
        exit(1);
    }
}

if (count($lessons) < 9) {
    fwrite(STDERR, "Expected at least 9 lessons, found " . count($lessons) . ".\n");
    exit(1);
}

$badgeCatalog = badgeCatalog();
$bodyGuideDescription = $badgeCatalog['body-guide']['description'] ?? '';
if ($bodyGuideDescription !== 'Completed every body tour lesson') {
    fwrite(STDERR, "Unexpected body-guide badge description.\n");
    exit(1);
}

$requiredBadgeKeys = [
    'streak-three',
    'streak-seven',
    'lesson-sprinter',
    'quest-master',
];

foreach ($requiredBadgeKeys as $badgeKey) {
    if (!isset($badgeCatalog[$badgeKey])) {
        fwrite(STDERR, "Missing badge key: {$badgeKey}\n");
        exit(1);
    }
}

$healthIssues = healthIssuesForGirls();
if (count($healthIssues) !== 20) {
    fwrite(STDERR, "Expected exactly 20 health issues, found " . count($healthIssues) . ".\n");
    exit(1);
}

$safetyGuide = sexualAbuseSafetyGuide();
if (count($safetyGuide) < 8) {
    fwrite(STDERR, "Expected at least 8 safety guide items, found " . count($safetyGuide) . ".\n");
    exit(1);
}

$hotlineSectionFound = false;
foreach ($safetyGuide as $item) {
    if (str_contains(strtolower((string) ($item['title'] ?? '')), 'hotlines')) {
        $hotlineSectionFound = true;
        break;
    }
}
if (!$hotlineSectionFound) {
    fwrite(STDERR, "Missing hotline/reporting section in safety guide.\n");
    exit(1);
}

$firstGynGuide = firstGynExamGuide();
if (count($firstGynGuide) < 9) {
    fwrite(STDERR, "Expected at least 9 first-gyn guide items, found " . count($firstGynGuide) . ".\n");
    exit(1);
}

$privacySectionFound = false;
foreach ($firstGynGuide as $item) {
    if (str_contains(strtolower((string) ($item['title'] ?? '')), 'privacy')) {
        $privacySectionFound = true;
        break;
    }
}
if (!$privacySectionFound) {
    fwrite(STDERR, "Missing privacy/confidentiality section in first-gyn guide.\n");
    exit(1);
}

if (!function_exists('communityPosts') || !function_exists('createCommunityPost') || !function_exists('likeCommunityPost')) {
    fwrite(STDERR, "Missing community social helper functions.\n");
    exit(1);
}

if (!function_exists('followUser') || !function_exists('unfollowUser') || !function_exists('suggestedUsers')) {
    fwrite(STDERR, "Missing social follow helper functions.\n");
    exit(1);
}
if (!function_exists('commentsForPost') || !function_exists('createCommunityComment') || !function_exists('commentCountForPost')) {
    fwrite(STDERR, "Missing community comment helper functions.\n");
    exit(1);
}
if (!function_exists('createNotification') || !function_exists('notificationsForUser') || !function_exists('markNotificationsRead')) {
    fwrite(STDERR, "Missing notifications helper functions.\n");
    exit(1);
}

$sexEd = sexEducationModules();
if (count($sexEd) < 12) {
    fwrite(STDERR, "Expected at least 12 sex education modules, found " . count($sexEd) . ".\n");
    exit(1);
}

fwrite(STDOUT, "Content pass checks passed.\n");
