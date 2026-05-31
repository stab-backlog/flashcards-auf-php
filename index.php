<?php 
require_once 'auth.php'; 

if (isset($_POST['create_deck']) && checkAuth()) {
    $stmt = $pdo->prepare("INSERT INTO decks (user_id, title, is_public) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_POST['title'], isset($_POST['is_public']) ? 1 : 0]);
}
?>
<!DOCTYPE html>
<html>
<body>
<?php if (!checkAuth()): ?>
    <h2>Login / Register</h2>
    <?php if(isset($auth_msg)) echo $auth_msg; ?>
    <form method="POST">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit" name="login">Login</button>
        <button type="submit" name="register">Register</button>
    </form>
<?php else: ?>
    <p>Logged in as <?php echo $_SESSION['username']; ?> | <a href="logout.php">Logout</a></p>
    
    <h3>Create Deck</h3>
    <form method="POST">
        Title: <input type="text" name="title" required>
        Public: <input type="checkbox" name="is_public">
        <button type="submit" name="create_deck">Create</button>
    </form>

    <h3>My Decks</h3>
    <ul>
    <?php
    $stmt = $pdo->prepare("SELECT * FROM decks WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    while ($deck = $stmt->fetch()): ?>
        <li>
            <a href="deck_view.php?id=<?php echo $deck['id']; ?>"><?php echo htmlspecialchars($deck['title']); ?></a>
            (<?php echo $deck['is_public'] ? 'Public' : 'Private'; ?>)
            - <a href="confirm_delete.php?type=deck&id=<?php echo $deck['id']; ?>">Delete</a>
        </li>
    <?php endwhile; ?>
    </ul>

    <h3>Public Decks</h3>
    <ul>
    <?php
    $stmt = $pdo->prepare("SELECT decks.*, users.username FROM decks JOIN users ON decks.user_id = users.id WHERE is_public = 1 AND user_id != ?");
    $stmt->execute([$_SESSION['user_id']]);
    while ($pDeck = $stmt->fetch()): ?>
        <li>
            <a href="deck_view.php?id=<?php echo $pDeck['id']; ?>"><?php echo htmlspecialchars($pDeck['title']); ?></a> 
            by <?php echo htmlspecialchars($pDeck['username']); ?>
        </li>
    <?php endwhile; ?>
    </ul>
<?php endif; ?>
</body>
</html>
