<?php
require_once 'auth.php';
if (!checkAuth()) { header("Location: index.php"); exit; }

$deck_id = $_GET['id'] ?? 0;
$card_search = isset($_GET['card_search']) ? $_GET['card_search'] : '';

$stmt = $pdo->prepare("SELECT * FROM decks WHERE id = ?");
$stmt->execute([$deck_id]);
$deck = $stmt->fetch();

if (!$deck || (!$deck['is_public'] && $deck['user_id'] != $_SESSION['user_id'])) { die("Denied."); }
$is_owner = ($deck['user_id'] == $_SESSION['user_id']);

if ($is_owner && isset($_POST['update_card'])) {
    $stmt = $pdo->prepare("UPDATE cards SET front = ?, back = ? WHERE id = ? AND deck_id = ?");
    $stmt->execute([$_POST['front'], $_POST['back'], $_POST['card_id'], $deck_id]);
}

if ($is_owner && isset($_POST['add_card'])) {
    $stmt = $pdo->prepare("INSERT INTO cards (deck_id, front, back) VALUES (?, ?, ?)");
    $stmt->execute([$deck_id, $_POST['front'], $_POST['back']]);
}
?>
<!DOCTYPE html>
<html>
<body>
<a href="index.php">Back to Home</a>
<h2>Deck: <?php echo htmlspecialchars($deck['title']); ?></h2>

<?php if ($is_owner): ?>
    <h4>Search in this Deck</h4>
    <form method="GET" action="deck_view.php">
        <input type="hidden" name="id" value="<?php echo $deck_id; ?>">
        Search Phrase: <input type="text" name="card_search" value="<?php echo htmlspecialchars($card_search); ?>">
        <button type="submit">Filter Cards</button>
        <a href="deck_view.php?id=<?php echo $deck_id; ?>">Clear Filter</a>
    </form>
    <br>

    <h4>Add Card</h4>
    <form method="POST">
        Front: <input type="text" name="front" required>
        Back: <input type="text" name="back" required>
        <button type="submit" name="add_card">Add Card</button>
    </form>
    <br>
    <a href="bulk_select.php?deck_id=<?php echo $deck_id; ?>">Delete Multiple Cards</a>
<?php endif; ?>

<table>
    <tr>
        <th>Front</th>
        <th>Back</th>
        <?php if ($is_owner): ?><th>Actions</th><?php endif; ?>
    </tr>
    <?php
    $sql = "SELECT * FROM cards WHERE deck_id = ?";
    $params = [$deck_id];

    if ($card_search !== '') {
        $sql .= " AND (front LIKE ? OR back LIKE ?)";
        $params[] = "%$card_search%";
        $params[] = "%$card_search%";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cards = $stmt->fetchAll();

    if (!$cards) {
        echo "<tr><td colspan='3'>No cards found matching your criteria.</td></tr>";
    }

    foreach ($cards as $card): ?>
    <tr>
        <?php if ($is_owner): ?>
            <form method="POST">
                <td><input type="text" name="front" value="<?php echo htmlspecialchars($card['front']); ?>"></td>
                <td><input type="text" name="back" value="<?php echo htmlspecialchars($card['back']); ?>"></td>
                <td>
                    <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                    <button type="submit" name="update_card">Save</button>
                    <a href="confirm_delete.php?type=card&id=<?php echo $card['id']; ?>&deck_id=<?php echo $deck_id; ?>">Delete</a>
                </td>
            </form>
        <?php else: ?>
            <td><?php echo htmlspecialchars($card['front']); ?></td>
            <td><?php echo htmlspecialchars($card['back']); ?></td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>