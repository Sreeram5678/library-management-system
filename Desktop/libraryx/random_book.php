<?php
require_once 'config.php';
header('Content-Type: application/json');
// Get up to 10 random available books
$sql = "SELECT b.id, b.title, b.author, c.name as category FROM books b JOIN categories c ON b.category_id = c.id WHERE b.status = 'available' ORDER BY RAND() LIMIT 10";
$result = $conn->query($sql);
$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}
if (count($books) > 0) {
    // Pick a random winner from the list
    $winner = $books[array_rand($books)];
    echo json_encode([
        'books' => $books,
        'winner' => $winner
    ]);
} else {
    echo json_encode(["error" => "No available books found."]);
} 