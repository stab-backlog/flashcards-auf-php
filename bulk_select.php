<?php
// Reuse authentication and database setup.
// auth.php starts the session and makes $pdo available.
require_once 'auth.php';

// The deck being edited is passed in the query string.
$deck_id = $_GET['deck_id'];

// Check whether the current user owns this deck.
// This prevents users from bulk-deleting cards from decks they do not own.
$stmt = $pdo->prepare("SELECT * FROM decks WHERE id = ? AND user_id = ?");
$stmt->execute([$deck_id, $_SESSION['user_id']]);

// If no matching deck is found, stop immediately.
if (!$stmt->fetch()) die("Denied");

// Handle the bulk delete POST action.
if (isset($_POST['do_bulk_delete']) && !empty($_POST['selected_cards'])) {
    // selected_cards[] is an array of card IDs from checkbox inputs.
    $ids = $_POST['selected_cards'];

    // Build a placeholder list like "?, ?, ?" dynamically based on how many cards were selected.
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Delete only the selected cards, and only within this deck.
    $stmt = $pdo->prepare("DELETE FROM cards WHERE id IN ($placeholders) AND deck_id = ?");
    $stmt->execute(array_merge($ids, [$deck_id]));

    // Return to the deck view after deletion.
    header("Location: deck_view.php?id=$deck_id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<body>
    <!-- Simple cancel link back to the deck -->
    <a href="deck_view.php?id=<?php echo $deck_id; ?>">Cancel</a>

    <h3>Select cards to delete</h3>

    <!-- POST form containing a checkbox for each card in the deck -->
    <form method="POST">
        <?php
        // Load all cards in the deck so the owner can choose which ones to delete.
        $stmt = $pdo->prepare("SELECT * FROM cards WHERE deck_id = ?");
        $stmt->execute([$deck_id]);

        while ($card = $stmt->fetch()): ?>
            <!-- Checkbox value is the card ID, which will be sent back in selected_cards[] -->
            <input type="checkbox" name="selected_cards[]" value="<?php echo $card['id']; ?>">
            <!-- front text is escaped to prevent HTML injection -->
            <?php echo htmlspecialchars($card['front']); ?><br>
        <?php endwhile; ?>

        <br>
        <button type="submit" name="do_bulk_delete">Delete Selected</button>
    </form>
</body>
</html>