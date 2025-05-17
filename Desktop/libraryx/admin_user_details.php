<?php
require_once 'config.php';
if (!isset($_GET['id'])) {
    die('User ID not specified.');
}
$user_id = intval($_GET['id']);
// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) die('User not found.');
// Get borrowing history
$history = $conn->prepare("SELECT b.title, bb.borrow_date, bb.due_date, bb.return_date FROM borrowed_books bb JOIN books b ON bb.book_id = b.id WHERE bb.borrower_name = ? ORDER BY bb.borrow_date DESC");
$history->bind_param("s", $user['full_name']);
$history->execute();
$borrows = $history->get_result();
// Handle password reset
$reset_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $new_password = trim($_POST['new_password']);
    if (empty($new_password)) {
        // Generate a random password
        $new_password = bin2hex(random_bytes(4)); // 8 chars
    }
    if (strlen($new_password) < 8) {
        $reset_msg = 'Password must be at least 8 characters.';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        if ($stmt->execute()) {
            $reset_msg = 'Password reset successfully! New password: <span class=\'font-mono bg-[#e0e7ff] px-2 rounded\'>' . htmlspecialchars($new_password) . '</span>';
        } else {
            $reset_msg = 'Failed to reset password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - LibraryX Admin</title>
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
        <a href="admin_categories.php">Categories</a>
        <a href="admin_tags.php">Tags</a>
        <a href="admin_users.php" class="active">Users</a>
        <a href="index.php" style="margin-top:2rem;color:#e11d48;">Back to Site</a>
    </div>
    <div style="margin-left:300px;padding:2rem;">
        <a href="admin_users.php" class="text-[#4f46e5] hover:underline mb-4 inline-block">&larr; Back to Users</a>
        <h1 class="text-3xl font-bold text-[#4f46e5] mb-4">User Details</h1>
        <div class="bg-white rounded-2xl shadow p-6 mb-8">
            <h3 class="text-xl font-bold text-[#1f2937] mb-2">User Info</h3>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Status:</strong> <?php echo ucfirst($user['status']); ?></p>
            <p><strong>Joined:</strong> <?php echo $user['created_at'] ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?></p>
            <p><strong>Last Login:</strong> <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'N/A'; ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow p-6 mb-8">
            <h3 class="text-xl font-bold text-[#1f2937] mb-4">Borrowing History</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-[#e0e7ff]">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Title</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Borrowed</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Due</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Returned</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Overdue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while($row = $borrows->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo htmlspecialchars($row['title']); ?> </td>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo $row['borrow_date'] ? date('M d, Y', strtotime($row['borrow_date'])) : 'N/A'; ?> </td>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo $row['due_date'] ? date('M d, Y', strtotime($row['due_date'])) : 'N/A'; ?> </td>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo $row['return_date'] ? date('M d, Y', strtotime($row['return_date'])) : '<span style=\'color:#e53e3e;\'>Not returned</span>'; ?> </td>
                            <td class="px-4 py-2 text-[#1f2937]">
                                <?php 
                                if (!$row['return_date'] && $row['due_date'] && strtotime($row['due_date']) < time()) {
                                    echo '<span class="inline-block px-3 py-1 rounded-full bg-[#fee2e2] text-[#e11d48] font-bold">Overdue</span>';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow p-6 mb-8">
            <h3 class="text-xl font-bold text-[#1f2937] mb-4">Reset Password</h3>
            <?php if ($reset_msg): ?>
                <div class="mb-4 px-6 py-3 rounded-lg bg-[#d1fae5] text-[#047857] font-semibold text-lg shadow"><?php echo $reset_msg; ?></div>
            <?php endif; ?>
            <form method="post" class="flex flex-col sm:flex-row gap-4 items-center">
                <input type="text" name="new_password" placeholder="Enter new password or leave blank for random" class="flex-1 px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition" />
                <button type="submit" name="reset_password" class="px-6 py-2 rounded-lg bg-[#4f46e5] text-white font-bold shadow-lg hover:bg-[#0ea5e9] transition">Reset Password</button>
            </form>
        </div>
    </div>
</body>
</html> 