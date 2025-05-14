<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    die('Book ID not specified.');
}
$book_id = intval($_GET['id']);

// Get book details
$sql = "SELECT b.*, c.name as category FROM books b JOIN categories c ON b.category_id = c.id WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
if (!$book) {
    die('Book not found.');
}

// Get reviews
$sql = "SELECT * FROM book_reviews WHERE book_id = ? ORDER BY review_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$reviews = $stmt->get_result();

// Get borrowing history
$sql = "SELECT * FROM borrowed_books WHERE book_id = ? ORDER BY borrow_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$history = $stmt->get_result();

// Get recommendations
$sql = "SELECT id, title, author FROM books WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT 3";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $book['category_id'], $book_id);
$stmt->execute();
$recommendations = $stmt->get_result();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reviewer_name'], $_POST['rating'], $_POST['review'])) {
    $reviewer_name = $_POST['reviewer_name'];
    $rating = intval($_POST['rating']);
    $review = $_POST['review'];
    $sql = "INSERT INTO book_reviews (book_id, reviewer_name, rating, review) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isis", $book_id, $reviewer_name, $rating, $review);
    $stmt->execute();
    header("Location: book_details.php?id=$book_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - Details</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .book-details-container {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            margin-bottom: 2rem;
        }
        .book-meta {
            flex: 1;
        }
        .section {
            background: #fafbfc;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .reviews-list, .history-section ul, .recommendations-section ul {
            margin-top: 1rem;
        }
        .review-item {
            border-bottom: 1px solid #eee;
            padding: 0.7rem 0;
        }
        .review-item:last-child {
            border-bottom: none;
        }
        .rating {
            color: #f39c12;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        .review-date {
            color: #888;
            font-size: 0.95em;
            margin-left: 1rem;
        }
        .review-form {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .review-form input, .review-form select, .review-form textarea {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 1rem;
        }
        .review-form textarea {
            flex: 2;
            min-width: 180px;
            min-height: 40px;
        }
        .review-form button {
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 0.5rem 1.2rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .review-form button:hover {
            background: #217dbb;
        }
        .recommendations-section ul {
            list-style: none;
            padding-left: 0;
        }
        .recommendations-section li {
            margin-bottom: 0.7rem;
        }
        .recommendation-title {
            color: #222;
            font-weight: 700;
            font-size: 1.08rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .recommendation-title:hover {
            color: #1976d2;
            text-decoration: underline;
        }
        .recommendation-author {
            color: #555;
            font-style: italic;
            font-size: 1rem;
            margin-left: 0.3rem;
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
                <a href="wishlist.php">Wishlist</a>
            </div>
        </nav>
        <main>
            <div class="book-details-container section">
                <div class="book-meta">
                    <h2><?php echo htmlspecialchars($book['title']); ?></h2>
                    <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?></p>
                    <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($book['description']); ?></p>
                    <p><strong>Status:</strong> <span class="status <?php echo $book['status']; ?>"><?php echo ucfirst($book['status']); ?></span></p>
                </div>
            </div>
            <div class="reviews-section section">
                <h3>Reviews & Ratings</h3>
                <form method="post" class="review-form">
                    <input type="text" name="reviewer_name" placeholder="Your Name" required>
                    <select name="rating" required>
                        <option value="">Rating</option>
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Good</option>
                        <option value="3">3 - Average</option>
                        <option value="2">2 - Poor</option>
                        <option value="1">1 - Terrible</option>
                    </select>
                    <textarea name="review" placeholder="Write your review..." required></textarea>
                    <button type="submit" class="btn">Submit Review</button>
                </form>
                <?php if ($reviews->num_rows > 0): ?>
                    <div class="reviews-list">
                        <?php while($r = $reviews->fetch_assoc()): ?>
                            <div class="review-item">
                                <strong><?php echo htmlspecialchars($r['reviewer_name']); ?></strong>
                                <span class="rating">Rating: <?php echo $r['rating']; ?>/5</span>
                                <p><?php echo nl2br(htmlspecialchars($r['review'])); ?></p>
                                <span class="review-date"><?php echo date('F j, Y', strtotime($r['review_date'])); ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No reviews yet.</p>
                <?php endif; ?>
            </div>
            <div class="history-section section">
                <h3>Borrowing History</h3>
                <?php if ($history->num_rows > 0): ?>
                    <ul>
                        <?php while($h = $history->fetch_assoc()): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($h['borrower_name']); ?></strong> borrowed on <?php echo date('F j, Y', strtotime($h['borrow_date'])); ?>
                                <?php if ($h['return_date']): ?>, returned on <?php echo date('F j, Y', strtotime($h['return_date'])); ?><?php endif; ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No borrowing history for this book.</p>
                <?php endif; ?>
            </div>
            <div class="recommendations-section section">
                <h3>You might also like</h3>
                <?php if ($recommendations->num_rows > 0): ?>
                    <ul>
                        <?php while($rec = $recommendations->fetch_assoc()): ?>
                            <li>
                                <a class="recommendation-title" href="book_details.php?id=<?php echo $rec['id']; ?>">
                                    <?php echo htmlspecialchars($rec['title']); ?>
                                </a>
                                <span class="recommendation-author">by <?php echo htmlspecialchars($rec['author']); ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No recommendations available.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 