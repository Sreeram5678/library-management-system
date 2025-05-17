<?php
require_once 'config.php';
header('Content-Type: application/json');

$character = isset($_GET['character']) ? $_GET['character'] : '';
$map = [
  'sherlock' => ['Mystery'],
  'elon' => ['Technology', 'Biography', 'Science'],
  'chanakya' => ['Philosophy', 'History'],
  'hermione' => ['Fantasy', 'Adventure'],
  'curie' => ['Science', 'Biography'],
  'agatha' => ['Mystery'],
  'jobs' => ['Innovation', 'Biography', 'Technology'],
  'kalam' => ['Inspiration', 'Science'],
  'tony' => ['Technology', 'Adventure'],
  'austen' => ['Classic Romance', 'Romance'],
  'tolkien' => ['Fantasy', 'Adventure'],
  'hawking' => ['Science', 'Physics'],
  'angelou' => ['Poetry', 'Memoir'],
  'gandhi' => ['Philosophy', 'Peace', 'History'],
  'ada' => ['Mathematics', 'Computing', 'Science'],
  'picasso' => ['Art', 'Creativity'],
  'tesla' => ['Science', 'Inventor', 'Technology'],
  'rowling' => ['Fantasy'],
  'king' => ['Civil Rights', 'Biography'],
  'frida' => ['Art', 'Resilience'],
  'einstein' => ['Physics', 'Science'],
  'aristotle' => ['Philosophy'],
  'simone' => ['Philosophy', 'Feminism'],
  'neil' => ['Space', 'Exploration', 'Science'],
  'malala' => ['Education', 'Activism', 'Memoir'],
];
$genres = isset($map[$character]) ? $map[$character] : [];
if (!$genres) {
  echo json_encode([]);
  exit;
}
// Build SQL for genres
$in = implode(',', array_fill(0, count($genres), '?'));
$sql = "SELECT b.id, b.title, b.author, c.name as category FROM books b JOIN categories c ON b.category_id = c.id WHERE c.name IN ($in) ORDER BY RAND() LIMIT 8";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($genres)), ...$genres);
$stmt->execute();
$result = $stmt->get_result();
$books = [];
while ($row = $result->fetch_assoc()) {
  $books[] = $row;
}
echo json_encode($books); 