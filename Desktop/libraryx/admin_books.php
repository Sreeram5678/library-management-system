<?php
require_once 'config.php';
// Handle add/edit/delete for books and tag assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_book'])) {
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $isbn = trim($_POST['isbn']);
        $category_id = intval($_POST['category_id']);
        $desc = trim($_POST['description']);
        $copies = intval($_POST['copies']);
        if ($title && $author && $category_id && $copies > 0) {
            $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, category_id, description, copies, available_copies) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssissi", $title, $author, $isbn, $category_id, $desc, $copies, $copies);
            $stmt->execute();
        }
    } elseif (isset($_POST['delete_book'])) {
        $book_id = intval($_POST['book_id']);
        $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
    } elseif (isset($_POST['assign_tags'])) {
        $book_id = intval($_POST['book_id']);
        $tag_ids = isset($_POST['tag_ids']) ? $_POST['tag_ids'] : [];
        $conn->query("DELETE FROM book_tags WHERE book_id = $book_id");
        foreach ($tag_ids as $tag_id) {
            $stmt = $conn->prepare("INSERT INTO book_tags (book_id, tag_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $book_id, $tag_id);
            $stmt->execute();
        }
    }
}
// Get all books, categories, tags
$books = $conn->query("SELECT b.*, c.name as category FROM books b JOIN categories c ON b.category_id = c.id ORDER BY b.title");
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$tags = $conn->query("SELECT * FROM tags ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Books - LibraryX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
      body { background: linear-gradient(120deg, #e0e7ff 0%, #ffffff 100%); min-height: 100vh; }
      .sidebar { width: 260px; background: #fff; border-radius: 24px; box-shadow: 0 8px 32px rgba(76, 81, 255, 0.10); padding: 2rem 1rem; position: fixed; top: 40px; left: 40px; height: calc(100vh - 80px); display: flex; flex-direction: column; gap: 2rem; z-index: 10; }
      .sidebar .logo { font-size: 2rem; font-weight: 700; color: #4f46e5; margin-bottom: 2rem; text-align: center; }
      .sidebar a { display: block; padding: 0.8rem 1.2rem; border-radius: 12px; color: #4f46e5; font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem; text-decoration: none; transition: background 0.2s, color 0.2s; }
      .sidebar a.active, .sidebar a:hover { background: #e0e7ff; color: #1f2937; }
      @media (max-width: 900px) { .sidebar { position: static; width: 100%; height: auto; border-radius: 0; box-shadow: none; padding: 1rem 0.5rem; } }
    </style>
</head>
<body x-data="{ modalOpen: false, modalBook: {} }">
    <div class="sidebar">
        <div class="logo">LibraryX Admin</div>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_books.php" class="active">Books</a>
        <a href="admin_categories.php">Categories</a>
        <a href="admin_tags.php">Tags</a>
        <a href="admin_users.php">Users</a>
        <a href="index.php" style="margin-top:2rem;color:#e11d48;">Back to Site</a>
    </div>
    <div style="margin-left:300px;padding:2rem;">
        <h1 class="text-3xl font-bold text-[#4f46e5] mb-8">Books Management</h1>
        <div class="bg-white rounded-2xl shadow p-6 mb-8">
            <h3 class="text-xl font-bold text-[#1f2937] mb-4">Add Book</h3>
            <form class="flex flex-col gap-4 mb-4" method="post">
                <input type="text" name="title" placeholder="Title" required class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition" />
                <input type="text" name="author" placeholder="Author" required class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition" />
                <input type="text" name="isbn" placeholder="ISBN" class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition" />
                <select name="category_id" required class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition">
                    <option value="">Select Category</option>
                    <?php $categories->data_seek(0); while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <textarea name="description" placeholder="Description" class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition"></textarea>
                <input type="number" name="copies" placeholder="Copies" min="1" value="5" required class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition" />
                <button type="submit" name="add_book" class="px-6 py-2 rounded-lg bg-[#4f46e5] text-white font-bold shadow-lg hover:bg-[#0ea5e9] transition">Add Book</button>
            </form>
        </div>
        <div class="bg-white rounded-2xl shadow p-6 mb-8">
            <h3 class="text-xl font-bold text-[#1f2937] mb-4">Books List</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-[#e0e7ff]">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Title</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Author</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Category</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">ISBN</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Available/Total</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Tags</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $books->data_seek(0); while($book = $books->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-2 text-[#1f2937]">
                                <span class="cursor-pointer hover:text-[#4f46e5] underline" @click="modalBook = {
                                    title: '<?php echo addslashes(htmlspecialchars($book['title'])); ?>',
                                    author: '<?php echo addslashes(htmlspecialchars($book['author'])); ?>',
                                    category: '<?php echo addslashes(htmlspecialchars($book['category'])); ?>',
                                    description: '<?php echo addslashes(htmlspecialchars($book['description'])); ?>',
                                    available: '<?php echo $book['available_copies']; ?>',
                                    total: '<?php echo $book['copies']; ?>',
                                    tags: '<?php 
                                        $book_tags = $conn->query("SELECT t.name FROM book_tags bt JOIN tags t ON bt.tag_id = t.id WHERE bt.book_id = " . $book['id']);
                                        $tag_names = [];
                                        while($bt = $book_tags->fetch_assoc()) $tag_names[] = $bt['name'];
                                        echo addslashes(implode(", ", $tag_names));
                                    ?>',
                                    isbn: '<?php 
                                        $isbn = $book['isbn'];
                                        if (empty($isbn) || preg_match('/^https?:\/\//', $isbn)) {
                                            echo 'N/A';
                                        } else {
                                            echo addslashes(htmlspecialchars($isbn));
                                        }
                                    ?>'
                                }; modalOpen = true">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo htmlspecialchars($book['author']); ?> </td>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo htmlspecialchars($book['category']); ?> </td>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php 
                            $isbn = $book['isbn'];
                            if (empty($isbn) || preg_match('/^https?:\/\//', $isbn)) {
                                echo 'N/A';
                            } else {
                                echo htmlspecialchars($isbn);
                            }
                            ?> </td>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo $book['available_copies']; ?> / <?php echo $book['copies']; ?> </td>
                            <td class="px-4 py-2">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <select name="tag_ids[]" multiple size="2" class="px-2 py-1 rounded border border-gray-200 bg-gray-100 text-sm focus:ring-2 focus:ring-[#0ea5e9] outline-none transition min-w-[100px]">
                                        <?php $all_tags = $conn->query("SELECT * FROM tags ORDER BY name");
                                        $book_tags = $conn->query("SELECT tag_id FROM book_tags WHERE book_id = " . $book['id']);
                                        $book_tag_ids = [];
                                        while($bt = $book_tags->fetch_assoc()) $book_tag_ids[] = $bt['tag_id'];
                                        while($tag = $all_tags->fetch_assoc()): ?>
                                            <option value="<?php echo $tag['id']; ?>" <?php if(in_array($tag['id'], $book_tag_ids)) echo 'selected'; ?>><?php echo htmlspecialchars($tag['name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <button type="submit" name="assign_tags" class="px-3 py-1 rounded bg-[#10b981] text-white font-bold hover:bg-[#4f46e5] transition ml-2">Save</button>
                                </form>
                            </td>
                            <td class="px-4 py-2">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <button type="submit" name="delete_book" class="px-4 py-1 rounded bg-[#e11d48] text-white font-bold hover:bg-[#be123c] transition" onclick="return confirm('Delete this book?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div x-show="modalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-lg w-full relative">
            <button @click="modalOpen = false" class="absolute top-4 right-4 text-[#e11d48] text-2xl font-bold">&times;</button>
            <h2 class="text-2xl font-bold text-[#4f46e5] mb-2" x-text="modalBook.title"></h2>
            <p class="mb-1"><strong>Author:</strong> <span x-text="modalBook.author"></span></p>
            <p class="mb-1"><strong>Category:</strong> <span x-text="modalBook.category"></span></p>
            <p class="mb-1"><strong>Description:</strong> <span x-text="modalBook.description"></span></p>
            <p class="mb-1"><strong>Available/Total:</strong> <span x-text="modalBook.available"></span> / <span x-text="modalBook.total"></span></p>
            <p class="mb-1"><strong>Tags:</strong> <span x-text="modalBook.tags"></span></p>
            <p class="mb-1"><strong>ISBN:</strong> <span x-text="modalBook.isbn"></span></p>
        </div>
    </div>
</body>
</html> 