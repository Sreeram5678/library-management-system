<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['book_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing book ID',
        'error' => 'validation_error'
    ]);
    exit;
}

$book_id = $data['book_id'];

// Start transaction
$conn->begin_transaction();

try {
    // Check if book is borrowed
    $stmt = $conn->prepare("SELECT b.status, b.title, bb.borrower_name FROM books b LEFT JOIN borrowed_books bb ON b.id = bb.book_id AND bb.return_date IS NULL WHERE b.id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();

    if (!$book) {
        throw new Exception('Book not found');
    }

    if ($book['status'] !== 'borrowed') {
        throw new Exception('Book is not currently borrowed');
    }

    // Update book status
    $stmt = $conn->prepare("UPDATE books SET status = 'available' WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();

    // Update borrow record
    $stmt = $conn->prepare("UPDATE borrowed_books SET return_date = CURRENT_TIMESTAMP WHERE book_id = ? AND return_date IS NULL");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    // Check for pending notifications
    $notif_stmt = $conn->prepare("SELECT name, email FROM notifications WHERE book_id = ? AND notified = 0");
    $notif_stmt->bind_param("i", $book_id);
    $notif_stmt->execute();
    $notif_result = $notif_stmt->get_result();
    $notified_users = [];
    while ($notif = $notif_result->fetch_assoc()) {
        $notified_users[] = [
            'name' => $notif['name'],
            'email' => $notif['email']
        ];
    }

    // Mark notifications as notified
    $update_stmt = $conn->prepare("UPDATE notifications SET notified = 1 WHERE book_id = ? AND notified = 0");
    $update_stmt->bind_param("i", $book_id);
    $update_stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Book returned successfully',
        'data' => [
            'book_title' => $book['title'],
            'returned_by' => $book['borrower_name'],
            'return_date' => date('Y-m-d H:i:s'),
            'notified_users' => $notified_users
        ]
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => 'return_error'
    ]);
}
?> 