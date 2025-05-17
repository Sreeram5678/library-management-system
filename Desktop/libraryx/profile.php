<?php
session_start();
require_once 'config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$user_sql = "SELECT username, email, full_name, created_at, last_login FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Handle profile update
$update_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_full_name = trim($_POST['full_name']);
    $new_password = $_POST['password'];
    $update_fields = [];
    $params = [];
    $types = '';

    if (!empty($new_full_name) && $new_full_name !== $user['full_name']) {
        $update_fields[] = 'full_name = ?';
        $params[] = $new_full_name;
        $types .= 's';
    }
    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            $update_msg = 'Password must be at least 8 characters.';
        } else {
            $update_fields[] = 'password = ?';
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            $types .= 's';
        }
    }
    if ($update_fields && !$update_msg) {
        $params[] = $user_id;
        $types .= 'i';
        $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $update_msg = 'Profile updated successfully!';
            // Refresh user info
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user = $user_result->fetch_assoc();
            $_SESSION['full_name'] = $user['full_name'];
        } else {
            $update_msg = 'Failed to update profile.';
        }
    }
}

// Fetch borrowing history (all)
$borrow_sql = "SELECT b.title, b.author, bb.borrow_date, bb.due_date, bb.return_date
               FROM borrowed_books bb
               JOIN books b ON bb.book_id = b.id
               WHERE bb.borrower_name = ? AND bb.return_date IS NULL AND bb.due_date IS NOT NULL
               ORDER BY bb.borrow_date DESC";
$borrow_stmt = $conn->prepare($borrow_sql);
$borrow_stmt->bind_param("s", $user['full_name']);
$borrow_stmt->execute();
$borrow_result = $borrow_stmt->get_result();
$borrows = [];
while ($row = $borrow_result->fetch_assoc()) $borrows[] = $row;

// Dashboard stats
$total_borrowed = count($borrows);
$current_borrowed = 0;
$next_due = null;
$books_returned_this_month = 0;
$last_borrowed = null;
$now = new DateTime();
foreach ($borrows as $b) {
    if (!$b['return_date']) {
        $current_borrowed++;
        if ($b['due_date'] && (!$next_due || $b['due_date'] < $next_due)) {
            $next_due = $b['due_date'];
        }
    }
    if ($b['return_date']) {
        $retMonth = date('Y-m', strtotime($b['return_date']));
        if ($retMonth == $now->format('Y-m')) $books_returned_this_month++;
    }
    if (!$last_borrowed || $b['borrow_date'] > $last_borrowed) $last_borrowed = $b['borrow_date'];
}

// Reading streak tracker (consecutive days with borrow/return)
$dates = [];
foreach ($borrows as $b) {
    $dates[date('Y-m-d', strtotime($b['borrow_date']))] = true;
    if ($b['return_date']) $dates[date('Y-m-d', strtotime($b['return_date']))] = true;
}
$streak = 0;
$today = new DateTime();
while (isset($dates[$today->format('Y-m-d')])) {
    $streak++;
    $today->modify('-1 day');
}

// Badges
$badges = [];
if ($total_borrowed > 0) $badges[] = ['🎉','First Borrow'];
if ($total_borrowed >= 10) $badges[] = ['🏅','10+ Books Borrowed'];
$late = false;
foreach ($borrows as $b) {
    if ($b['return_date'] && $b['due_date'] && strtotime($b['return_date']) > strtotime($b['due_date'])) $late = true;
}
if (!$late && $total_borrowed > 0) $badges[] = ['🕒','No Late Returns'];

// Session activity log
$last_login = isset($user['last_login']) && $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'N/A';
$last_borrowed_disp = $last_borrowed ? date('M d, Y H:i', strtotime($last_borrowed)) : 'N/A';

// Current borrows for progress bars
$current_borrows = array_filter($borrows, function($b){ return !$b['return_date']; });

// Fetch wishlist
$wishlist_sql = "SELECT b.title, b.author, w.added_date
                 FROM wishlist w
                 JOIN books b ON w.book_id = b.id
                 WHERE w.borrower_name = ?
                 ORDER BY w.added_date DESC";
$wishlist_stmt = $conn->prepare($wishlist_sql);
$wishlist_stmt->bind_param("s", $user['full_name']);
$wishlist_stmt->execute();
$wishlist_result = $wishlist_stmt->get_result();

// Fetch review history
$review_sql = "SELECT b.title, br.rating, br.review, br.review_date
               FROM book_reviews br
               JOIN books b ON br.book_id = b.id
               WHERE br.reviewer_name = ?
               ORDER BY br.review_date DESC";
$review_stmt = $conn->prepare($review_sql);
$review_stmt->bind_param("s", $user['full_name']);
$review_stmt->execute();
$review_result = $review_stmt->get_result();

// Helper for avatar initials
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $w) {
        if ($w) $initials .= strtoupper($w[0]);
    }
    return substr($initials, 0, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - LibraryX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0;
            margin: 0;
        }
        .profile-card {
            max-width: 700px;
            margin: 48px auto 32px auto;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(76, 81, 255, 0.10);
            padding: 2.5rem 2rem 2rem 2rem;
            position: relative;
            z-index: 2;
        }
        .back-arrow {
            position: absolute;
            top: 24px;
            left: 24px;
            font-size: 1.7rem;
            color: #667eea;
            background: #f8fafc;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px #667eea11;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .back-arrow:hover {
            background: #667eea;
            color: #fff;
        }
        .profile-avatar-large {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 2.8rem;
            font-weight: 700;
            margin: -80px auto 0 auto;
            box-shadow: 0 4px 24px #667eea22;
            border: 6px solid #fff;
        }
        .logout-btn {
            position: absolute;
            top: 24px;
            right: 24px;
            background: linear-gradient(135deg, #ff5858 0%, #f09819 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 22px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px #ff585822;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .logout-btn:hover {
            background: linear-gradient(135deg, #f09819 0%, #ff5858 100%);
            box-shadow: 0 4px 16px #ff585844;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .profile-header h2 {
            margin: 0.5rem 0 0.2rem 0;
            color: #2d3748;
            font-size: 2rem;
            font-weight: 700;
        }
        .profile-header p {
            color: #4a5568;
            margin: 0.2rem 0;
        }
        .dashboard {
            display: flex;
            flex-wrap: wrap;
            gap: 1.2rem;
            justify-content: center;
            margin-bottom: 2.2rem;
        }
        .widget {
            background: linear-gradient(135deg, #f8fafc 60%, #e0e7ff 100%);
            border-radius: 16px;
            box-shadow: 0 2px 8px #667eea11;
            padding: 1.2rem 1.5rem;
            min-width: 140px;
            flex: 1 1 140px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .widget .widget-icon {
            font-size: 2rem;
            margin-bottom: 0.3rem;
        }
        .widget .widget-label {
            color: #667eea;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .widget .widget-value {
            font-size: 1.7rem;
            font-weight: 700;
            color: #2d3748;
        }
        .streak {
            background: linear-gradient(135deg, #e0e7ff 60%, #f8fafc 100%);
            border-radius: 16px;
            box-shadow: 0 2px 8px #667eea11;
            padding: 1.2rem 1.5rem;
            text-align: center;
            margin-bottom: 2.2rem;
        }
        .streak .streak-label {
            color: #764ba2;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .streak .streak-value {
            font-size: 1.7rem;
            font-weight: 700;
            color: #2d3748;
        }
        .badges {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2.2rem;
            justify-content: center;
        }
        .badge {
            background: linear-gradient(135deg, #fffbe6 60%, #ffe0e0 100%);
            border-radius: 12px;
            box-shadow: 0 2px 8px #f0981911;
            padding: 0.7rem 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: #f09819;
        }
        .activity-log {
            background: linear-gradient(135deg, #f8fafc 60%, #e0e7ff 100%);
            border-radius: 16px;
            box-shadow: 0 2px 8px #667eea11;
            padding: 1.2rem 1.5rem;
            margin-bottom: 2.2rem;
            text-align: center;
        }
        .activity-log .activity-label {
            color: #667eea;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .activity-log .activity-value {
            font-size: 1.1rem;
            color: #2d3748;
        }
        .profile-edit-form {
            margin: 2rem 0 2.5rem 0;
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem 1rem;
            box-shadow: 0 2px 8px #667eea11;
        }
        .profile-edit-form input {
            margin-bottom: 1rem;
        }
        .section-title {
            color: #667eea;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-title i {
            font-size: 1.1em;
        }
        .profile-section {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 8px #667eea11;
            padding: 1.5rem 1rem;
            margin-bottom: 2rem;
        }
        .progress-bar-container {
            background: #e0e7ff;
            border-radius: 8px;
            height: 18px;
            width: 100%;
            margin-top: 0.5rem;
            margin-bottom: 1.2rem;
            overflow: hidden;
        }
        .progress-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            border-radius: 8px;
            transition: width 0.5s;
        }
        .success { background: #f0fff4; color: #2f855a; padding: 10px; border-radius: 6px; margin-bottom: 1rem; border: 1px solid #9ae6b4; }
        .error { background: #fff5f5; color: #c53030; padding: 10px; border-radius: 6px; margin-bottom: 1rem; border: 1px solid #feb2b2; }
        @media (max-width: 900px) {
            .profile-card { max-width: 98vw; }
        }
        @media (max-width: 700px) {
            .profile-card { padding: 1.2rem 0.5rem; }
            .profile-section, .profile-edit-form, .dashboard, .streak, .badges, .activity-log { padding: 1rem 0.5rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);">
        <div class="profile-card">
            <a href="index.php" class="back-arrow" title="Back to Home"><i class="fas fa-arrow-left"></i></a>
            <form action="logout.php" method="post">
                <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </form>
            <div class="profile-avatar-large"><?php echo getInitials($user['full_name'] ?? ''); ?></div>
            <div class="profile-header">
                <h2><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></h2>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username'] ?? ''); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                <p><strong>Joined:</strong> <?php echo isset($user['created_at']) && $user['created_at'] ? date('F j, Y', strtotime($user['created_at'])) : 'N/A'; ?></p>
            </div>
            <!-- Dashboard Widgets -->
            <div class="dashboard">
                <div class="widget">
                    <span class="widget-icon">📚</span>
                    <span class="widget-label">Total Borrowed</span>
                    <span class="widget-value"><?php echo $total_borrowed; ?></span>
                </div>
                <div class="widget">
                    <span class="widget-icon">📖</span>
                    <span class="widget-label">Current Borrowed</span>
                    <span class="widget-value"><?php echo $current_borrowed; ?></span>
                </div>
                <div class="widget">
                    <span class="widget-icon">⏰</span>
                    <span class="widget-label">Next Due</span>
                    <span class="widget-value"><?php echo $next_due ? date('M d, Y', strtotime($next_due)) : 'N/A'; ?></span>
                </div>
                <div class="widget">
                    <span class="widget-icon">✅</span>
                    <span class="widget-label">Returned This Month</span>
                    <span class="widget-value"><?php echo $books_returned_this_month; ?></span>
                </div>
            </div>
            <!-- Reading Streak -->
            <div class="streak">
                <span class="streak-label">🔥 Reading Streak</span>
                <div class="streak-value"><?php echo $streak; ?> day<?php echo $streak==1?'':'s'; ?></div>
            </div>
            <!-- Badges -->
            <div class="badges">
                <?php foreach ($badges as $badge): ?>
                    <div class="badge"><?php echo $badge[0]; ?> <?php echo $badge[1]; ?></div>
                <?php endforeach; ?>
                                </div>
            <!-- Activity Log -->
            <div class="activity-log">
                <div class="activity-label"><i class="fas fa-sign-in-alt"></i> Last Login:</div>
                <div class="activity-value"><?php echo $last_login; ?></div>
                <div class="activity-label" style="margin-top:0.7rem;"><i class="fas fa-book"></i> Last Book Borrowed:</div>
                <div class="activity-value"><?php echo $last_borrowed_disp; ?></div>
                        </div>
            <!-- Profile Edit Form -->
            <form class="profile-edit-form" method="POST" action="">
                <h3 class="section-title"><i class="fas fa-edit"></i> Edit Profile</h3>
                <?php if ($update_msg): ?>
                    <div class="<?php echo strpos($update_msg, 'success') !== false ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($update_msg); ?></div>
                    <?php endif; ?>
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label for="password">New Password <span style="color:#888;font-size:0.9em;">(leave blank to keep current)</span></label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="New password">
                </div>
                <button type="submit" name="update_profile" class="btn"><i class="fas fa-save"></i> Save Changes</button>
            </form>
            <!-- Current Borrowed Books Progress Bars -->
            <?php if (count($current_borrows)): ?>
            <div class="profile-section">
                <h3 class="section-title"><i class="fas fa-hourglass-half"></i> Current Borrow Progress</h3>
                <?php foreach ($current_borrows as $b): 
                    $borrowed = strtotime($b['borrow_date']);
                    $due = strtotime($b['due_date']);
                    $now = time();
                    $progress = $due > $borrowed ? min(100, max(0, round(100*($now-$borrowed)/($due-$borrowed)))) : 0;
                ?>
                <div style="margin-bottom:0.5rem;"><strong><?php echo htmlspecialchars($b['title']); ?></strong> <span style="color:#667eea;font-size:0.95em;">(Due: <?php echo date('M d, Y', strtotime($b['due_date'])); ?>)</span></div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width:<?php echo $progress; ?>%"></div>
                </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <!-- Borrowing History -->
            <div class="profile-section">
                <h3 class="section-title"><i class="fas fa-book"></i> Borrowing History</h3>
                <table class="history-table">
                    <tr><th>Title</th><th>Author</th><th>Borrowed</th><th>Due</th><th>Returned</th></tr>
                    <?php foreach ($borrows as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['author']); ?></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['borrow_date']))); ?></td>
                            <td><?php echo $row['due_date'] ? htmlspecialchars(date('M d, Y', strtotime($row['due_date']))) : '-'; ?></td>
                            <td><?php echo $row['return_date'] ? htmlspecialchars(date('M d, Y', strtotime($row['return_date']))) : '<span style="color:#e53e3e;">Not returned</span>'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <!-- Wishlist -->
            <div class="profile-section">
                <h3 class="section-title"><i class="fas fa-heart"></i> Wishlist</h3>
                <table class="wishlist-table">
                    <tr><th>Title</th><th>Author</th><th>Added</th></tr>
                    <?php while ($row = $wishlist_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['author']); ?></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['added_date']))); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <!-- Reviews -->
            <div class="profile-section">
                <h3 class="section-title"><i class="fas fa-star"></i> My Reviews</h3>
                <table class="review-table">
                    <tr><th>Title</th><th>Rating</th><th>Review</th><th>Date</th></tr>
                    <?php while ($row = $review_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['rating']); ?>/5</td>
                            <td><?php echo htmlspecialchars($row['review']); ?></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['review_date']))); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 