<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['book_id']) || !isset($data['borrower_name'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields',
        'error' => 'validation_error'
    ]);
    exit;
}

$book_id = $data['book_id'];
$borrower_name = trim($data['borrower_name']);

if (empty($borrower_name)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Borrower name is required',
        'error' => 'validation_error'
    ]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if book is available
    $stmt = $conn->prepare("SELECT available_copies, status, title FROM books WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();

    if (!$book) {
        throw new Exception('Book not found');
    }

    if ($book['available_copies'] <= 0) {
        throw new Exception('No copies available for borrowing');
    }

    // Decrement available_copies
    $stmt = $conn->prepare("UPDATE books SET available_copies = available_copies - 1, status = IF(available_copies - 1 = 0, 'borrowed', 'available') WHERE id = ? AND available_copies > 0");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();

    // Calculate due date (7 days from now)
    $due_date = date('Y-m-d H:i:s', strtotime('+7 days'));

    // Create borrow record with due_date
    $stmt = $conn->prepare("INSERT INTO borrowed_books (book_id, borrower_name, due_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $book_id, $borrower_name, $due_date);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Book borrowed successfully',
        'data' => [
            'book_title' => $book['title'],
            'borrower_name' => $borrower_name,
            'borrow_date' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => 'borrow_error'
    ]);
}
?> 