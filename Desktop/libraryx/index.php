<?php
require_once 'config.php';

// Handle borrowing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_book'])) {
    $book_id = $_POST['book_id'];
    $borrower_name = $_POST['borrower_name'];
    $due_date = date('Y-m-d H:i:s', strtotime('+14 days')); // 2 weeks borrowing period
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update book status
        $sql = "UPDATE books SET status = 'borrowed' WHERE id = ? AND status = 'available'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Add to borrowed_books
            $sql = "INSERT INTO borrowed_books (book_id, borrower_name, due_date) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $book_id, $borrower_name, $due_date);
            $stmt->execute();
            
            $conn->commit();
            $success_message = "Book borrowed successfully!";
        } else {
            throw new Exception("Book is no longer available.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

// Handle adding to wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_wishlist'])) {
    $book_id = $_POST['book_id'];
    $borrower_name = $_POST['borrower_name'];
    
    $sql = "INSERT INTO wishlist (book_id, borrower_name) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $book_id, $borrower_name);
    $stmt->execute();
    
    $success_message = "Book added to wishlist!";
}

// Handle notification request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notify_me'])) {
    $book_id = $_POST['book_id'];
    $notify_name = $_POST['notify_name'];
    $notify_email = $_POST['notify_email'];
    $sql = "INSERT INTO notifications (book_id, name, email) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $book_id, $notify_name, $notify_email);
    $stmt->execute();
    $success_message = "You will be notified when this book becomes available!";
}

// Get search and category filter from GET
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Pagination and sorting
$per_page = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'title';
$sort_options = [
    'title' => 'b.title',
    'author' => 'b.author',
    'category' => 'c.name',
    'recent' => 'b.id DESC'
];
$order_by = isset($sort_options[$sort]) ? $sort_options[$sort] : $sort_options['title'];

// Get all categories for filter
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];
while($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Get all tags for filter
$tags_sql = "SELECT * FROM tags ORDER BY name";
$tags_result = $conn->query($tags_sql);
$tags = [];
while($row = $tags_result->fetch_assoc()) {
    $tags[] = $row;
}

// Get tag filter from GET
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';

// Build the query dynamically
$sql = "SELECT DISTINCT b.*, c.name as category 
        FROM books b 
        JOIN categories c ON b.category_id = c.id 
        LEFT JOIN book_tags bt ON b.id = bt.book_id
        LEFT JOIN tags t ON bt.tag_id = t.id
        WHERE b.status = 'available'";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}
if ($category !== '') {
    $sql .= " AND b.category_id = ?";
    $params[] = $category;
    $types .= 'i';
}
if ($tag !== '') {
    $sql .= " AND t.id = ?";
    $params[] = $tag;
    $types .= 'i';
}
$sql .= " ORDER BY $order_by LIMIT $per_page OFFSET $offset";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get total count for pagination
$count_sql = "SELECT COUNT(DISTINCT b.id) as total 
              FROM books b 
              LEFT JOIN book_tags bt ON b.id = bt.book_id
              LEFT JOIN tags t ON bt.tag_id = t.id
              WHERE b.status = 'available'";
$count_params = [];
$count_types = '';
if ($search !== '') {
    $count_sql .= " AND (b.title LIKE ? OR b.author LIKE ?)";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
    $count_types .= 'ss';
}
if ($category !== '') {
    $count_sql .= " AND b.category_id = ?";
    $count_params[] = $category;
    $count_types .= 'i';
}
if ($tag !== '') {
    $count_sql .= " AND t.id = ?";
    $count_params[] = $tag;
    $count_types .= 'i';
}
$count_stmt = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_books = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_books / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibraryX - Available Books</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dark-mode.css">
    <link rel="stylesheet" href="contrast-mode.css">
    <style>
        body {
            background: #f5f6fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 0;
        }
        .search-section {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 1.5rem 2rem;
            margin-bottom: 2.5rem;
            display: flex;
            justify-content: center;
        }
        .search-form {
            width: 100%;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .search-form input[type="text"], .search-form select {
            padding: 0.9rem 1.1rem;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 1.08rem;
            background: #f8fafd;
        }
        .search-btn {
            background: #3498db;
            color: #fff;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1.08rem;
            padding: 0.9rem 2.2rem;
            box-shadow: 0 2px 6px rgba(52,152,219,0.08);
            border: none;
            transition: background 0.2s;
        }
        .search-btn:hover {
            background: #217dbb;
        }
        h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
            color: #222;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
            gap: 2.2rem;
        }
        .book-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            transition: box-shadow 0.2s, transform 0.2s;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }
        .book-card:hover {
            box-shadow: 0 6px 24px rgba(52,152,219,0.13);
            transform: translateY(-4px) scale(1.02);
        }
        .book-card h3 {
            margin-bottom: 0.3rem;
        }
        .book-card h3 a {
            color: #222;
            text-decoration: none;
            font-size: 1.22rem;
            font-weight: 700;
            transition: color 0.2s;
        }
        .book-card h3 a:hover {
            color: #1976d2;
            text-decoration: underline;
        }
        .book-card .author {
            color: #555;
            font-style: italic;
            font-size: 1rem;
            margin-bottom: 0.1rem;
        }
        .book-card .category {
            color: #3498db;
            font-weight: 500;
            font-size: 1rem;
            margin-bottom: 0.1rem;
        }
        .book-card .isbn {
            color: #888;
            font-size: 0.98rem;
            margin-bottom: 0.5rem;
        }
        .book-card .status {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 20px;
            font-size: 0.98rem;
            font-weight: 500;
            padding: 0.3rem 1.1rem;
            margin-bottom: 1.1rem;
        }
        .book-actions {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
        }
        .borrow-form input[type="text"] {
            padding: 0.7rem 1rem;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        .borrow-btn {
            background: #2ecc71;
            color: #fff;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1.08rem;
            padding: 0.7rem 0;
            border: none;
            transition: background 0.2s;
        }
        .borrow-btn:hover {
            background: #27ae60;
        }
        .wishlist-btn {
            background: #f1c40f;
            color: #fff;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1.08rem;
            padding: 0.7rem 0;
            border: none;
            transition: background 0.2s;
        }
        .wishlist-btn:hover {
            background: #f39c12;
        }
        @media (max-width: 900px) {
            .container { padding: 10px 0; }
            .search-section { padding: 1rem; }
            .book-grid { gap: 1.2rem; }
        }
        @media (max-width: 600px) {
            .search-form { flex-direction: column; gap: 0.7rem; }
            .book-card { padding: 1.2rem 0.7rem; }
        }
        .book-tags { margin-top: 0.5rem; }
        .book-tag-label {
            display: inline-block;
            background: #f1c40f;
            color: #fff;
            border-radius: 12px;
            font-size: 0.93rem;
            font-weight: 500;
            padding: 0.2rem 0.8rem;
            margin-right: 0.3rem;
            margin-bottom: 0.2rem;
        }
        .accessibility-toggles {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1001;
            display: flex;
            flex-direction: column;
            gap: 16px;
            align-items: flex-end;
        }
        .fontsize-controls {
            display: flex;
            flex-direction: row;
            gap: 6px;
            margin-top: 20px;
        }
        .fontsize-btn {
            background: #fff;
            color: #222;
            border: 2px solid #888;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: bold;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .fontsize-btn:focus {
            outline: 2px solid #3498db;
        }
        .theme-toggle {
            min-width: 40px;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            cursor: pointer;
            margin-left: 16px;
        }
        .theme-toggle svg {
            width: 28px;
            height: 28px;
            fill: #222;
        }
        [data-theme="dark"] .theme-toggle svg {
            fill: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="nav-bar">
            <h1>LibraryX</h1>
            <div class="nav-links">
                <a href="index.php" class="active">Home</a>
                <a href="borrowed.php">Borrowed Books</a>
                <a href="history.php">Borrowing History</a>
                <a href="wishlist.php">Wishlist</a>
                <a href="stats.php">Statistics</a>
            </div>
            <button id="nav-dark-toggle" class="theme-toggle" aria-label="Toggle dark mode" style="margin-left:24px; background:none; border:none; cursor:pointer; display:flex; align-items:center;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-2.98 0-5.4-2.42-5.4-5.4 0-1.81.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z"/></svg>
            </button>
        </nav>

        <main>
            <div class="search-section">
                <form action="" method="get" class="search-form">
                    <input type="text" name="search" placeholder="Search books..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="tag">
                        <option value="">All Tags</option>
                        <?php foreach($tags as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo (isset($_GET['tag']) && $_GET['tag'] == $t['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="sort">
                        <option value="title" <?php if($sort=='title') echo 'selected'; ?>>Sort by Title</option>
                        <option value="author" <?php if($sort=='author') echo 'selected'; ?>>Sort by Author</option>
                        <option value="category" <?php if($sort=='category') echo 'selected'; ?>>Sort by Category</option>
                        <option value="recent" <?php if($sort=='recent') echo 'selected'; ?>>Recently Added</option>
                    </select>
                    <button type="submit" class="btn search-btn">Search</button>
                </form>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <h2>Available Books</h2>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="book-grid">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="book-card">
                            <div class="book-info">
                                <h3><a href="book_details.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h3>
                                <p class="author">by <?php echo htmlspecialchars($row['author']); ?></p>
                                <p class="category"><?php echo htmlspecialchars($row['category']); ?></p>
                                <p class="isbn">ISBN: <?php echo htmlspecialchars($row['isbn']); ?></p>
                                <p class="status <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></p>
                                <?php
                                    $tag_labels = '';
                                    $tag_query = $conn->query("SELECT t.name FROM tags t JOIN book_tags bt ON t.id = bt.tag_id WHERE bt.book_id = " . $row['id']);
                                    while($tag = $tag_query->fetch_assoc()) {
                                        $tag_labels .= '<span class="book-tag-label">' . htmlspecialchars($tag['name']) . '</span> ';
                                    }
                                    if ($tag_labels) {
                                        echo '<div class="book-tags">' . $tag_labels . '</div>';
                                    }
                                ?>
                            </div>
                            <div class="book-actions">
                                <?php if ($row['status'] === 'available'): ?>
                                    <form action="" method="post" class="borrow-form">
                                        <input type="hidden" name="book_id" value="<?php echo $row['id']; ?>">
                                        <input type="text" name="borrower_name" placeholder="Your Name" required>
                                        <button type="submit" name="borrow_book" class="btn borrow-btn">Borrow</button>
                                    </form>
                                    <form action="" method="post" class="wishlist-form">
                                        <input type="hidden" name="book_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="borrower_name" value="">
                                        <button type="submit" name="add_to_wishlist" class="btn wishlist-btn">Add to Wishlist</button>
                                    </form>
                                <?php else: ?>
                                    <form action="" method="post" class="notify-form">
                                        <input type="hidden" name="book_id" value="<?php echo $row['id']; ?>">
                                        <input type="text" name="notify_name" placeholder="Your Name" required>
                                        <input type="email" name="notify_email" placeholder="Your Email" required>
                                        <button type="submit" name="notify_me" class="btn wishlist-btn" style="background:#1976d2;">Notify Me</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-books">No books available at the moment.</p>
            <?php endif; ?>

            <?php if ($total_pages > 1): ?>
                <div style="text-align:center; margin-top:2rem;">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                           style="display:inline-block; margin:0 6px; padding:7px 15px; border-radius:5px; background:<?php echo $i==$page?'#3498db':'#f1f1f1'; ?>; color:<?php echo $i==$page?'#fff':'#222'; ?>; text-decoration:none; font-weight:600;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <div class="accessibility-toggles"></div>
    <script src="script.js"></script>
    <script src="dark-mode.js"></script>
    <script>
    // High-contrast mode toggle
    function setContrastMode(enabled) {
      if (enabled) {
        document.body.classList.add('contrast-mode');
        localStorage.setItem('contrastMode', '1');
      } else {
        document.body.classList.remove('contrast-mode');
        localStorage.setItem('contrastMode', '0');
      }
    }
    document.addEventListener('DOMContentLoaded', function() {
      // Add toggle button
      const btn = document.createElement('button');
      btn.className = 'contrast-toggle';
      btn.setAttribute('aria-label', 'Toggle high contrast mode');
      btn.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" fill="#ff0"/><path d="M12 6v6l4 2" stroke="#000"/></svg>';
      btn.style.position = 'fixed';
      btn.style.bottom = '90px';
      btn.style.right = '24px';
      btn.style.zIndex = '1001';
      btn.style.background = '#ff0';
      btn.style.border = '2px solid #000';
      btn.style.borderRadius = '50%';
      btn.style.width = '48px';
      btn.style.height = '48px';
      btn.style.display = 'flex';
      btn.style.alignItems = 'center';
      btn.style.justifyContent = 'center';
      btn.style.boxShadow = '0 2px 8px rgba(0,0,0,0.18)';
      btn.style.cursor = 'pointer';
      btn.style.transition = 'background 0.2s';
      btn.tabIndex = 0;
      btn.onkeydown = function(e) { if (e.key === 'Enter' || e.key === ' ') { btn.click(); } };
      btn.onclick = function() {
        const enabled = !document.body.classList.contains('contrast-mode');
        setContrastMode(enabled);
      };
      var togglesDiv = document.querySelector('.accessibility-toggles');
      if (togglesDiv) togglesDiv.appendChild(btn); else document.body.appendChild(btn);
      // Restore preference
      if (localStorage.getItem('contrastMode') === '1') setContrastMode(true);
    });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      const togglesDiv = document.querySelector('.accessibility-toggles');
      if (!togglesDiv) return;
      const fsDiv = document.createElement('div');
      fsDiv.className = 'fontsize-controls';
      const btnMinus = document.createElement('button');
      btnMinus.className = 'fontsize-btn';
      btnMinus.setAttribute('aria-label', 'Decrease font size');
      btnMinus.textContent = 'A-';
      const btnReset = document.createElement('button');
      btnReset.className = 'fontsize-btn';
      btnReset.setAttribute('aria-label', 'Reset font size');
      btnReset.textContent = 'A';
      const btnPlus = document.createElement('button');
      btnPlus.className = 'fontsize-btn';
      btnPlus.setAttribute('aria-label', 'Increase font size');
      btnPlus.textContent = 'A+';
      fsDiv.append(btnMinus, btnReset, btnPlus);
      // Remove any existing .fontsize-controls before appending
      const oldFs = togglesDiv.querySelector('.fontsize-controls');
      if (oldFs) togglesDiv.removeChild(oldFs);
      togglesDiv.appendChild(fsDiv);
      // Font size logic
      const minSize = 14, maxSize = 22, defaultSize = 16;
      function setFontSize(size) {
        size = Math.max(minSize, Math.min(maxSize, size));
        document.documentElement.style.fontSize = size + 'px';
        localStorage.setItem('fontSize', size);
      }
      // Restore preference
      const saved = parseInt(localStorage.getItem('fontSize'), 10);
      setFontSize(saved && !isNaN(saved) ? saved : defaultSize);
      btnMinus.onclick = () => setFontSize(parseInt(getComputedStyle(document.documentElement).fontSize) - 2);
      btnPlus.onclick = () => setFontSize(parseInt(getComputedStyle(document.documentElement).fontSize) + 2);
      btnReset.onclick = () => setFontSize(defaultSize);
    });
    </script>
</body>
</html> 