<?php 
require_once 'auth.php'; 

// Handle Deck Creation
if (isset($_POST['create_deck']) && checkAuth()) {
    $stmt = $pdo->prepare("INSERT INTO decks (user_id, title, is_public) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_POST['title'], isset($_POST['is_public']) ? 1 : 0]);
}

// Handle Deck Deletion
if (isset($_POST['delete_deck']) && checkAuth()) {
    $stmt = $pdo->prepare("DELETE FROM decks WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['deck_id'], $_SESSION['user_id']]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Flashcards</title>
</head>
<body>

<?php if (!checkAuth()): ?>
    <div>
        <h2>Login or Register</h2>
        <?php echo $msg;?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required><br><br>
            <input type="password" name="password" placeholder="Password" required><br><br>
            <button type="submit" name="login">Login</button>
            <button type="submit" name="register">Register</button>
        </form>
    </div>
<?php else: ?>
    <div>
        <h1>My Decks</h1>
        <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
    </div>

    <div>
        <h3>Create New Deck</h3>
        <form method="POST">
            <input type="text" name="title" placeholder="Deck Title" required>
            <label><input type="checkbox" name="is_public"> Public Deck</label>
            <button type="submit" name="create_deck">Create</button>
        </form>
    </div>

    <div>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM decks WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $myDecks = $stmt->fetchAll();

        if (!$myDecks) echo "No decks created yet.";
        foreach ($myDecks as $deck): ?>
            <div class="deck-row">
                <a class="deck-link" href="deck_view.php?id=<?php echo $deck['id']; ?>">
                    <?php echo htmlspecialchars($deck['title']); ?> 
                    <small>(<?php echo $deck['is_public'] ? 'Public' : 'Private'; ?>)</small>
                </a>
                <form method="POST" onsubmit="return confirm('Delete this deck and all its cards?');">
                    <input type="hidden" name="deck_id" value="<?php echo $deck['id']; ?>">
                    <button type="submit" name="delete_deck" class="btn-del">Delete Deck</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <h2>Public Decks (Browse)</h2>
    <div>
        <?php
        $stmt = $pdo->prepare("SELECT decks.*, users.username FROM decks JOIN users ON decks.user_id = users.id WHERE is_public = 1 AND user_id != ?");
        $stmt->execute([$_SESSION['user_id']]);
        $publicDecks = $stmt->fetchAll();
        
        if (!$publicDecks) echo "No public decks available.";
        foreach ($publicDecks as $pDeck): ?>
            <div class="deck-row">
                <a class="deck-link" href="deck_view.php?id=<?php echo $pDeck['id']; ?>">
                    <?php echo htmlspecialchars($pDeck['title']); ?> 
                </a>
                <span>by <?php echo htmlspecialchars($pDeck['username']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</body>
</html>
