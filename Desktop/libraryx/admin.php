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
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-section { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 2rem; margin-bottom: 2rem; }
        .admin-section h3 { margin-bottom: 1rem; }
        .admin-form input, .admin-form select, .admin-form textarea { padding: 0.6rem 1rem; border-radius: 6px; border: 1px solid #ddd; font-size: 1.05rem; margin-bottom: 0.7rem; }
        .admin-form button { background: #3498db; color: #fff; border-radius: 6px; font-weight: 600; font-size: 1.05rem; padding: 0.6rem 1.5rem; border: none; margin-left: 0.5rem; }
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .admin-table th, .admin-table td { padding: 0.7rem 0.5rem; border-bottom: 1px solid #eee; text-align: left; }
        .admin-table th { background: #f8fafd; }
        .admin-table tr:last-child td { border-bottom: none; }
        .delete-btn { background: #e74c3c; color: #fff; border-radius: 6px; border: none; padding: 0.4rem 1rem; font-weight: 500; margin-left: 0.5rem; }
        .delete-btn:hover { background: #c0392b; }
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
                <a href="profile.php">Profile</a>
                <a href="admin.php" class="active">Admin</a>
            </div>
        </nav>
        <main>
            <div class="admin-section">
                <h3>Categories</h3>
                <form class="admin-form" method="post">
                    <input type="text" name="category_name" placeholder="New Category Name" required>
                    <button type="submit" name="add_category">Add Category</button>
                </form>
                <table class="admin-table">
                    <tr><th>Name</th><th>Action</th></tr>
                    <?php while($cat = $categories->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cat['name']); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                    <button type="submit" name="delete_category" class="delete-btn" onclick="return confirm('Delete this category?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <div class="admin-section">
                <h3>Add Book</h3>
                <form class="admin-form" method="post">
                    <input type="text" name="title" placeholder="Title" required>
                    <input type="text" name="author" placeholder="Author" required>
                    <input type="text" name="isbn" placeholder="ISBN">
                    <select name="category_id" required>
                        <option value="">Select Category</option>
                        <?php $categories2 = $conn->query("SELECT * FROM categories ORDER BY name"); while($cat = $categories2->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                    <textarea name="description" placeholder="Description"></textarea>
                    <button type="submit" name="add_book">Add Book</button>
                </form>
            </div>
            <div class="admin-section">
                <h3>Tags</h3>
                <form class="admin-form" method="post" autocomplete="off" style="display:flex; gap:0.7rem; align-items:center; max-width:400px;">
                    <input type="text" name="tag_name" placeholder="New Tag Name" required style="flex:1; min-width:0;">
                    <button type="submit" name="add_tag">Add Tag</button>
                </form>
                <table class="admin-table">
                    <tr><th>Name</th><th>Action</th></tr>
                    <?php $tags2 = $conn->query("SELECT * FROM tags ORDER BY name"); while($tag = $tags2->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tag['name']); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                                    <button type="submit" name="delete_tag" class="delete-btn" onclick="return confirm('Delete this tag?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <div class="admin-section">
                <h3>Books</h3>
                <table class="admin-table">
                    <tr><th>Title</th><th>Author</th><th>Category</th><th>ISBN</th><th>Tags</th><th>Action</th></tr>
                    <?php while($book = $books->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo htmlspecialchars($book['category']); ?></td>
                            <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <select name="tag_ids[]" multiple size="2" style="min-width:100px;">
                                        <?php $all_tags = $conn->query("SELECT * FROM tags ORDER BY name");
                                        $book_tags = $conn->query("SELECT tag_id FROM book_tags WHERE book_id = " . $book['id']);
                                        $book_tag_ids = [];
                                        while($bt = $book_tags->fetch_assoc()) $book_tag_ids[] = $bt['tag_id'];
                                        while($tag = $all_tags->fetch_assoc()): ?>
                                            <option value="<?php echo $tag['id']; ?>" <?php if(in_array($tag['id'], $book_tag_ids)) echo 'selected'; ?>><?php echo htmlspecialchars($tag['name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <button type="submit" name="assign_tags" class="btn" style="background:#27ae60; color:#fff; padding:0.3rem 1rem; margin-left:0.5rem;">Save</button>
                                </form>
                            </td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <button type="submit" name="delete_book" class="delete-btn" onclick="return confirm('Delete this book?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </main>
    </div>
</body>
</html> 