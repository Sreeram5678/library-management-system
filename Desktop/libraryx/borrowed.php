<?php
require_once 'config.php';

// Get only currently borrowed books (where return_date is NULL)
$sql = "SELECT b.*, c.name as category_name, bb.borrow_date, bb.borrower_name, bb.due_date,
        DATEDIFF(CURRENT_DATE, bb.due_date) as days_overdue
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.id 
    JOIN borrowed_books bb ON b.id = bb.book_id 
    WHERE b.status = 'borrowed' 
    AND bb.return_date IS NULL
    ORDER BY bb.due_date ASC";
$result = $conn->query($sql);
$borrowed_books = [];
$overdue_count = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (strtotime($row['due_date']) < time()) {
            $overdue_count++;
        }
        $borrowed_books[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowed Books - LibraryX</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dark-mode.css">
    <style>
        .books-grid {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .book-card.borrowed {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 2rem 2.5rem;
            margin: 0 auto;
            max-width: 500px;
            font-size: 1.08rem;
        }
        .book-card.borrowed h3 {
            margin-bottom: 0.3rem;
        }
        .book-card.borrowed h3 a {
            color: #222;
            text-decoration: none;
            font-size: 1.22rem;
            font-weight: 700;
            transition: color 0.2s;
        }
        .book-card.borrowed h3 a:hover {
            color: #1976d2;
            text-decoration: underline;
        }
        .book-card.borrowed p {
            margin: 0.3rem 0;
        }
        .book-card.borrowed .author {
            color: #555;
            font-style: italic;
            font-size: 1rem;
            margin-bottom: 0.1rem;
        }
        .book-card.borrowed .category {
            color: #3498db;
            font-weight: 500;
            font-size: 1rem;
            margin-bottom: 0.1rem;
        }
        .return-btn {
            background: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 1rem;
            transition: background 0.2s;
        }
        .return-btn:hover {
            background: #c0392b;
        }
        .due-overdue {
            color: #c62828;
            font-weight: bold;
        }
        .due-normal {
            color: #2e7d32;
            font-weight: 500;
        }
        .book-card.borrowed.overdue {
            border-left: 4px solid #c62828;
            background: #fff8f8;
        }
        .overdue-badge {
            background: #c62828;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }
        .overdue-count {
            background: #c62828;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }
        .due-warning {
            color: #f57c00;
            font-weight: bold;
        }
        .days-overdue {
            font-size: 0.9rem;
            color: #c62828;
            margin-top: 0.3rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="nav-bar">
            <h1>LibraryX</h1>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="borrowed.php" class="active">
                    Borrowed Books
                    <?php if ($overdue_count > 0): ?>
                        <span class="overdue-count"><?php echo $overdue_count; ?> overdue</span>
                    <?php endif; ?>
                </a>
                <a href="history.php">Borrowing History</a>
                <a href="wishlist.php">Wishlist</a>
            </div>
        </nav>

        <main>
            <h2>Borrowed Books</h2>
            <?php if ($overdue_count > 0): ?>
                <div class="alert error" style="margin-bottom: 1.5rem;">
                    You have <?php echo $overdue_count; ?> overdue book<?php echo $overdue_count > 1 ? 's' : ''; ?>. Please return them as soon as possible.
                </div>
            <?php endif; ?>
            <div class="books-grid">
                <?php if (empty($borrowed_books)): ?>
                    <div class="no-books">
                        <p>No books are currently borrowed.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($borrowed_books as $book): ?>
                        <?php 
                            $is_overdue = strtotime($book['due_date']) < time();
                            $days_overdue = $is_overdue ? floor((time() - strtotime($book['due_date'])) / (60 * 60 * 24)) : 0;
                            $is_warning = !$is_overdue && strtotime($book['due_date']) - time() < 3 * 24 * 60 * 60; // 3 days warning
                        ?>
                        <div class="book-card borrowed <?php echo $is_overdue ? 'overdue' : ''; ?>">
                            <h3>
                                <a href="book_details.php?id=<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a>
                                <?php if ($is_overdue): ?>
                                    <span class="overdue-badge">Overdue</span>
                                <?php endif; ?>
                            </h3>
                            <p>Author: <?php echo htmlspecialchars($book['author']); ?></p>
                            <p>Category: <?php echo htmlspecialchars($book['category_name']); ?></p>
                            <p>ISBN: <?php echo htmlspecialchars($book['isbn']); ?></p>
                            <p>Borrowed by: <?php echo htmlspecialchars($book['borrower_name']); ?></p>
                            <p>Borrowed on: <?php echo date('Y-m-d H:i', strtotime($book['borrow_date'])); ?></p>
                            <p>
                                Due date: 
                                <?php 
                                    if ($is_overdue) {
                                        echo '<span class="due-overdue">' . date('Y-m-d', strtotime($book['due_date'])) . '</span>';
                                        echo '<div class="days-overdue">Overdue by ' . $days_overdue . ' day' . ($days_overdue != 1 ? 's' : '') . '</div>';
                                    } elseif ($is_warning) {
                                        echo '<span class="due-warning">' . date('Y-m-d', strtotime($book['due_date'])) . ' (Due soon)</span>';
                                    } else {
                                        echo '<span class="due-normal">' . date('Y-m-d', strtotime($book['due_date'])) . '</span>';
                                    }
                                ?>
                            </p>
                            <button class="return-btn" data-book-id="<?php echo $book['id']; ?>">Return Book</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="script.js"></script>
    <script src="dark-mode.js"></script>
</body>
</html> 