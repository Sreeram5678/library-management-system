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
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
      body {
        background: linear-gradient(120deg, #e0e7ff 0%, #ffffff 100%);
        min-height: 100vh;
        position: relative;
        overflow-x: hidden;
      }
      .floating-shape {
        position: absolute;
        z-index: 0;
        opacity: 0.10;
        filter: blur(12px);
        pointer-events: none;
      }
      .floating-shape1 { top: 5%; left: 10%; width: 220px; height: 220px; background: #4f46e5; border-radius: 50%; }
      .floating-shape2 { bottom: 10%; right: 8%; width: 180px; height: 180px; background: #0ea5e9; border-radius: 50%; }
      .floating-shape3 { top: 60%; left: 60%; width: 120px; height: 120px; background: #a5b4fc; border-radius: 50%; }
    </style>
</head>
<body class="relative font-sans min-h-screen transition-colors duration-300">
    <div class="floating-shape floating-shape1"></div>
    <div class="floating-shape floating-shape2"></div>
    <div class="floating-shape floating-shape3"></div>
    <div class="max-w-7xl mx-auto px-4 py-6 relative z-10">
        <nav class="flex items-center justify-between px-6 py-4 rounded-2xl shadow-glass bg-white/60 backdrop-blur-md sticky top-4 z-30 mb-8 border border-white/30">
            <div class="flex items-center gap-4">
                <img src='https://api.dicebear.com/7.x/identicon/svg?seed=LibraryX' alt='avatar' class='w-12 h-12 rounded-full shadow border-2 border-primary/40'>
                <div>
                  <h1 class="text-3xl font-extrabold text-[#4f46e5] tracking-tight font-poppins">LibraryX</h1>
                  <div class="text-xs text-gray-500 font-semibold mt-1">Welcome, Admin!</div>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <a href="index.php" class="text-lg font-medium text-[#1f2937] hover:text-[#4f46e5]">Home</a>
                <a href="borrowed.php" class="text-lg font-medium text-[#1f2937] hover:text-[#4f46e5]">Borrowed Books</a>
                <a href="history.php" class="text-lg font-medium text-[#1f2937] hover:text-[#4f46e5]">Borrowing History</a>
                <a href="wishlist.php" class="text-lg font-medium text-[#1f2937] hover:text-[#4f46e5]">Wishlist</a>
                <a href="profile.php" class="text-lg font-medium text-[#1f2937] hover:text-[#4f46e5]">Profile</a>
                <a href="characters.php" class="text-lg font-medium text-gray-700 hover:text-primary">Characters</a>
                <a href="admin.php" class="text-lg font-semibold text-[#4f46e5] border-b-2 border-[#4f46e5] pb-1">Admin</a>
            </div>
        </nav>
        <!-- Dashboard Quick Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
          <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center">
            <div class="text-2xl font-bold text-[#4f46e5]">📚 <?php echo $books->num_rows; ?></div>
            <div class="text-[#6b7280] mt-2">Books</div>
          </div>
          <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center">
            <div class="text-2xl font-bold text-[#0ea5e9]">🏷️ <?php echo $tags->num_rows; ?></div>
            <div class="text-[#6b7280] mt-2">Tags</div>
          </div>
          <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center">
            <div class="text-2xl font-bold text-[#10b981]">📁 <?php echo $categories->num_rows; ?></div>
            <div class="text-[#6b7280] mt-2">Categories</div>
          </div>
        </div>
        <main>
            <!-- Categories Section -->
            <div class="bg-white rounded-2xl shadow p-6 mb-8">
                <h3 class="text-xl font-bold text-[#1f2937] mb-4">Categories</h3>
                <form class="flex flex-col sm:flex-row gap-4 mb-4" method="post">
                    <input type="text" name="category_name" placeholder="New Category Name" required class="flex-1 px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition" />
                    <button type="submit" name="add_category" class="px-6 py-2 rounded-lg bg-[#4f46e5] text-white font-bold shadow-lg hover:bg-[#0ea5e9] transition">Add Category</button>
                </form>
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-[#e0e7ff]">
                      <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2"></th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <?php $categories->data_seek(0); while($cat = $categories->fetch_assoc()): ?>
                        <tr>
                          <td class="px-4 py-2 text-[#1f2937]"><?php echo htmlspecialchars($cat['name']); ?></td>
                          <td class="px-4 py-2">
                            <form method="post" style="display:inline;">
                              <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                              <button type="submit" name="delete_category" class="px-4 py-1 rounded bg-[#e11d48] text-white font-bold hover:bg-[#be123c] transition" onclick="return confirm('Delete this category?');">Delete</button>
                            </form>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
            </div>
            <!-- Add Book Section -->
            <div class="bg-white rounded-2xl shadow p-6 mb-8">
                <h3 class="text-xl font-bold text-[#1f2937] mb-4">Add Book</h3>
                <form class="flex flex-col gap-4 mb-4" method="post">
                    <input type="text" name="title" placeholder="Title" required class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition" />
                    <input type="text" name="author" placeholder="Author" required class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition" />
                    <input type="text" name="isbn" placeholder="ISBN" class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition" />
                    <select name="category_id" required class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition">
                        <option value="">Select Category</option>
                        <?php $categories2 = $conn->query("SELECT * FROM categories ORDER BY name"); while($cat = $categories2->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                    <textarea name="description" placeholder="Description" class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition"></textarea>
                    <button type="submit" name="add_book" class="px-6 py-2 rounded-lg bg-[#4f46e5] text-white font-bold shadow-lg hover:bg-[#0ea5e9] transition">Add Book</button>
                </form>
            </div>
            <!-- Tags Section -->
            <div class="bg-white rounded-2xl shadow p-6 mb-8">
                <h3 class="text-xl font-bold text-[#1f2937] mb-4">Tags</h3>
                <form class="flex flex-col sm:flex-row gap-4 mb-4" method="post" autocomplete="off">
                    <input type="text" name="tag_name" placeholder="New Tag Name" required class="flex-1 px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition" />
                    <button type="submit" name="add_tag" class="px-6 py-2 rounded-lg bg-[#0ea5e9] text-white font-bold shadow-lg hover:bg-[#4f46e5] transition">Add Tag</button>
                </form>
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-[#e0e7ff]">
                      <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2"></th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <?php $tags->data_seek(0); while($tag = $tags->fetch_assoc()): ?>
                        <tr>
                          <td class="px-4 py-2 text-[#1f2937]"><?php echo htmlspecialchars($tag['name']); ?></td>
                          <td class="px-4 py-2">
                            <form method="post" style="display:inline;">
                              <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                              <button type="submit" name="delete_tag" class="px-4 py-1 rounded bg-[#e11d48] text-white font-bold hover:bg-[#be123c] transition" onclick="return confirm('Delete this tag?');">Delete</button>
                            </form>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
            </div>
            <!-- Books Section -->
            <div class="bg-white rounded-2xl shadow p-6 mb-8">
                <h3 class="text-xl font-bold text-[#1f2937] mb-4">Books</h3>
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-[#e0e7ff]">
                      <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Title</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Author</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Category</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">ISBN</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Tags</th>
                        <th class="px-4 py-2"></th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <?php $books->data_seek(0); while($book = $books->fetch_assoc()): ?>
                        <tr>
                          <td class="px-4 py-2 text-[#1f2937]"><?php echo htmlspecialchars($book['title']); ?></td>
                          <td class="px-4 py-2 text-[#1f2937]"><?php echo htmlspecialchars($book['author']); ?></td>
                          <td class="px-4 py-2 text-[#1f2937]"><?php echo htmlspecialchars($book['category']); ?></td>
                          <td class="px-4 py-2 text-[#1f2937]"><?php echo htmlspecialchars($book['isbn']); ?></td>
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
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html> 