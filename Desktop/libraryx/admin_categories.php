<?php
require_once 'config.php';
// Handle add/delete for categories
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
    }
}
// Get all categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Categories - LibraryX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
      body { background: linear-gradient(120deg, #e0e7ff 0%, #ffffff 100%); min-height: 100vh; }
      .sidebar { width: 260px; background: #fff; border-radius: 24px; box-shadow: 0 8px 32px rgba(76, 81, 255, 0.10); padding: 2rem 1rem; position: fixed; top: 40px; left: 40px; height: calc(100vh - 80px); display: flex; flex-direction: column; gap: 2rem; z-index: 10; }
      .sidebar .logo { font-size: 2rem; font-weight: 700; color: #4f46e5; margin-bottom: 2rem; text-align: center; }
      .sidebar a { display: block; padding: 0.8rem 1.2rem; border-radius: 12px; color: #4f46e5; font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem; text-decoration: none; transition: background 0.2s, color 0.2s; }
      .sidebar a.active, .sidebar a:hover { background: #e0e7ff; color: #1f2937; }
      @media (max-width: 900px) { .sidebar { position: static; width: 100%; height: auto; border-radius: 0; box-shadow: none; padding: 1rem 0.5rem; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">LibraryX Admin</div>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_books.php">Books</a>
        <a href="admin_categories.php" class="active">Categories</a>
        <a href="admin_tags.php">Tags</a>
        <a href="admin_users.php">Users</a>
        <a href="index.php" style="margin-top:2rem;color:#e11d48;">Back to Site</a>
    </div>
    <div style="margin-left:300px;padding:2rem;">
        <h1 class="text-3xl font-bold text-[#4f46e5] mb-8">Category Management</h1>
        <div class="bg-white rounded-2xl shadow p-6 mb-8">
            <h3 class="text-xl font-bold text-[#1f2937] mb-4">Add Category</h3>
            <form class="flex flex-col sm:flex-row gap-4 mb-4" method="post">
                <input type="text" name="category_name" placeholder="New Category Name" required class="flex-1 px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition" />
                <button type="submit" name="add_category" class="px-6 py-2 rounded-lg bg-[#4f46e5] text-white font-bold shadow-lg hover:bg-[#0ea5e9] transition">Add Category</button>
            </form>
        </div>
        <div class="bg-white rounded-2xl shadow p-6 mb-8">
            <h3 class="text-xl font-bold text-[#1f2937] mb-4">Categories List</h3>
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
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo htmlspecialchars($cat['name']); ?> </td>
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
    </div>
</body>
</html> 