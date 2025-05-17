<?php
require_once 'config.php';
// Handle suspend/reactivate actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['suspend_user'])) {
        $user_id = intval($_POST['user_id']);
        $stmt = $conn->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    } elseif (isset($_POST['activate_user'])) {
        $user_id = intval($_POST['user_id']);
        $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
}
// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users - LibraryX</title>
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
        <h1 class="text-3xl font-bold text-[#4f46e5] mb-8">User Management</h1>
        <div class="bg-white rounded-2xl shadow p-6 mb-8">
            <h3 class="text-xl font-bold text-[#1f2937] mb-4">Users List</h3>
            <input type="text" id="userSearch" placeholder="Search users..." class="mb-4 px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition w-full max-w-md" />
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="usersTable">
                    <thead class="bg-[#e0e7ff]">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Username</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Full Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Email</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Joined</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-[#6b7280] uppercase tracking-wider">Last Login</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $users->data_seek(0); while($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-2 text-[#1f2937] user-search">
                                <a href="admin_user_details.php?id=<?php echo $user['id']; ?>" class="text-[#4f46e5] hover:underline font-semibold"><?php echo htmlspecialchars($user['username']); ?></a>
                            </td>
                            <td class="px-4 py-2 text-[#1f2937] user-search"> <?php echo htmlspecialchars($user['full_name']); ?> </td>
                            <td class="px-4 py-2 text-[#1f2937] user-search"> <?php echo htmlspecialchars($user['email']); ?> </td>
                            <td class="px-4 py-2 text-[#1f2937]">
                                <?php 
                                $status = strtolower($user['status']);
                                $badge = '';
                                if ($status === 'active') $badge = '<span class="inline-block px-3 py-1 rounded-full bg-[#d1fae5] text-[#10b981] font-bold">Active</span>';
                                elseif ($status === 'suspended') $badge = '<span class="inline-block px-3 py-1 rounded-full bg-[#fee2e2] text-[#e11d48] font-bold">Suspended</span>';
                                elseif ($status === 'inactive') $badge = '<span class="inline-block px-3 py-1 rounded-full bg-[#fef9c3] text-[#b45309] font-bold">Inactive</span>';
                                echo $badge;
                                ?>
                            </td>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo $user['created_at'] ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?> </td>
                            <td class="px-4 py-2 text-[#1f2937]"> <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'N/A'; ?> </td>
                            <td class="px-4 py-2">
                                <?php if ($user['status'] === 'active'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="suspend_user" class="px-4 py-1 rounded bg-[#e11d48] text-white font-bold hover:bg-[#be123c] transition" onclick="return confirm('Suspend this user?');">Suspend</button>
                                </form>
                                <?php elseif ($user['status'] === 'suspended'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="activate_user" class="px-4 py-1 rounded bg-[#10b981] text-white font-bold hover:bg-[#4f46e5] transition" onclick="return confirm('Reactivate this user?');">Reactivate</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (isset($_POST['suspend_user']) || isset($_POST['activate_user'])): ?>
        <div class="mb-4 px-6 py-3 rounded-lg bg-[#d1fae5] text-[#047857] font-semibold text-lg shadow">
            User status updated successfully.
        </div>
        <?php endif; ?>
    </div>
    <script>
    document.getElementById('userSearch').addEventListener('input', function() {
        const search = this.value.toLowerCase();
        const rows = document.querySelectorAll('#usersTable tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('.user-search');
            const match = Array.from(cells).some(cell => cell.textContent.toLowerCase().includes(search));
            row.style.display = match ? '' : 'none';
        });
    });
    </script>
</body>
</html> 