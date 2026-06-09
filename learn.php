<?php
// Shared auth/database bootstrap.
require_once 'auth.php';

// Learning mode is only for logged-in users.
if (!checkAuth()) { header("Location: index.php"); exit; }

// Deck currently being studied.
$deck_id = $_GET['deck_id'] ?? 0;

// Reset the session counter when the user starts a new learning session.
if (isset($_GET['reset'])) {
    $_SESSION['session_count'] = 0;
}

// Handle the rating submission after the answer is shown.
if (isset($_POST['submit_rating'])) {
    // Update card difficulty and increment the review count.
    $stmt = $pdo->prepare("UPDATE cards SET difficulty = ?, times_reviewed = times_reviewed + 1 WHERE id = ?");
    $stmt->execute([$_POST['rating'], $_POST['card_id']]);

    // Track how many cards have been reviewed in this session.
    $_SESSION['session_count']++;

    // Return to the same page to load the next card.
    header("Location: learn.php?deck_id=$deck_id");
    exit;
}

// Stop after 10 reviews in a session.
if (($_SESSION['session_count'] ?? 0) >= 10) {
    die("Session complete! You have reviewed 10 cards. <a href='index.php'>Return Home</a>");
}

// Pick the next card.
// The intent is to show less-reviewed cards first, with harder cards ranked earlier within the same review count.
$stmt = $pdo->prepare("SELECT * FROM cards WHERE deck_id = ? ORDER BY times_reviewed ASC, difficulty DESC LIMIT 1");
$stmt->execute([$deck_id]);
$card = $stmt->fetch();

if (!$card) {
    die("No cards found. <a href='index.php'>Go back</a>");
}

// Controls whether the answer is displayed or only the question.
$show_answer = isset($_POST['show_answer']);
?>
<!DOCTYPE html>
<html>
<body>
    <a href="index.php">Stop Learning</a>

    <!-- Session progress counter is purely per-session, not stored in the DB -->
    <p>Session Progress: <?php echo ($_SESSION['session_count'] ?? 0); ?> / 10</p>
    <hr>

    <h3>Question:</h3>
    <p><?php echo htmlspecialchars($card['front']); ?></p>

    <?php if (!$show_answer): ?>
        <form method="POST">
            <button type="submit" name="show_answer">Show Answer</button>
        </form>
    <?php else: ?>
        <hr>
        <h3>Answer:</h3>
        <p><?php echo htmlspecialchars($card['back']); ?></p>

        <form method="POST">
            <!-- Hidden field identifies which card is being rated -->
            <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
            <p>Difficulty (1 Easy - 5 Hard):</p>
            1 <input type="range" name="rating" min="1" max="5" value="3"> 5
            <br><br>
            <button type="submit" name="submit_rating">Next Card</button>
        </form>
    <?php endif; ?>
</body>
</html>