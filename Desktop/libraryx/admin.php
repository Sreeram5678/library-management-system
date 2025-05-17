<?php
require_once 'config.php';

// Handle add/edit/delete for categories
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $cat_name = trim($_POST['category_name']);
        if ($cat_name !== '') {
            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $cat_name);
            $stmt->execute();
        }
    } elseif (isset($_POST['delete_category'])) {
        $cat_id = intval($_POST['category_id']);
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $cat_id);
        $stmt->execute();
    } elseif (isset($_POST['add_book'])) {
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $isbn = trim($_POST['isbn']);
        $category_id = intval($_POST['category_id']);
        $desc = trim($_POST['description']);
        if ($title && $author && $category_id) {
            $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, category_id, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssis", $title, $author, $isbn, $category_id, $desc);
            $stmt->execute();
        }
    } elseif (isset($_POST['delete_book'])) {
        $book_id = intval($_POST['book_id']);
        $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
    } elseif (isset($_POST['add_tag'])) {
        $tag_name = trim($_POST['tag_name']);
        if ($tag_name !== '') {
            $stmt = $conn->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
            $stmt->bind_param("s", $tag_name);
            $stmt->execute();
        }
    } elseif (isset($_POST['assign_tags'])) {
        $book_id = intval($_POST['book_id']);
        $tag_ids = isset($_POST['tag_ids']) ? $_POST['tag_ids'] : [];
        // Remove all current tags for this book
        $conn->query("DELETE FROM book_tags WHERE book_id = $book_id");
        // Assign selected tags
        foreach ($tag_ids as $tag_id) {
            $stmt = $conn->prepare("INSERT INTO book_tags (book_id, tag_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $book_id, $tag_id);
            $stmt->execute();
        }
    } elseif (isset($_POST['delete_tag'])) {
        $tag_id = intval($_POST['tag_id']);
        $stmt = $conn->prepare("DELETE FROM tags WHERE id = ?");
        $stmt->bind_param("i", $tag_id);
        $stmt->execute();
    }
}

// Get all categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
// Get all books
$books = $conn->query("SELECT b.*, c.name as category FROM books b JOIN categories c ON b.category_id = c.id ORDER BY b.title");
// Get all tags
$tags = $conn->query("SELECT * FROM tags ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - LibraryX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
      body {
        background: linear-gradient(120deg, #e0e7ff 0%, #ffffff 100%);
        min-height: 100vh;
        position: relative;
        overflow-x: hidden;
      }
      .sidebar {
        width: 260px;
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 8px 32px rgba(76, 81, 255, 0.10);
        padding: 2rem 1rem;
        position: fixed;
        top: 40px;
        left: 40px;
        height: calc(100vh - 80px);
        display: flex;
        flex-direction: column;
        gap: 2rem;
        z-index: 10;
      }
      .sidebar .logo {
        font-size: 2rem;
        font-weight: 700;
        color: #4f46e5;
        margin-bottom: 2rem;
        text-align: center;
      }
      .sidebar a {
        display: block;
        padding: 0.8rem 1.2rem;
        border-radius: 12px;
        color: #4f46e5;
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        text-decoration: none;
        transition: background 0.2s, color 0.2s;
      }
      .sidebar a.active, .sidebar a:hover {
        background: #e0e7ff;
        color: #1f2937;
      }
      @media (max-width: 900px) {
        .sidebar { position: static; width: 100%; height: auto; border-radius: 0; box-shadow: none; padding: 1rem 0.5rem; }
      }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">LibraryX Admin</div>
        <a href="admin_dashboard.php" class="active">Dashboard</a>
        <a href="admin_books.php">Books</a>
        <a href="admin_categories.php">Categories</a>
        <a href="admin_tags.php">Tags</a>
        <a href="admin_users.php">Users</a>
        <a href="index.php" style="margin-top:2rem;color:#e11d48;">Back to Site</a>
    </div>
    <div style="margin-left:300px;padding:2rem;">
        <h1 class="text-3xl font-bold text-[#4f46e5] mb-6">Welcome to the Admin Panel</h1>
        <p class="text-lg text-[#6b7280]">Select a section from the sidebar to manage the library.</p>
    </div>
</body>
</html> 