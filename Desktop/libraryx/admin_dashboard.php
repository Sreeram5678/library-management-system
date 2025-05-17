<?php
require_once 'config.php';
// Quick stats
$total_books = $conn->query("SELECT COUNT(*) as cnt FROM books")->fetch_assoc()['cnt'];
$total_users = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
$total_categories = $conn->query("SELECT COUNT(*) as cnt FROM categories")->fetch_assoc()['cnt'];
$total_tags = $conn->query("SELECT COUNT(*) as cnt FROM tags")->fetch_assoc()['cnt'];
$total_borrowed = $conn->query("SELECT COUNT(*) as cnt FROM borrowed_books WHERE return_date IS NULL")->fetch_assoc()['cnt'];
$overdue_books = $conn->query("SELECT COUNT(*) as cnt FROM borrowed_books WHERE return_date IS NULL AND due_date < CURRENT_DATE")->fetch_assoc()['cnt'];
// Recent activity
$recent = $conn->query("SELECT b.title, bb.borrower_name, bb.borrow_date, bb.return_date FROM borrowed_books bb JOIN books b ON bb.book_id = b.id ORDER BY bb.borrow_date DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LibraryX</title>
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
        <a href="admin_dashboard.php" class="active">Dashboard</a>
        <a href="admin_books.php">Books</a>
        <a href="admin_categories.php">Categories</a>
        <a href="admin_tags.php">Tags</a>
        <a href="admin_users.php">Users</a>
        <a href="index.php" style="margin-top:2rem;color:#e11d48;">Back to Site</a>
    </div>
    <div style="margin-left:300px;padding:2rem;">
        <h1 class="text-3xl font-bold text-[#4f46e5] mb-8">Admin Dashboard</h1>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">
            <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center">
                <div class="text-2xl font-bold text-[#4f46e5]">📚 <?php echo $total_books; ?></div>
                <div class="text-[#6b7280] mt-2">Books</div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center">
                <div class="text-2xl font-bold text-[#0ea5e9]">👤 <?php echo $total_users; ?></div>
                <div class="text-[#6b7280] mt-2">Users</div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center">
                <div class="text-2xl font-bold text-[#10b981]">📁 <?php echo $total_categories; ?></div>
                <div class="text-[#6b7280] mt-2">Categories</div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center">
                <div class="text-2xl font-bold text-[#f59e42]">🏷️ <?php echo $total_tags; ?></div>
                <div class="text-[#6b7280] mt-2">Tags</div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center">
                <div class="text-2xl font-bold text-[#e11d48]">📖 <?php echo $total_borrowed; ?></div>
                <div class="text-[#6b7280] mt-2">Currently Borrowed</div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center">
                <div class="text-2xl font-bold text-[#be185d]">⏰ <?php echo $overdue_books; ?></div>
                <div class="text-[#6b7280] mt-2">Overdue Books</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow p-6 mb-8">
            <h3 class="text-xl font-bold text-[#1f2937] mb-4">Recent Activity</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-[#e0e7ff]">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Title</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Borrower</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Borrowed On</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Returned</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while($row = $recent->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo htmlspecialchars($row['title']); ?> </td>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo htmlspecialchars($row['borrower_name']); ?> </td>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo date('M d, Y', strtotime($row['borrow_date'])); ?> </td>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo $row['return_date'] ? date('M d, Y', strtotime($row['return_date'])) : '<span style="color:#e53e3e;">Not returned</span>'; ?> </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 