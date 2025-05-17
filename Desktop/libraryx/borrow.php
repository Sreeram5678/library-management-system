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
    $stmt = $conn->prepare("SELECT status, title FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();

    if (!$book) {
        throw new Exception('Book not found');
    }

    if ($book['status'] !== 'available') {
        throw new Exception('Book is not available for borrowing');
    }

    // Update book status
    $stmt = $conn->prepare("UPDATE books SET status = 'borrowed' WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();

    // Create borrow record
    $stmt = $conn->prepare("INSERT INTO borrowed_books (book_id, borrower_name) VALUES (?, ?)");
    $stmt->bind_param("is", $book_id, $borrower_name);
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