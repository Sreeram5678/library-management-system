<?php
require_once 'config.php';

// Get all borrowing history
$sql = "SELECT bb.*, b.title, b.author, b.isbn, c.name as category 
        FROM borrowed_books bb 
        JOIN books b ON bb.book_id = b.id 
        JOIN categories c ON b.category_id = c.id 
        ORDER BY bb.borrow_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing History - LibraryX</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <nav class="nav-bar">
            <h1>LibraryX</h1>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="borrowed.php">Borrowed Books</a>
                <a href="history.php" class="active">Borrowing History</a>
                <a href="wishlist.php">Wishlist</a>
            </div>
        </nav>

        <main>
            <h2>Borrowing History</h2>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="history-list">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="history-item">
                            <div class="book-info">
                                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                                <p class="author">by <?php echo htmlspecialchars($row['author']); ?></p>
                                <p class="category"><?php echo htmlspecialchars($row['category']); ?></p>
                                <p class="isbn">ISBN: <?php echo htmlspecialchars($row['isbn']); ?></p>
                            </div>
                            <div class="borrow-info">
                                <p><strong>Borrower:</strong> <?php echo htmlspecialchars($row['borrower_name']); ?></p>
                                <p><strong>Borrowed:</strong> <?php echo date('F j, Y', strtotime($row['borrow_date'])); ?></p>
                                <?php if ($row['due_date']): ?>
                                    <p><strong>Due:</strong> <?php echo date('F j, Y', strtotime($row['due_date'])); ?></p>
                                <?php endif; ?>
                                <?php if ($row['return_date']): ?>
                                    <p><strong>Returned:</strong> <?php echo date('F j, Y', strtotime($row['return_date'])); ?></p>
                                <?php else: ?>
                                    <p class="status borrowed">Currently Borrowed</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-books">No borrowing history found.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 