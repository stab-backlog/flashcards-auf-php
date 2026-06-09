<?php 
// Turn on visible errors for development. 
error_reporting(E_ALL);
ini_set('display_errors', 1);

// auth.php starts the session and makes $pdo available.
require_once 'auth.php'; 

// Main application logic only runs for authenticated users.
if (checkAuth()) {
    $user_id = $_SESSION['user_id'];

    // Search term used to filter decks and cards.
    $search_phrase = isset($_POST['search_phrase']) ? $_POST['search_phrase'] : '';

    // Export all decks for the current user as JSON.
    if (isset($_POST['export_json'])) {
        // Load all deck rows owned by this user.
        $stmt = $pdo->prepare("SELECT * FROM decks WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $all_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach card data to each deck before exporting.
        foreach ($all_data as &$deck) {
            $stmt_cards = $pdo->prepare("SELECT front, back, difficulty, times_reviewed FROM cards WHERE deck_id = ?");
            $stmt_cards->execute([$deck['id']]);
            $deck['cards'] = $stmt_cards->fetchAll(PDO::FETCH_ASSOC);
        }

        // Tell the browser this is a downloadable JSON file.
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="anki_export.json"');

        echo json_encode($all_data);
        exit;
    }

    // Import decks/cards from an uploaded JSON file.
    if (isset($_POST['import_json']) && isset($_FILES['json_file'])) {
        // Suppress file-read warnings and read the uploaded file contents.
        $json_content = @file_get_contents($_FILES['json_file']['tmp_name']);
        $data = json_decode($json_content, true);

        // If the JSON is valid, loop through decks and cards.
        if ($data) {
            foreach ($data as $deck_data) {
                // Create each imported deck under the current user.
                $stmt = $pdo->prepare("INSERT INTO decks (user_id, title, is_public) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $deck_data['title'], $deck_data['is_public']]);
                $new_deck_id = $pdo->lastInsertId();

                // Import each card attached to that deck.
                if (isset($deck_data['cards'])) {
                    foreach ($deck_data['cards'] as $card_data) {
                        $stmt = $pdo->prepare("INSERT INTO cards (deck_id, front, back, difficulty, times_reviewed) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $new_deck_id,
                            $card_data['front'],
                            $card_data['back'],
                            $card_data['difficulty'] ?? 0,
                            $card_data['times_reviewed'] ?? 0
                        ]);
                    }
                }
            }
        }
    }

    // Create a new deck belonging to the current user.
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
    <!-- Login/register form shown to anonymous visitors -->
    <h2>Login / Register</h2>
    <form method="POST">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit" name="login">Login</button>
        <button type="submit" name="register">Register</button>
    </form>
<?php else: ?>
    <p>Logged in as <?php echo htmlspecialchars($_SESSION['username']); ?> | <a href="logout.php">Logout</a></p>
    
    <!-- Deck creation form -->
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

    <!-- Owner-only deck list -->
    <h3>My Decks</h3>
    <ul>
    <?php
    // Query only the current user's decks.
    $query = "SELECT * FROM decks WHERE user_id = ?";
    $params = [$user_id];
    
    // Optional search across deck title and any card front/back text.
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

    // Show each deck with links to edit, learn, or delete.
    foreach ($my_results as $deck): ?>
        <li>
            <b><?php echo htmlspecialchars($deck['title']); ?></b>
            <a href="deck_view.php?id=<?php echo $deck['id']; ?>">Edit/View</a> |
            <a href="learn.php?deck_id=<?php echo $deck['id']; ?>&reset=1">Learn</a> |
            <a href="confirm_delete.php?type=deck&id=<?php echo $deck['id']; ?>">Delete</a>
        </li>
    <?php endforeach; ?>
    </ul>

    <!-- Public decks created by other users -->
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