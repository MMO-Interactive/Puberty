<?php

declare(strict_types=1);

require __DIR__ . '/includes/app.php';

handleActions();

$user = currentUser();
$flashes = consumeFlash();
$lessons = lessonCatalog();
$completed = $user ? completedLessons((int) $user['id']) : [];
$onboardingNeeded = $user ? needsOnboarding($user) : false;
$onboardingProgress = $user ? onboardingProgress((int) $user['id']) : ['completed' => 0, 'total' => 0, 'percent' => 0];
$tourSteps = [
    [
        'key' => 'skin',
        'title' => 'Face, Skin, And Hairline',
        'look_for' => 'Stand in front of a mirror and notice your skin, eyebrows, hairline, and any new oiliness or pimples.',
        'see_text' => 'You may see a shinier forehead, a few pimples, or changes in how quickly your hair gets oily. That is a common part of puberty.',
        'tip' => 'Use a gentle face wash and avoid picking at spots. Skin changes happen at different ages.',
        'diagram' => 'face',
    ],
    [
        'key' => 'breasts',
        'title' => 'Chest And Breast Changes',
        'look_for' => 'With a mirror, look at your chest shape and whether one side seems to be growing a little faster than the other.',
        'see_text' => 'You might notice small breast buds, tenderness, or uneven growth. Breasts usually develop in stages, not all at once.',
        'tip' => 'A soft bra or sports bra can feel more comfortable if the area is sore.',
        'diagram' => 'torso',
    ],
    [
        'key' => 'hair',
        'title' => 'Underarms And New Body Hair',
        'look_for' => 'Lift one arm with the mirror nearby and notice whether hair, sweat, or body odor has changed.',
        'see_text' => 'You may start to see underarm hair or sweat more than before. That is a typical puberty change.',
        'tip' => 'Body hair is normal. Keeping it or grooming it is your choice.',
        'diagram' => 'underarm',
    ],
    [
        'key' => 'height',
        'title' => 'Height, Hips, And Overall Shape',
        'look_for' => 'Take a few steps back and notice your height, shoulders, hips, and how your body shape is changing over time.',
        'see_text' => 'Clothes may fit differently, hips can widen, and you may feel like you are growing quickly for a while.',
        'tip' => 'Growth spurts can make you feel awkward for a bit. That is normal.',
        'diagram' => 'body',
    ],
    [
        'key' => 'periods',
        'title' => 'External Vulva Area And Discharge',
        'look_for' => 'Only if you feel comfortable, use a hand mirror in a private space to look at the outside of your genitals, called the vulva.',
        'see_text' => 'The skin folds on the outside are called the labia. They are often not perfectly even, and color can vary. Clear or whitish discharge in underwear can happen before or between periods.',
        'tip' => 'If you notice severe pain, itching, a strong bad smell, or discharge that seems very unusual for you, tell a trusted adult or doctor.',
        'diagram' => 'vulva',
    ],
    [
        'key' => 'feelings',
        'title' => 'Mood Check And Mirror Reflection',
        'look_for' => 'Take one more look and notice what feelings show up. Are you curious, nervous, calm, or proud?',
        'see_text' => 'Puberty changes can feel exciting one day and frustrating the next. Stronger feelings do not mean anything is wrong.',
        'tip' => 'Write a diary note or talk to someone you trust after the tour if you want support.',
        'diagram' => 'heart',
    ],
    [
        'key' => 'sleep',
        'title' => 'Sleep, Rest, And Energy Signals',
        'look_for' => 'Notice signs of tiredness this week: harder mornings, afternoon crashes, or feeling extra hungry after school.',
        'see_text' => 'Growth and hormone changes can affect sleep timing and energy. Feeling more tired during puberty is common.',
        'tip' => 'A simple wind-down routine (screen break, water, quiet activity) can improve sleep.',
        'diagram' => 'heart',
    ],
    [
        'key' => 'nutrition',
        'title' => 'Food, Water, And Growing Body Needs',
        'look_for' => 'Think about your day: meals, snacks, and hydration. Notice when your energy is strongest or lowest.',
        'see_text' => 'Puberty can increase appetite. Regular meals and hydration help with mood, focus, and cycle comfort.',
        'tip' => 'Try carrying a water bottle and one balanced snack to school.',
        'diagram' => 'body',
    ],
    [
        'key' => 'boundaries',
        'title' => 'Body Boundaries And Consent',
        'look_for' => 'Reflect on your comfort zones: who you feel safe with, and what kinds of touch or comments feel okay or not okay.',
        'see_text' => 'Your body belongs to you. You can set boundaries and ask trusted adults for help when something feels wrong.',
        'tip' => 'Practice one boundary sentence: “I am not comfortable with that.”',
        'diagram' => 'heart',
    ],
];

function renderTourDiagram(string $type): string
{
    switch ($type) {
        case 'face':
            return <<<SVG
<svg viewBox="0 0 240 220" aria-hidden="true">
    <circle cx="120" cy="96" r="62" class="diagram-fill"></circle>
    <path d="M84 72c10-18 22-28 36-28s26 10 36 28" class="diagram-line"></path>
    <circle cx="98" cy="94" r="5" class="diagram-dot"></circle>
    <circle cx="142" cy="94" r="5" class="diagram-dot"></circle>
    <path d="M102 126c10 8 26 8 36 0" class="diagram-line"></path>
    <path d="M74 172c14-24 32-36 46-36s32 12 46 36" class="diagram-line"></path>
    <circle cx="82" cy="118" r="4" class="diagram-accent"></circle>
    <circle cx="154" cy="120" r="4" class="diagram-accent"></circle>
</svg>
SVG;
        case 'torso':
            return <<<SVG
<svg viewBox="0 0 240 220" aria-hidden="true">
    <path d="M84 38c12 12 24 18 36 18s24-6 36-18" class="diagram-line"></path>
    <path d="M86 58c-10 18-14 38-14 58 0 42 18 78 48 78s48-36 48-78c0-20-4-40-14-58" class="diagram-line"></path>
    <circle cx="103" cy="110" r="14" class="diagram-fill"></circle>
    <circle cx="137" cy="115" r="18" class="diagram-fill"></circle>
    <path d="M96 172c14 10 34 10 48 0" class="diagram-line"></path>
</svg>
SVG;
        case 'underarm':
            return <<<SVG
<svg viewBox="0 0 240 220" aria-hidden="true">
    <path d="M72 48c-12 26-20 54-20 84 0 14 2 28 6 40" class="diagram-line"></path>
    <path d="M168 48c12 26 20 54 20 84 0 14-2 28-6 40" class="diagram-line"></path>
    <path d="M104 44c-4 24-6 52-6 88 0 26 2 52 6 72" class="diagram-line"></path>
    <path d="M136 44c4 24 6 52 6 88 0 26-2 52-6 72" class="diagram-line"></path>
    <path d="M62 102l18 20m-10-4l14 16m94-32l-18 20m10-4l-14 16" class="diagram-soft"></path>
</svg>
SVG;
        case 'body':
            return <<<SVG
<svg viewBox="0 0 240 240" aria-hidden="true">
    <circle cx="120" cy="42" r="22" class="diagram-fill"></circle>
    <path d="M98 72c-8 22-12 48-12 74 0 38 12 74 34 74s34-36 34-74c0-26-4-52-12-74" class="diagram-line"></path>
    <path d="M90 112c-16 10-28 24-34 42" class="diagram-line"></path>
    <path d="M150 112c16 10 28 24 34 42" class="diagram-line"></path>
    <path d="M106 220c-8 10-16 14-26 16m54-16c8 10 16 14 26 16" class="diagram-line"></path>
</svg>
SVG;
        case 'vulva':
            return <<<SVG
<svg viewBox="0 0 240 220" aria-hidden="true">
    <path d="M120 34c28 18 40 48 40 78s-14 58-40 74c-26-16-40-44-40-74s12-60 40-78z" class="diagram-line"></path>
    <path d="M120 58c16 12 24 30 24 54s-8 44-24 58c-16-14-24-34-24-58s8-42 24-54z" class="diagram-soft"></path>
    <circle cx="120" cy="92" r="6" class="diagram-dot"></circle>
    <ellipse cx="120" cy="124" rx="10" ry="18" class="diagram-fill"></ellipse>
    <path d="M74 76h34m58 0h-34M70 140h40m60 0h-40" class="diagram-line"></path>
</svg>
SVG;
        case 'heart':
        default:
            return <<<SVG
<svg viewBox="0 0 240 220" aria-hidden="true">
    <circle cx="120" cy="74" r="24" class="diagram-fill"></circle>
    <path d="M120 106v62m-28-34h56" class="diagram-line"></path>
    <path d="M78 54c12-14 26-22 42-22s30 8 42 22" class="diagram-soft"></path>
</svg>
SVG;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guided Body Tour</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;700;800&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="page-shell">
        <header class="hero tour-hero">
            <nav class="topbar">
                <div class="brand">
                    <span class="brand-mark">GW</span>
                    <div>
                        <p class="eyebrow">Guided Mirror Tour</p>
                        <h1>Take A Calm Look At Your Changing Body</h1>
                    </div>
                </div>
                <div class="topbar-links">
                    <a href="index.php">Home</a>
                    <?php if ($user): ?>
                        <a href="index.php#dashboard">Dashboard</a>
                    <?php else: ?>
                        <a href="index.php#join">Join</a>
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

            <section class="hero-panel tour-hero-panel">
                <div class="hero-copy">
                    <p class="date-pill">What you need: a mirror, privacy, and a few quiet minutes</p>
                    <h2>Notice. Learn. Breathe.</h2>
                    <p>This tour uses simple diagrams and clear mirror prompts to help you notice normal puberty changes without panic or embarrassment.</p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="#mirror-tour">Start Step 1</a>
                        <a class="button button-secondary" href="<?php echo $user ? ($onboardingNeeded ? 'onboarding.php' : 'diary.php') : 'index.php#join'; ?>">
                            <?php echo $user ? ($onboardingNeeded ? 'Back To Checklist' : 'Write A Note After') : 'Create Account'; ?>
                        </a>
                    </div>
                </div>
                <div class="hero-card">
                    <p class="card-label">Before You Start</p>
                    <ul>
                        <li>Use a private, comfortable space</li>
                        <li>You do not have to do every step today</li>
                        <li>If anything worries you, talk to a trusted adult</li>
                    </ul>
                    <?php if ($user && $onboardingNeeded): ?>
                        <p class="card-label onboarding-mini-label">Checklist Progress</p>
                        <p><?php echo (int) $onboardingProgress['completed']; ?>/<?php echo (int) $onboardingProgress['total']; ?> onboarding steps done</p>
                        <div class="progress-bar">
                            <span style="width: <?php echo (int) $onboardingProgress['percent']; ?>%"></span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </header>

        <main id="mirror-tour">
            <section class="section section-heading">
                <p class="eyebrow">Mirror Tour</p>
                <h2>Step By Step</h2>
                <p>Each section tells you where to look, what changes are common, and what the diagram represents. The diagrams are simple guides, not exact pictures of every body.</p>
            </section>

            <div class="mirror-tour-list">
                <?php foreach ($tourSteps as $index => $step): ?>
                    <section class="mirror-step panel">
                        <div class="mirror-step-copy">
                            <p class="eyebrow">Step <?php echo $index + 1; ?></p>
                            <h3><?php echo h($step['title']); ?></h3>
                            <div class="mirror-note">
                                <strong>Grab a mirror and look for:</strong>
                                <p><?php echo h($step['look_for']); ?></p>
                            </div>
                            <div class="mirror-note">
                                <strong>You may be seeing:</strong>
                                <p><?php echo h($step['see_text']); ?></p>
                            </div>
                            <p class="tour-tip"><?php echo h($step['tip']); ?></p>
                            <?php if ($user): ?>
                                <form method="post" class="lesson-form mirror-complete-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo h(csrfToken()); ?>">
                                    <input type="hidden" name="action" value="lesson">
                                    <input type="hidden" name="lesson_key" value="<?php echo h($step['key']); ?>">
                                    <button type="submit" class="button button-secondary" <?php echo in_array($step['key'], $completed, true) ? 'disabled' : ''; ?>>
                                        <?php echo in_array($step['key'], $completed, true) ? 'Step Completed' : 'Mark Step Complete'; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="mirror-step-visual">
                            <div class="diagram-card">
                                <p class="card-label">Simple Diagram</p>
                                <div class="diagram-frame diagram-<?php echo h($step['diagram']); ?>">
                                    <?php echo renderTourDiagram($step['diagram']); ?>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

            <section class="section panel after-tour-panel">
                <p class="eyebrow">After The Tour</p>
                <h2>What Should You Do Next?</h2>
                <div class="after-tour-grid">
                    <article class="resource-card">
                        <strong>Write down questions</strong>
                        <p>If you spotted something new, save a diary note so you remember what you want to ask about.</p>
                    </article>
                    <article class="resource-card">
                        <strong>Track patterns</strong>
                        <p>If you have discharge, cramps, or a period, use the tracker to notice when things happen.</p>
                    </article>
                    <article class="resource-card">
                        <strong>Ask for help if needed</strong>
                        <p>Talk to a parent, guardian, school nurse, or doctor if you have strong worry, pain, itching, or unusual symptoms.</p>
                    </article>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
