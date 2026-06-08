<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'auth.php'; 

if (checkAuth()) {
    $user_id = $_SESSION['user_id'];
    $search_phrase = isset($_POST['search_phrase']) ? $_POST['search_phrase'] : '';

    if (isset($_POST['export_json'])) {
        $stmt = $pdo->prepare("SELECT * FROM decks WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $all_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($all_data as &$deck) {
            $stmt_cards = $pdo->prepare("SELECT front, back, difficulty, times_reviewed FROM cards WHERE deck_id = ?");
            $stmt_cards->execute([$deck['id']]);
            $deck['cards'] = $stmt_cards->fetchAll(PDO::FETCH_ASSOC);
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="anki_export.json"');
        echo json_encode($all_data);
        exit;
    }

    if (isset($_POST['import_json']) && isset($_FILES['json_file'])) {
        $json_content = @file_get_contents($_FILES['json_file']['tmp_name']);
        $data = json_decode($json_content, true);
        if ($data) {
            foreach ($data as $deck_data) {
                $stmt = $pdo->prepare("INSERT INTO decks (user_id, title, is_public) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $deck_data['title'], $deck_data['is_public']]);
                $new_deck_id = $pdo->lastInsertId();
                if (isset($deck_data['cards'])) {
                    foreach ($deck_data['cards'] as $card_data) {
                        $stmt = $pdo->prepare("INSERT INTO cards (deck_id, front, back, difficulty, times_reviewed) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$new_deck_id, $card_data['front'], $card_data['back'], $card_data['difficulty'] ?? 0, $card_data['times_reviewed'] ?? 0]);
                    }
                }
            }
        }
    }

    if (isset($_POST['create_deck'])) {
        $stmt = $pdo->prepare("INSERT INTO decks (user_id, title, is_public) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $_POST['title'], isset($_POST['is_public']) ? 1 : 0]);
    }
}
?>
<!DOCTYPE html>
<html>
<body>
<?php if (!checkAuth()): ?>
    <h2>Login / Register</h2>
    <form method="POST">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit" name="login">Login</button>
        <button type="submit" name="register">Register</button>
    </form>
<?php else: ?>
    <p>Logged in as <?php echo htmlspecialchars($_SESSION['username']); ?> | <a href="logout.php">Logout</a></p>
    
    <form method="POST">
        <h3>Create Deck</h3>
        Title: <input type="text" name="title" required>
        Public: <input type="checkbox" name="is_public">
        <button type="submit" name="create_deck">Create Deck</button>
    </form>

    <hr>
    <h3>Search (Decks & Cards)</h3>
    <form method="POST">
        Phrase: <input type="text" name="search_phrase" value="<?php echo htmlspecialchars($search_phrase); ?>">
        <button type="submit">Search</button>
        <a href="index.php">Clear</a>
    </form>

    <hr>
    <h3>Import / Export</h3>
    <form method="POST">
        <button type="submit" name="export_json">Export All My Decks (JSON)</button>
    </form>
    <br>
    <form method="POST" enctype="multipart/form-data">
        Import JSON File: <input type="file" name="json_file" required>
        <button type="submit" name="import_json">Import JSON</button>
    </form>
    <hr>

    <h3>My Decks</h3>
    <ul>
    <?php
    $query = "SELECT * FROM decks WHERE user_id = ?";
    $params = [$user_id];
    
    if ($search_phrase !== '') {
        $query .= " AND (title LIKE ? OR EXISTS (SELECT 1 FROM cards WHERE cards.deck_id = decks.id AND (front LIKE ? OR back LIKE ?)))";
        $term = "%$search_phrase%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $my_results = $stmt->fetchAll();

    if (!$my_results) echo "<li>No decks found.</li>";
    foreach ($my_results as $deck): ?>
        <li>
            <b><?php echo htmlspecialchars($deck['title']); ?></b>
            <a href="deck_view.php?id=<?php echo $deck['id']; ?>">Edit/View</a> |
            <a href="learn.php?deck_id=<?php echo $deck['id']; ?>&reset=1">Learn</a> |
            <a href="confirm_delete.php?type=deck&id=<?php echo $deck['id']; ?>">Delete</a>
        </li>
    <?php endforeach; ?>
    </ul>

    <h3>Public Decks (Others)</h3>
    <ul>
    <?php
    $query = "SELECT decks.*, users.username FROM decks 
              JOIN users ON decks.user_id = users.id 
              WHERE is_public = 1 AND user_id != ?";
    $params = [$user_id];
    
    if ($search_phrase !== '') {
        $query .= " AND (decks.title LIKE ? OR EXISTS (SELECT 1 FROM cards WHERE cards.deck_id = decks.id AND (front LIKE ? OR back LIKE ?)))";
        $term = "%$search_phrase%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $public_results = $stmt->fetchAll();

    if (!$public_results) echo "<li>No public decks found.</li>";
    foreach ($public_results as $pDeck): ?>
        <li>
            <?php echo htmlspecialchars($pDeck['title']); ?> by <?php echo htmlspecialchars($pDeck['username']); ?>
            <a href="deck_view.php?id=<?php echo $pDeck['id']; ?>">View</a> |
            <a href="learn.php?deck_id=<?php echo $pDeck['id']; ?>&reset=1">Learn</a>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
</body>
</html>
