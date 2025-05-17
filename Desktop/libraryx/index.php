<?php
session_start();
require_once 'config.php';

// Handle borrowing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_book'])) {
    $book_id = $_POST['book_id'];
    $borrower_name = $_SESSION['full_name'];
    $due_date = date('Y-m-d H:i:s', strtotime('+7 days'));
    $conn->begin_transaction();
    try {
        // Check available copies
        $sql = "SELECT available_copies FROM books WHERE id = ? FOR UPDATE";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $book = $result->fetch_assoc();
        if (!$book || $book['available_copies'] <= 0) {
            throw new Exception("No copies available.");
        }
        // Decrement available_copies
        $sql = "UPDATE books SET available_copies = available_copies - 1, status = IF(available_copies - 1 = 0, 'borrowed', 'available') WHERE id = ? AND available_copies > 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
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
    $borrower_name = $_SESSION['full_name'];
    
    $sql = "INSERT INTO wishlist (book_id, borrower_name) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $book_id, $borrower_name);
    $stmt->execute();
    
    $success_message = "Book added to wishlist!";
}

// Handle notification request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notify_me'])) {
    $book_id = $_POST['book_id'];
    $notify_name = $_SESSION['full_name'];
    $notify_email = $_SESSION['email'];
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

// Get resource type filter from GET
$type = isset($_GET['type']) ? trim($_GET['type']) : '';

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
if ($type !== '') {
    $sql .= " AND b.type = ?";
    $params[] = $type;
    $types .= 's';
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
if ($type !== '') {
    $count_sql .= " AND b.type = ?";
    $count_params[] = $type;
    $count_types .= 's';
}
$count_stmt = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_books = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_books / $per_page);

$type_icons = [
    'book' => '📚 Book',
    'article' => '📰 Article',
    'magazine' => '🗞️ Magazine',
    'research_paper' => '📄 Research'
];

// Personalized Recommendations Carousel
$recommendations = [];
if (isset($_SESSION['user_id'])) {
    // Get categories and authors from user's history and wishlist
    $user = $_SESSION['full_name'];
    $cat_sql = "SELECT DISTINCT b.category_id FROM borrowed_books bb JOIN books b ON bb.book_id = b.id WHERE bb.borrower_name = ? UNION SELECT DISTINCT b.category_id FROM wishlist w JOIN books b ON w.book_id = b.id WHERE w.borrower_name = ?";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->bind_param("ss", $user, $user);
    $cat_stmt->execute();
    $cat_res = $cat_stmt->get_result();
    $categories = [];
    while ($row = $cat_res->fetch_assoc()) {
        $categories[] = $row['category_id'];
    }
    $auth_sql = "SELECT DISTINCT b.author FROM borrowed_books bb JOIN books b ON bb.book_id = b.id WHERE bb.borrower_name = ? UNION SELECT DISTINCT b.author FROM wishlist w JOIN books b ON w.book_id = b.id WHERE w.borrower_name = ?";
    $auth_stmt = $conn->prepare($auth_sql);
    $auth_stmt->bind_param("ss", $user, $user);
    $auth_stmt->execute();
    $auth_res = $auth_stmt->get_result();
    $authors = [];
    while ($row = $auth_res->fetch_assoc()) {
        $authors[] = $row['author'];
    }
    // Recommend books not already borrowed or wishlisted, matching category or author
    if ($categories || $authors) {
        $cat_placeholders = $categories ? implode(',', array_fill(0, count($categories), '?')) : '';
        $auth_placeholders = $authors ? implode(',', array_fill(0, count($authors), '?')) : '';
        $params = [];
        $types = '';
        $where = [];
        if ($categories) {
            $where[] = 'b.category_id IN (' . $cat_placeholders . ')';
            $params = array_merge($params, $categories);
            $types .= str_repeat('i', count($categories));
        }
        if ($authors) {
            $where[] = 'b.author IN (' . $auth_placeholders . ')';
            $params = array_merge($params, $authors);
            $types .= str_repeat('s', count($authors));
        }
        $sql = "SELECT b.*, c.name as category FROM books b JOIN categories c ON b.category_id = c.id WHERE b.status = 'available' AND (" . implode(' OR ', $where) . ") AND b.id NOT IN (SELECT book_id FROM borrowed_books WHERE borrower_name = ? UNION SELECT book_id FROM wishlist WHERE borrower_name = ?) LIMIT 5";
        $params[] = $user;
        $params[] = $user;
        $types .= 'ss';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $recommendations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
if (empty($recommendations)) {
    // Fallback: show 5 most popular available books
    $sql = "SELECT b.*, c.name as category, COUNT(bb.id) as borrow_count FROM books b JOIN categories c ON b.category_id = c.id LEFT JOIN borrowed_books bb ON b.id = bb.book_id WHERE b.status = 'available' GROUP BY b.id ORDER BY borrow_count DESC, b.id DESC LIMIT 5";
    $recommendations = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibraryX - Available Books</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/cdn.min.js"></script>
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
      #chatbox-collapsed {
        position: fixed; bottom: 24px; right: 24px; width: 60px; height: 60px; background: linear-gradient(135deg,#4f46e5,#0ea5e9); border-radius: 50%; box-shadow: 0 2px 12px #0002; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 9999; transition: box-shadow 0.2s;
        }
      #chatbox-collapsed:hover { box-shadow: 0 4px 24px #4f46e5aa; }
      #chatbox-expanded {
        position: fixed; bottom: 24px; right: 24px; width: 340px; background: #fff; border-radius: 18px; box-shadow: 0 4px 32px #0003; padding: 0; z-index: 9999; display: flex; flex-direction: column; transition: box-shadow 0.2s, width 0.2s;
        }
      #chatbox-header { background: linear-gradient(135deg,#4f46e5,#0ea5e9); color: #fff; border-radius: 18px 18px 0 0; padding: 16px; display: flex; align-items: center; justify-content: space-between; }
      #chatbox-close { background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; }
      #chatlog { height: 220px; overflow-y: auto; margin: 0 16px 8px 16px; font-size: 15px; }
      #usermsg { width: 70%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; margin-left: 16px; }
      #sendbtn { padding: 8px 12px; border: none; background: #4f46e5; color: #fff; border-radius: 6px; margin-right: 16px; }
    </style>
</head>
<body class="relative font-sans min-h-screen transition-colors duration-300">
    <!-- Animated floating shapes -->
    <div class="floating-shape floating-shape1"></div>
    <div class="floating-shape floating-shape2"></div>
    <div class="floating-shape floating-shape3"></div>
    <div class="max-w-7xl mx-auto px-4 py-6 relative z-10">
        <?php include 'navbar.php'; ?>
        <!-- Book Illustration for Fun -->
        <div class="flex justify-center mb-8">
          <svg width="140" height="140" viewBox="0 0 140 140" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="20" y="40" width="30" height="70" rx="6" fill="#4f46e5"/>
            <rect x="50" y="30" width="30" height="80" rx="6" fill="#0ea5e9"/>
            <rect x="80" y="50" width="30" height="60" rx="6" fill="#a5b4fc"/>
            <rect x="35" y="60" width="70" height="10" rx="3" fill="#10b981"/>
            <rect x="35" y="80" width="70" height="10" rx="3" fill="#6b7280"/>
            <rect x="35" y="100" width="70" height="10" rx="3" fill="#e0e7ff"/>
            <rect x="35" y="120" width="70" height="6" rx="3" fill="#1f2937"/>
          </svg>
        </div>
        <!-- Surprise Me (Borrow Roulette) Button -->
        <div class="flex justify-center mb-8">
          <button 
            x-data="{}"
            @click="$dispatch('open-roulette')"
            class="flex items-center gap-2 px-6 py-3 rounded-full bg-gradient-to-r from-[#4f46e5] to-[#0ea5e9] text-white font-bold text-lg shadow-lg hover:from-[#0ea5e9] hover:to-[#4f46e5] transition focus:ring-2 focus:ring-primary">
            <span class="text-2xl">🎯</span> Surprise Me
          </button>
        </div>
        <!-- Redesigned Filter/Search Bar -->
        <div class="bg-white/70 rounded-full shadow-glass p-2 mb-8 flex flex-col md:flex-row items-center gap-2 md:gap-0 border border-white/30 max-w-5xl mx-auto">
          <form action="" method="get" class="flex flex-col md:flex-row w-full items-center gap-2 md:gap-0">
            <input type="text" name="search" placeholder="Search books..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" class="flex-1 px-4 py-2 rounded-full border-none bg-transparent text-lg focus:ring-2 focus:ring-primary outline-none transition min-w-[160px]" />
            <div class="w-px h-8 bg-gray-200 mx-2 hidden md:block"></div>
            <div class="flex flex-row gap-2 md:gap-0">
              <!-- Category Dropdown -->
              <div x-data="{ open: false, search: '', selected: '<?php echo isset($_GET['category']) ? $_GET['category'] : ''; ?>' }" class="relative">
                <button type="button" @click="open = !open" class="flex items-center gap-1 px-3 py-2 rounded-full border-none bg-transparent text-base hover:bg-gray-100 transition">
                  <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                  <span class="hidden md:inline" x-text="$refs.catSelect.options[$refs.catSelect.selectedIndex]?.text || 'All Categories'"></span>
                </button>
                <select x-ref="catSelect" name="category" class="hidden">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <div x-show="open" @click.away="open = false" class="absolute left-0 mt-2 w-48 bg-white rounded-xl shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto animate-fade-in">
                  <input x-model="search" type="text" placeholder="Search..." class="w-full px-3 py-2 border-b border-gray-200 rounded-t-xl focus:outline-none" />
                  <template x-for="(option, i) in Array.from($refs.catSelect.options).filter(o => o.text.toLowerCase().includes(search.toLowerCase()))" :key="i">
                    <div @click="$refs.catSelect.selectedIndex = option.index; open = false" class="px-4 py-2 cursor-pointer hover:bg-primary/10" x-text="option.text"></div>
                  </template>
                </div>
              </div>
              <div class="w-px h-8 bg-gray-200 mx-2 hidden md:block"></div>
              <!-- Type Dropdown -->
              <div x-data="{ open: false, selected: '<?php echo isset($_GET['type']) ? $_GET['type'] : ''; ?>' }" class="relative">
                <button type="button" @click="open = !open" class="flex items-center gap-1 px-3 py-2 rounded-full border-none bg-transparent text-base hover:bg-gray-100 transition">
                  <svg class="w-5 h-5 text-[#047857]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                  <span class="hidden md:inline" x-text="$refs.typeSelect.options[$refs.typeSelect.selectedIndex]?.text || 'All Types'"></span>
                </button>
                <select x-ref="typeSelect" name="type" class="hidden">
                  <option value="">All Types</option>
                  <option value="book" <?php if(isset($_GET['type']) && $_GET['type']==='book') echo 'selected'; ?>>Book</option>
                  <option value="article" <?php if(isset($_GET['type']) && $_GET['type']==='article') echo 'selected'; ?>>Article</option>
                  <option value="magazine" <?php if(isset($_GET['type']) && $_GET['type']==='magazine') echo 'selected'; ?>>Magazine</option>
                  <option value="research_paper" <?php if(isset($_GET['type']) && $_GET['type']==='research_paper') echo 'selected'; ?>>Research Paper</option>
                </select>
                <div x-show="open" @click.away="open = false" class="absolute left-0 mt-2 w-48 bg-white rounded-xl shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto animate-fade-in">
                  <template x-for="(option, i) in Array.from($refs.typeSelect.options)" :key="i">
                    <div @click="$refs.typeSelect.selectedIndex = option.index; open = false" class="px-4 py-2 cursor-pointer hover:bg-[#d1fae5]/40" x-text="option.text"></div>
                  </template>
                </div>
              </div>
              <div class="w-px h-8 bg-gray-200 mx-2 hidden md:block"></div>
              <!-- Tag Dropdown -->
              <div x-data="{ open: false, search: '', selected: '<?php echo isset($_GET['tag']) ? $_GET['tag'] : ''; ?>' }" class="relative">
                <button type="button" @click="open = !open" class="flex items-center gap-1 px-3 py-2 rounded-full border-none bg-transparent text-base hover:bg-gray-100 transition">
                  <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 12l2 2 4-4"/></svg>
                  <span class="hidden md:inline" x-text="$refs.tagSelect.options[$refs.tagSelect.selectedIndex]?.text || 'All Tags'"></span>
                </button>
                <select x-ref="tagSelect" name="tag" class="hidden">
                        <option value="">All Tags</option>
                        <?php foreach($tags as $t): ?>
                    <option value="<?php echo $t['id']; ?>" <?php echo (isset($_GET['tag']) && $_GET['tag'] == $t['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <div x-show="open" @click.away="open = false" class="absolute left-0 mt-2 w-48 bg-white rounded-xl shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto animate-fade-in">
                  <input x-model="search" type="text" placeholder="Search..." class="w-full px-3 py-2 border-b border-gray-200 rounded-t-xl focus:outline-none" />
                  <template x-for="(option, i) in Array.from($refs.tagSelect.options).filter(o => o.text.toLowerCase().includes(search.toLowerCase()))" :key="i">
                    <div @click="$refs.tagSelect.selectedIndex = option.index; open = false" class="px-4 py-2 cursor-pointer hover:bg-accent/10" x-text="option.text"></div>
                  </template>
                </div>
              </div>
              <div class="w-px h-8 bg-gray-200 mx-2 hidden md:block"></div>
              <!-- Sort Dropdown -->
              <div x-data="{ open: false, selected: '<?php echo $sort; ?>' }" class="relative">
                <button type="button" @click="open = !open" class="flex items-center gap-1 px-3 py-2 rounded-full border-none bg-transparent text-base hover:bg-gray-100 transition">
                  <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/></svg>
                  <span class="hidden md:inline" x-text="$refs.sortSelect.options[$refs.sortSelect.selectedIndex]?.text || 'Sort by Title'"></span>
                </button>
                <select x-ref="sortSelect" name="sort" class="hidden">
                        <option value="title" <?php if($sort=='title') echo 'selected'; ?>>Sort by Title</option>
                        <option value="author" <?php if($sort=='author') echo 'selected'; ?>>Sort by Author</option>
                        <option value="category" <?php if($sort=='category') echo 'selected'; ?>>Sort by Category</option>
                        <option value="recent" <?php if($sort=='recent') echo 'selected'; ?>>Recently Added</option>
                    </select>
                <div x-show="open" @click.away="open = false" class="absolute left-0 mt-2 w-48 bg-white rounded-xl shadow-lg z-20 border border-gray-200 max-h-60 overflow-y-auto animate-fade-in">
                  <template x-for="(option, i) in Array.from($refs.sortSelect.options)" :key="i">
                    <div @click="$refs.sortSelect.selectedIndex = option.index; open = false" class="px-4 py-2 cursor-pointer hover:bg-primary/10" x-text="option.text"></div>
                  </template>
                </div>
              </div>
            </div>
            <button type="submit" class="ml-0 md:ml-4 px-6 py-2 rounded-full bg-gradient-to-r from-primary to-accent text-white font-bold text-lg shadow-lg hover:from-accent hover:to-primary transition flex items-center gap-2 group focus:ring-2 focus:ring-primary">
              <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              <span>Search</span>
            </button>
                </form>
        </div>
        <?php if (isset($success_message)): ?>
            <div class="mb-4 px-6 py-3 rounded-lg bg-success/20 text-success font-semibold text-lg shadow animate-bounce-in">
              <span class="inline-block align-middle mr-2">🎉</span> <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
            <div class="mb-4 px-6 py-3 rounded-lg bg-error/20 text-error font-semibold text-lg shadow animate-shake">
              <span class="inline-block align-middle mr-2">❌</span> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
        <h2 class="text-2xl md:text-3xl font-bold text-white mb-6 border-b border-white/30 pb-2 drop-shadow-lg">Available Books</h2>
            <?php if ($result->num_rows > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition transform hover:-translate-y-2 hover:scale-105 p-6 flex flex-col animate-fade-in-up relative overflow-hidden group">
                        <div class="absolute -top-8 -right-8 opacity-10 group-hover:opacity-20 transition pointer-events-none">
                          <svg width="80" height="80" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="#6366f1" stroke-width="4"/></svg>
                        </div>
                        <div class="mb-4">
                            <span class="inline-block mb-2 px-3 py-1 rounded-full text-xs font-bold <?php
                                if ($row['type'] == 'book') echo 'bg-[#e0e7ff] text-[#4f46e5]';
                                elseif ($row['type'] == 'article') echo 'bg-[#fef9c3] text-[#b45309]';
                                elseif ($row['type'] == 'magazine') echo 'bg-[#fce7f3] text-[#be185d]';
                                elseif ($row['type'] == 'research_paper') echo 'bg-[#d1fae5] text-[#047857]';
                            ?>">
                                <?php echo $type_icons[$row['type']] ?? ucfirst($row['type']); ?>
                            </span>
                            <h3 class="text-xl font-bold text-[#1f2937] mb-1 font-poppins"><a href="book_details.php?id=<?php echo $row['id']; ?>" class="hover:text-[#4f46e5] transition underline underline-offset-4 decoration-[#0ea5e9]/60"><?php echo htmlspecialchars($row['title']); ?></a></h3>
                            <p class="italic text-[#6b7280] mb-1">by <?php echo htmlspecialchars($row['author']); ?></p>
                            <p class="text-[#4f46e5] font-medium mb-1">📚 <?php echo htmlspecialchars($row['category']); ?></p>
                            <p class="text-[#6b7280] text-sm mb-2">ISBN: <?php echo !empty($row['isbn']) ? htmlspecialchars($row['isbn']) : 'N/A'; ?></p>
                            <p class="text-[#6b7280] text-sm mb-2">Available copies: <?php echo $row['available_copies']; ?> / <?php echo $row['copies']; ?></p>
                            <p class="inline-block bg-[#10b981]/10 text-[#10b981] rounded-full px-4 py-1 text-sm font-semibold mb-2">✔ <?php echo ucfirst($row['status']); ?></p>
                                <?php
                                    $tag_labels = '';
                                    $tag_query = $conn->query("SELECT t.name FROM tags t JOIN book_tags bt ON t.id = bt.tag_id WHERE bt.book_id = " . $row['id']);
                                    while($tag = $tag_query->fetch_assoc()) {
                                    $tag_labels .= '<span class="inline-block bg-accent text-white rounded-full px-3 py-1 text-xs font-semibold mr-2 mb-1">#' . htmlspecialchars($tag['name']) . '</span> ';
                                    }
                                    if ($tag_labels) {
                                    echo '<div class="mt-2">' . $tag_labels . '</div>';
                                    }
                                ?>
                            </div>
                        <div class="mt-auto flex flex-col gap-2">
                                <?php if ($row['status'] === 'available'): ?>
                                <form action="" method="post" class="flex flex-col gap-2">
                                        <input type="hidden" name="book_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="borrow_book" class="px-4 py-2 rounded-lg bg-[#4f46e5] text-white font-bold shadow-lg hover:bg-[#0ea5e9] transition flex items-center justify-center gap-2 group focus:ring-2 focus:ring-[#4f46e5]">
                                      <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5 group-hover:scale-110 transition-transform' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7' /></svg>Borrow
                                    </button>
                                    </form>
                                <form action="" method="post">
                                        <input type="hidden" name="book_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="borrower_name" value="">
                                    <button type="submit" name="add_to_wishlist" class="px-4 py-2 rounded-lg bg-[#0ea5e9] text-white font-bold shadow-lg hover:bg-[#4f46e5] transition flex items-center justify-center gap-2 group focus:ring-2 focus:ring-[#0ea5e9]">
                                      <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5 group-hover:scale-110 transition-transform' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 15l7-7 7 7' /></svg>Add to Wishlist
                                    </button>
                                    </form>
                                <?php else: ?>
                                <form action="" method="post" class="flex flex-col gap-2">
                                        <input type="hidden" name="book_id" value="<?php echo $row['id']; ?>">
                                    <input type="text" name="notify_name" placeholder="Your Name" required class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-primary outline-none transition" />
                                    <input type="email" name="notify_email" placeholder="Your Email" required class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-primary outline-none transition" />
                                    <button type="submit" name="notify_me" class="px-4 py-2 rounded-lg bg-gradient-to-r from-primary to-accent text-white font-bold shadow-lg hover:from-accent hover:to-primary transition flex items-center justify-center gap-2 group focus:ring-2 focus:ring-primary">
                                      <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5 group-hover:scale-110 transition-transform' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m4 4h-1v-4h-1' /></svg>Notify Me
                                    </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
            <p class="text-center text-lg text-white mt-8">No books available at the moment.</p>
            <?php endif; ?>
            <?php if ($total_pages > 1): ?>
            <div class="flex justify-center mt-10 gap-2">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                       class="px-4 py-2 rounded-lg font-bold transition bg-gradient-to-r <?php echo $i==$page?'from-primary to-accent text-white shadow-lg':'from-white/80 to-gray-100/80 text-primary hover:from-primary hover:to-accent hover:text-white'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <!-- Personalized Recommendations Carousel -->
        <div class="my-10">
          <h2 class="text-2xl font-bold text-[#4f46e5] mb-4">Recommended for You</h2>
          <div class="relative" x-data="{ scroll: 0 }">
            <button @click="scroll -= 320" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-white/80 rounded-full shadow p-2 hover:bg-[#e0e7ff] transition hidden md:block"><svg class="w-6 h-6 text-[#4f46e5]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
            <div class="overflow-x-auto no-scrollbar">
              <div class="flex gap-6 transition-transform duration-300" :style="'transform: translateX(' + scroll + 'px)'">
                <?php foreach ($recommendations as $rec): ?>
                  <div class="min-w-[280px] max-w-xs bg-white rounded-2xl shadow-xl p-6 flex flex-col items-start hover:shadow-2xl transition">
                    <span class="inline-block mb-2 px-3 py-1 rounded-full text-xs font-bold bg-[#e0e7ff] text-[#4f46e5]">Book</span>
                    <h3 class="text-lg font-bold text-[#1f2937] mb-1"><?php echo htmlspecialchars($rec['title']); ?></h3>
                    <p class="italic text-[#6b7280] mb-1">by <?php echo htmlspecialchars($rec['author']); ?></p>
                    <p class="text-[#4f46e5] font-medium mb-2">📚 <?php echo htmlspecialchars($rec['category']); ?></p>
                    <form action="index.php" method="post" class="w-full flex gap-2 mt-2">
                      <input type="hidden" name="book_id" value="<?php echo $rec['id']; ?>">
                      <button type="submit" name="borrow_book" class="flex-1 px-4 py-2 rounded-lg bg-[#4f46e5] text-white font-bold shadow hover:bg-[#0ea5e9] transition">Borrow</button>
                      <button type="submit" name="add_to_wishlist" class="flex-1 px-4 py-2 rounded-lg bg-[#0ea5e9] text-white font-bold shadow hover:bg-[#4f46e5] transition">Wishlist</button>
                    </form>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <button @click="scroll += 320" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-white/80 rounded-full shadow p-2 hover:bg-[#e0e7ff] transition hidden md:block"><svg class="w-6 h-6 text-[#4f46e5]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
          </div>
        </div>
    </div>
    <!-- Borrow Roulette Modal -->
    <div x-data="borrowRoulette()" x-show="open" @open-roulette.window="openRoulette()" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" x-cloak>
      <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full relative flex flex-col items-center">
        <button @click="open = false" class="absolute top-4 right-4 text-gray-400 hover:text-[#e11d48] text-2xl">&times;</button>
        <h2 class="text-2xl font-bold mb-4 text-center">Borrow Roulette</h2>
        <template x-if="!result">
          <div class="flex flex-col items-center">
            <div class="mb-6 w-64 h-20 overflow-hidden relative">
              <!-- Animated list of book titles/authors, repeated for infinite feel -->
              <div :style="'transform: translateY(' + offset + 'px); transition: ' + transitionStyle" class="flex flex-col">
                <template x-for="(book, i) in displayBooks" :key="i">
                  <div :class="'h-12 flex flex-col items-center justify-center text-lg font-semibold w-64 transition-all duration-200 ' + (centerIndex === i ? 'scale-110 shadow-lg ring-2 ring-[#10b981] bg-[#f0fdf4]')">
                    <span x-text="book.title"></span>
                    <span class="text-xs text-[#6b7280]" x-text="'by ' + book.author"></span>
                  </div>
                </template>
              </div>
              <div class="absolute top-1/2 left-0 w-full h-12 border-t-4 border-b-4 border-[#10b981] pointer-events-none shadow-xl" style="transform: translateY(-50%)"></div>
            </div>
            <button @click="spinRoulette()" :disabled="spinning" class="px-6 py-3 rounded-full bg-gradient-to-r from-[#4f46e5] to-[#0ea5e9] text-white font-bold text-lg shadow-lg hover:from-[#0ea5e9] hover:to-[#4f46e5] transition disabled:opacity-50">Spin Roulette</button>
          </div>
        </template>
        <template x-if="result">
          <div class="flex flex-col items-center mt-4">
            <div class="mb-4">
              <svg width="80" height="80" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="#10b981" stroke-width="4"/></svg>
            </div>
            <h3 class="text-xl font-bold mb-2 text-center" x-text="result.title"></h3>
            <p class="text-[#6b7280] mb-1" x-text="'by ' + result.author"></p>
            <p class="text-[#4f46e5] font-medium mb-1" x-text="result.category"></p>
            <a :href="'book_details.php?id=' + result.id" class="mt-4 px-6 py-2 rounded-full bg-[#4f46e5] text-white font-bold shadow-lg hover:bg-[#0ea5e9] transition">Go to Book</a>
            <button @click="resetRoulette()" class="mt-2 text-sm text-[#4f46e5] underline">Try Again</button>
            <div x-ref="confetti"></div>
          </div>
        </template>
      </div>
    </div>
    <!-- Character Suggestions Modal -->
    <div x-data="characterSuggest()" x-show="open" @open-character.window="openModal($event.detail.character)" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" x-cloak>
      <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-lg w-full relative flex flex-col items-center">
        <button @click="open = false" class="absolute top-4 right-4 text-gray-400 hover:text-[#e11d48] text-2xl">&times;</button>
        <h2 class="text-2xl font-bold mb-4 text-center" x-text="title"></h2>
        <template x-if="loading">
          <div class="flex flex-col items-center justify-center py-8">
            <div class="w-12 h-12 border-4 border-[#4f46e5] border-t-[#0ea5e9] rounded-full animate-spin mb-4"></div>
            <span class="text-[#6b7280]">Finding books...</span>
          </div>
        </template>
        <template x-if="!loading && books.length">
          <div class="w-full">
            <ul class="divide-y divide-[#e0e7ff]">
              <template x-for="book in books" :key="book.id">
                <li class="py-4 flex flex-col md:flex-row md:items-center md:gap-4">
                  <span class="font-semibold text-[#4f46e5]" x-text="book.title"></span>
                  <span class="text-[#6b7280]">by <span x-text="book.author"></span></span>
                  <a :href="'book_details.php?id=' + book.id" class="ml-auto mt-2 md:mt-0 px-4 py-2 rounded-full bg-[#4f46e5] text-white font-bold shadow hover:bg-[#0ea5e9] transition text-sm">View</a>
                </li>
              </template>
            </ul>
          </div>
        </template>
        <template x-if="!loading && !books.length">
          <div class="flex flex-col items-center justify-center py-8">
            <span class="text-[#6b7280]">No books found for this character right now.</span>
          </div>
        </template>
      </div>
    </div>
    <!-- Modern Collapsible LibraryX Chatbot Widget -->
    <div id="chatbox-collapsed" onclick="openChatbox()" style="display:block;">
      <svg width="32" height="32" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="12" fill="#fff"/><path d="M7 10h10M7 14h6" stroke="#4f46e5" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <div id="chatbox-expanded" style="display:none;">
      <div id="chatbox-header">
        <span>💬 LibraryX Chatbot</span>
        <button id="chatbox-close" onclick="closeChatbox()">&times;</button>
      </div>
      <div id="chatlog"></div>
      <div style="display:flex;align-items:center;margin-bottom:16px;">
        <input id="usermsg" type="text" placeholder="Ask LibraryX..." onkeydown="if(event.key==='Enter'){sendMsg()}" />
        <button id="sendbtn" onclick="sendMsg()">Send</button>
      </div>
    </div>
    <script src="script.js"></script>
    <script>
    function borrowRoulette() {
      return {
        open: false,
        spinning: false,
        result: null,
        books: [],
        displayBooks: [],
        offset: 0,
        transitionStyle: 'transform 0.2s cubic-bezier(0.4,2,0.2,1)',
        centerIndex: 0,
        openRoulette() {
          this.open = true;
          this.spinning = false;
          this.result = null;
          this.books = [];
          this.displayBooks = [];
          this.offset = 0;
          this.centerIndex = 0;
          this.transitionStyle = 'none';
        },
        async spinRoulette() {
          this.spinning = true;
          this.result = null;
          // Fetch random books and winner
          const res = await fetch('random_book.php');
          const data = await res.json();
          if (data.error) {
            this.books = [{title: 'No books found', author: '', category: '', id: 0}];
            this.displayBooks = this.books;
            this.spinning = false;
            return;
          }
          this.books = data.books;
          // Repeat the list for a longer spin
          let repeatCount = 8;
          this.displayBooks = Array(repeatCount).fill(this.books).flat();
          // Find winner index in the repeated list (land in the center)
          let winnerIdx = this.books.findIndex(b => b.id == data.winner.id);
          let totalItems = this.displayBooks.length;
          let itemHeight = 48; // px (h-12)
          let center = Math.floor((this.displayBooks.length / 2));
          let finalIdx = center + winnerIdx;
          let finalOffset = -(finalIdx * itemHeight);
          this.offset = 0;
          this.centerIndex = center;
          this.transitionStyle = 'none';
          // Animate in steps (tick effect)
          let steps = finalIdx;
          let step = 0;
          let tick = () => {
            if (step < steps) {
              this.transitionStyle = 'transform 0.12s cubic-bezier(0.4,2,0.2,1)';
              this.offset = -(step * itemHeight);
              this.centerIndex = center - (step - steps);
              step++;
              setTimeout(tick, Math.max(40, 180 - step * 3));
      } else {
              // Slow final move
              this.transitionStyle = 'transform 1.2s cubic-bezier(0.2,1,0.2,1)';
              this.offset = finalOffset;
              this.centerIndex = finalIdx;
              setTimeout(() => {
                this.result = data.winner;
                this.spinning = false;
                this.centerIndex = finalIdx;
                this.confettiBurst();
              }, 1200);
            }
          };
          setTimeout(tick, 200);
        },
        resetRoulette() {
          this.spinning = false;
          this.result = null;
          this.books = [];
          this.displayBooks = [];
          this.offset = 0;
          this.centerIndex = 0;
          this.transitionStyle = 'none';
        },
        confettiBurst() {
          // Simple confetti burst using emoji
          const el = this.$refs.confetti;
          if (!el) return;
          el.innerHTML = '';
          for (let i = 0; i < 18; i++) {
            const span = document.createElement('span');
            span.textContent = ['🎉','✨','🎊','💥','⭐️'][Math.floor(Math.random()*5)];
            span.style.position = 'absolute';
            span.style.left = (40 + Math.random()*80) + 'px';
            span.style.top = '0px';
            span.style.fontSize = '2rem';
            span.style.opacity = 1;
            span.style.transition = 'all 1.2s cubic-bezier(0.2,1,0.2,1)';
            el.appendChild(span);
            setTimeout(() => {
              span.style.transform = `translate(${(Math.random()-0.5)*120}px, ${60+Math.random()*60}px) rotate(${Math.random()*360}deg)`;
              span.style.opacity = 0;
            }, 10);
            setTimeout(() => span.remove(), 1400);
          }
        }
      }
    }
    function characterSuggest() {
      return {
        open: false,
        loading: false,
        books: [],
        title: '',
        async openModal(character) {
          this.open = true;
          this.loading = true;
          this.books = [];
          this.title = {
            'sherlock': 'Mystery Picks for Sherlock Holmes',
            'elon': 'Tech & Biography Picks for Elon Musk',
            'chanakya': 'Philosophy Picks for Chanakya',
            'hermione': 'Fantasy & Adventure Picks for Hermione',
            'curie': 'Science Picks for Marie Curie',
          }[character] || 'Book Picks';
          const res = await fetch('character_books.php?character=' + character);
          this.books = await res.json();
          this.loading = false;
        }
      }
    }
    function openChatbox() {
      document.getElementById('chatbox-collapsed').style.display = 'none';
      document.getElementById('chatbox-expanded').style.display = 'flex';
    }
    function closeChatbox() {
      document.getElementById('chatbox-expanded').style.display = 'none';
      document.getElementById('chatbox-collapsed').style.display = 'flex';
    }
    function sendMsg() {
      var msg = document.getElementById('usermsg').value;
      if (!msg) return;
      var chatlog = document.getElementById('chatlog');
      chatlog.innerHTML += "<div><b>You:</b> " + msg + "</div>";
      document.getElementById('usermsg').value = '';
      fetch('http://localhost:5005/chat', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({message: msg})
      })
      .then(res => res.json())
      .then(data => {
        chatlog.innerHTML += "<div><b>Bot:</b> " + data.reply + "</div>";
        chatlog.scrollTop = chatlog.scrollHeight;
    });
    }
    </script>
</body>
</html> 