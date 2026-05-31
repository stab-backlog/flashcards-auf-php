<?php
require_once 'auth.php';
$type = $_GET['type'];
$id = $_GET['id'];
$deck_id = $_GET['deck_id'] ?? 0;

if (isset($_POST['confirm'])) {
    if ($type == 'deck') {
        $stmt = $pdo->prepare("DELETE FROM decks WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        header("Location: index.php");
    } else {
        $stmt = $pdo->prepare("DELETE FROM cards WHERE id = ? AND deck_id IN (SELECT id FROM decks WHERE user_id = ?)");
        $stmt->execute([$id, $_SESSION['user_id']]);
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
    <form method="POST">
        <button type="submit" name="confirm">Yes, Delete</button>
        <a href="index.php">Cancel</a>
    </form>
</body>
</html>
