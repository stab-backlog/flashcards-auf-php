<?php
require_once 'auth.php';
$deck_id = $_GET['deck_id'];
$stmt = $pdo->prepare("SELECT * FROM decks WHERE id = ? AND user_id = ?");
$stmt->execute([$deck_id, $_SESSION['user_id']]);
if (!$stmt->fetch()) die("Denied");

if (isset($_POST['do_bulk_delete']) && !empty($_POST['selected_cards'])) {
    $ids = $_POST['selected_cards'];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM cards WHERE id IN ($placeholders) AND deck_id = ?");
    $stmt->execute(array_merge($ids, [$deck_id]));
    header("Location: deck_view.php?id=$deck_id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<body>
    <a href="deck_view.php?id=<?php echo $deck_id; ?>">Cancel</a>
    <h3>Select cards to delete</h3>
    <form method="POST">
        <?php
        $stmt = $pdo->prepare("SELECT * FROM cards WHERE deck_id = ?");
        $stmt->execute([$deck_id]);
        while ($card = $stmt->fetch()): ?>
            <input type="checkbox" name="selected_cards[]" value="<?php echo $card['id']; ?>">
            <?php echo htmlspecialchars($card['front']); ?><br>
        <?php endwhile; ?>
        <br>
        <button type="submit" name="do_bulk_delete">Delete Selected</button>
    </form>
</body>
</html>
