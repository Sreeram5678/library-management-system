<?php
require_once 'config.php';

$name = isset($_GET['name']) ? trim($_GET['name']) : '';
$history = $wishlist = $reviews = [];

if ($name !== '') {
    // Borrowing history
    $sql = "SELECT bb.*, b.title, b.author FROM borrowed_books bb JOIN books b ON bb.book_id = b.id WHERE bb.borrower_name = ? ORDER BY bb.borrow_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $history = $stmt->get_result();

    // Wishlist
    $sql = "SELECT w.*, b.title, b.author FROM wishlist w JOIN books b ON w.book_id = b.id WHERE w.borrower_name = ? ORDER BY w.added_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $wishlist = $stmt->get_result();

    // Reviews
    $sql = "SELECT r.*, b.title FROM book_reviews r JOIN books b ON r.book_id = b.id WHERE r.reviewer_name = ? ORDER BY r.review_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $reviews = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - LibraryX</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-section { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 2rem; margin-bottom: 2rem; }
        .profile-section h3 { margin-bottom: 1rem; }
        .profile-form { margin-bottom: 2rem; }
        .profile-form input { padding: 0.7rem 1.1rem; border-radius: 6px; border: 1px solid #ddd; font-size: 1.08rem; }
        .profile-form button { background: #3498db; color: #fff; border-radius: 6px; font-weight: 600; font-size: 1.08rem; padding: 0.7rem 2.2rem; border: none; margin-left: 1rem; }
        .profile-list { margin-top: 1rem; }
        .profile-list li { margin-bottom: 0.7rem; }
        .review-item { border-bottom: 1px solid #eee; padding: 0.7rem 0; }
        .review-item:last-child { border-bottom: none; }
        .rating { color: #f39c12; font-weight: bold; margin-left: 0.5rem; }
        .review-date { color: #888; font-size: 0.95em; margin-left: 1rem; }
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
                <a href="wishlist.php">Wishlist</a>
                <a href="profile.php" class="active">Profile</a>
            </div>
        </nav>
        <main>
            <form class="profile-form" method="get" action="profile.php">
                <input type="text" name="name" placeholder="Enter your name" value="<?php echo htmlspecialchars($name); ?>" required>
                <button type="submit">View Profile</button>
            </form>
            <?php if ($name !== ''): ?>
                <div class="profile-section">
                    <h3>Borrowing History</h3>
                    <?php if ($history && $history->num_rows > 0): ?>
                        <ul class="profile-list">
                            <?php while($h = $history->fetch_assoc()): ?>
                                <li><strong><?php echo htmlspecialchars($h['title']); ?></strong> by <?php echo htmlspecialchars($h['author']); ?> (Borrowed: <?php echo date('F j, Y', strtotime($h['borrow_date'])); ?><?php if ($h['return_date']): ?>, Returned: <?php echo date('F j, Y', strtotime($h['return_date'])); ?><?php endif; ?>)</li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No borrowing history found.</p>
                    <?php endif; ?>
                </div>
                <div class="profile-section">
                    <h3>Wishlist</h3>
                    <?php if ($wishlist && $wishlist->num_rows > 0): ?>
                        <ul class="profile-list">
                            <?php while($w = $wishlist->fetch_assoc()): ?>
                                <li><strong><?php echo htmlspecialchars($w['title']); ?></strong> by <?php echo htmlspecialchars($w['author']); ?></li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No wishlist items found.</p>
                    <?php endif; ?>
                </div>
                <div class="profile-section">
                    <h3>Reviews</h3>
                    <?php if ($reviews && $reviews->num_rows > 0): ?>
                        <div class="reviews-list">
                            <?php while($r = $reviews->fetch_assoc()): ?>
                                <div class="review-item">
                                    <strong><?php echo htmlspecialchars($r['title']); ?></strong>
                                    <span class="rating">Rating: <?php echo $r['rating']; ?>/5</span>
                                    <p><?php echo nl2br(htmlspecialchars($r['review'])); ?></p>
                                    <span class="review-date"><?php echo date('F j, Y', strtotime($r['review_date'])); ?></span>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p>No reviews found.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 