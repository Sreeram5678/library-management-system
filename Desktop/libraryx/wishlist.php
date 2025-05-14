<?php
require_once 'config.php';

// Handle adding to wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_wishlist'])) {
    $book_id = $_POST['book_id'];
    $borrower_name = $_POST['borrower_name'];
    
    $sql = "INSERT INTO wishlist (book_id, borrower_name) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $book_id, $borrower_name);
    $stmt->execute();
}

// Handle removing from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_wishlist'])) {
    $wishlist_id = $_POST['wishlist_id'];
    
    $sql = "DELETE FROM wishlist WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $wishlist_id);
    $stmt->execute();
}

// Get wishlist items
$sql = "SELECT w.*, b.title, b.author, b.isbn, b.status, c.name as category 
        FROM wishlist w 
        JOIN books b ON w.book_id = b.id 
        JOIN categories c ON b.category_id = c.id 
        ORDER BY w.added_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - LibraryX</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .book-card h3 {
            margin-bottom: 0.3rem;
        }
        .book-card h3 a {
            color: #222;
            text-decoration: none;
            font-size: 1.22rem;
            font-weight: 700;
            transition: color 0.2s;
        }
        .book-card h3 a:hover {
            color: #1976d2;
            text-decoration: underline;
        }
        .book-card .author {
            color: #555;
            font-style: italic;
            font-size: 1rem;
            margin-bottom: 0.1rem;
        }
        .book-card .category {
            color: #3498db;
            font-weight: 500;
            font-size: 1rem;
            margin-bottom: 0.1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="nav-bar">
            <h1>LibraryX</h1>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="borrowed.php">Borrowed Books</a>
                <a href="history.php">Borrowing History</a>
                <a href="wishlist.php" class="active">Wishlist</a>
            </div>
        </nav>

        <main>
            <h2>My Wishlist</h2>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="wishlist-grid">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="book-card">
                            <div class="book-info">
                                <h3><a href="book_details.php?id=<?php echo $row['book_id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h3>
                                <p class="author">by <?php echo htmlspecialchars($row['author']); ?></p>
                                <p class="category"><?php echo htmlspecialchars($row['category']); ?></p>
                                <p class="isbn">ISBN: <?php echo htmlspecialchars($row['isbn']); ?></p>
                                <p class="status <?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </p>
                            </div>
                            <div class="book-actions">
                                <?php if ($row['status'] === 'available'): ?>
                                    <form action="index.php" method="post" class="borrow-form">
                                        <input type="hidden" name="book_id" value="<?php echo $row['book_id']; ?>">
                                        <input type="text" name="borrower_name" placeholder="Your Name" required>
                                        <button type="submit" name="borrow_book" class="btn borrow-btn">Borrow Now</button>
                                    </form>
                                <?php endif; ?>
                                <form action="wishlist.php" method="post">
                                    <input type="hidden" name="wishlist_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="remove_from_wishlist" class="btn remove-btn">Remove from Wishlist</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-books">Your wishlist is empty.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 