<?php
require_once 'config.php';

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['book_id']) || !isset($data['borrower_name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$book_id = $data['book_id'];
$borrower_name = trim($data['borrower_name']);

if (empty($borrower_name)) {
    echo json_encode(['success' => false, 'message' => 'Borrower name is required']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check if book is available
    $stmt = $pdo->prepare("SELECT status FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();

    if (!$book) {
        throw new Exception('Book not found');
    }

    if ($book['status'] !== 'available') {
        throw new Exception('Book is not available for borrowing');
    }

    // Update book status
    $stmt = $pdo->prepare("UPDATE books SET status = 'borrowed' WHERE id = ?");
    $stmt->execute([$book_id]);

    // Create borrow record
    $stmt = $pdo->prepare("INSERT INTO borrowed_books (book_id, borrower_name) VALUES (?, ?)");
    $stmt->execute([$book_id, $borrower_name]);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 