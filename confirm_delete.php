<?php
// Shared auth/database bootstrap.
require_once 'auth.php';

// Deletion target type, expected to be either "deck" or "card".
$type = $_GET['type'];

// Record ID of the thing to delete.
$id = $_GET['id'];

// Optional deck ID used when redirecting back after deleting a card.
$deck_id = $_GET['deck_id'] ?? 0;

// If the confirmation form was submitted, perform the deletion.
if (isset($_POST['confirm'])) {
    if ($type == 'deck') {
        // Delete only decks owned by the current user.
        $stmt = $pdo->prepare("DELETE FROM decks WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);

        // Go back to the home page after removing the deck.
        header("Location: index.php");
    } else {
        // Delete a card only if it belongs to a deck owned by the current user.
        $stmt = $pdo->prepare("DELETE FROM cards WHERE id = ? AND deck_id IN (SELECT id FROM decks WHERE user_id = ?)");
        $stmt->execute([$id, $_SESSION['user_id']]);

        // Go back to the deck view after removing the card.
        header("Location: deck_view.php?id=$deck_id");
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<body>
    <h3>Confirm Deletion</h3>
    <p>Are you sure you want to delete this <?php echo $type; ?>?</p>

    <!-- Confirm button submits the POST request that actually performs the delete -->
    <form method="POST">
        <button type="submit" name="confirm">Yes, Delete</button>
        <a href="index.php">Cancel</a>
    </form>
</body>
</html>