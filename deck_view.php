<?php
require_once 'auth.php';

if (!checkAuth()) { header("Location: index.php"); exit; }

$deck_id = $_GET['id'] ?? 0;

// Fetch Deck Info
$stmt = $pdo->prepare("SELECT * FROM decks WHERE id = ?");
$stmt->execute([$deck_id]);
$deck = $stmt->fetch();

if (!$deck || (!$deck['is_public'] && $deck['user_id'] != $_SESSION['user_id'])) {
    die("Deck not found or access denied.");
}

$is_owner = ($deck['user_id'] == $_SESSION['user_id']);

// Logic: Delete Selected Cards
if ($is_owner && isset($_POST['bulk_delete']) && !empty($_POST['selected_cards'])) {
    $placeholders = implode(',', array_fill(0, count($_POST['selected_cards']), '?'));
    $stmt = $pdo->prepare("DELETE FROM cards WHERE id IN ($placeholders) AND deck_id = ?");
    $params = array_merge($_POST['selected_cards'], [$deck_id]);
    $stmt->execute($params);
}

// Logic: Update Individual Card
if ($is_owner && isset($_POST['update_card'])) {
    $stmt = $pdo->prepare("UPDATE cards SET front = ?, back = ? WHERE id = ? AND deck_id = ?");
    $stmt->execute([$_POST['front'], $_POST['back'], $_POST['card_id'], $deck_id]);
}

// Logic: Add New Card
if ($is_owner && isset($_POST['add_card'])) {
    $stmt = $pdo->prepare("INSERT INTO cards (deck_id, front, back) VALUES (?, ?, ?)");
    $stmt->execute([$deck_id, $_POST['front'], $_POST['back']]);
}

// Fetch all cards for this deck
$stmt = $pdo->prepare("SELECT * FROM cards WHERE deck_id = ?");
$stmt->execute([$deck_id]);
$cards = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($deck['title']); ?></title>
    <script>
        function toggleAll(source) {
            checkboxes = document.getElementsByName('selected_cards[]');
            for(var i=0, n=checkboxes.length;i<n;i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
</head>
<body>

<div>
    <a href="index.php">← Back to Dashboard</a>
</div>

<h1>Deck: <?php echo htmlspecialchars($deck['title']); ?></h1>

<?php if ($is_owner): ?>
<div>
    <h3>Add New Card</h3>
    <form method="POST">
        <input type="text" name="front" placeholder="Question" required>
        <input type="text" name="back" placeholder="Answer" required>
        <button type="submit" name="add_card">Add Card</button>
    </form>
</div>
<?php endif; ?>

<form method="POST" onsubmit="return confirm('Delete selected cards?');">
    <div>
        <?php if ($is_owner): ?>
            <button type="submit" name="bulk_delete">Delete Selected</button>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <?php if ($is_owner): ?><th><input type="checkbox" onclick="toggleAll(this)"></th><?php endif; ?>
                    <th>Question (Front)</th>
                    <th>Answer (Back)</th>
                    <?php if ($is_owner): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cards as $card): ?>
                <tr>
                    <?php if ($is_owner): ?>
                        <td><input type="checkbox" name="selected_cards[]" value="<?php echo $card['id']; ?>"></td>
                    <?php endif; ?>

                    <?php if ($is_owner): ?>
                        <form method="POST">
                            <td><input type="text" name="front" value="<?php echo htmlspecialchars($card['front']); ?>"></td>
                            <td><input type="text" name="back" value="<?php echo htmlspecialchars($card['back']); ?>"></td>
                            <td>
                                <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                <button type="submit" name="update_card">Save</button>
                            </td>
                        </form>
                    <?php else: ?>
                        <td><?php echo htmlspecialchars($card['front']); ?></td>
                        <td><?php echo htmlspecialchars($card['back']); ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (!$cards) echo "<tr><td colspan='4'>No cards in this deck yet.</td></tr>"; ?>
            </tbody>
        </table>
    </div>
</form>

</body>
</html>