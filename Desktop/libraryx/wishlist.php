<?php
require_once 'config.php';

// Handle adding to wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_wishlist'])) {
    $book_id = $_POST['book_id'];
    $borrower_name = $_POST['borrower_name'];
    
    $sql = "INSERT INTO wishlist (book_id, borrower_name) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $book_id, $borrower_name);
    $stmt->execute();
}

// Handle removing from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_wishlist'])) {
    $wishlist_id = $_POST['wishlist_id'];
    
    $sql = "DELETE FROM wishlist WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $wishlist_id);
    $stmt->execute();
}

// Get wishlist items
$sql = "SELECT w.*, b.title, b.author, b.isbn, b.status, c.name as category, b.type 
        FROM wishlist w 
        JOIN books b ON w.book_id = b.id 
        JOIN categories c ON b.category_id = c.id 
        ORDER BY w.added_date DESC";
$result = $conn->query($sql);

$type_icons = [
    'book' => '📚 Book',
    'article' => '📰 Article',
    'magazine' => '🗞️ Magazine',
    'research_paper' => '📄 Research'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - LibraryX</title>
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
                  <div class="text-xs text-gray-500 font-semibold mt-1">Welcome, Guest!</div>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <a href="index.php" class="text-lg font-medium text-[#1f2937] hover:text-[#4f46e5]">Home</a>
                <a href="borrowed.php" class="text-lg font-medium text-[#1f2937] hover:text-[#4f46e5]">Borrowed Books</a>
                <a href="history.php" class="text-lg font-medium text-[#1f2937] hover:text-[#4f46e5]">Borrowing History</a>
                <a href="wishlist.php" class="text-lg font-semibold text-[#4f46e5] border-b-2 border-[#4f46e5] pb-1">Wishlist</a>
                <a href="characters.php" class="text-lg font-medium text-gray-700 hover:text-primary">Characters</a>
            </div>
        </nav>
        <main>
            <h2 class="text-2xl md:text-3xl font-bold text-[#1f2937] mb-6 border-b border-[#e0e7ff] pb-2 drop-shadow-lg">My Wishlist</h2>
            <?php if ($result->num_rows > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition transform hover:-translate-y-2 hover:scale-105 p-6 flex flex-col animate-fade-in-up relative overflow-hidden group">
                            <div class="mb-4">
                                <span class="inline-block mb-2 px-3 py-1 rounded-full text-xs font-bold <?php
                                    if ($row['type'] == 'book') echo 'bg-[#e0e7ff] text-[#4f46e5]';
                                    elseif ($row['type'] == 'article') echo 'bg-[#fef9c3] text-[#b45309]';
                                    elseif ($row['type'] == 'magazine') echo 'bg-[#fce7f3] text-[#be185d]';
                                    elseif ($row['type'] == 'research_paper') echo 'bg-[#d1fae5] text-[#047857]';
                                ?>">
                                    <?php echo $type_icons[$row['type'] ?? 'book'] ?? ucfirst($row['type'] ?? 'book'); ?>
                                </span>
                                <h3 class="text-xl font-bold text-[#1f2937] mb-1 font-poppins"><a href="book_details.php?id=<?php echo $row['book_id']; ?>" class="hover:text-[#4f46e5] transition underline underline-offset-4 decoration-[#0ea5e9]/60"><?php echo htmlspecialchars($row['title']); ?></a></h3>
                                <p class="italic text-[#6b7280] mb-1">by <?php echo htmlspecialchars($row['author']); ?></p>
                                <p class="text-[#4f46e5] font-medium mb-1">📚 <?php echo htmlspecialchars($row['category']); ?></p>
                                <p class="text-[#6b7280] text-sm mb-2">ISBN: <?php echo !empty($row['isbn']) ? htmlspecialchars($row['isbn']) : 'N/A'; ?></p>
                                <p class="inline-block bg-[#10b981]/10 text-[#10b981] rounded-full px-4 py-1 text-sm font-semibold mb-2"><?php echo ucfirst($row['status']); ?></p>
                            </div>
                            <div class="mt-auto flex flex-col gap-2">
                                <?php if ($row['status'] === 'available'): ?>
                                    <form action="index.php" method="post" class="flex flex-col gap-2">
                                        <input type="hidden" name="book_id" value="<?php echo $row['book_id']; ?>">
                                        <input type="text" name="borrower_name" placeholder="Your Name" required class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition" />
                                        <button type="submit" name="borrow_book" class="px-4 py-2 rounded-lg bg-[#4f46e5] text-white font-bold shadow-lg hover:bg-[#0ea5e9] transition flex items-center justify-center gap-2 group focus:ring-2 focus:ring-[#4f46e5]">Borrow Now</button>
                                    </form>
                                <?php endif; ?>
                                <form action="wishlist.php" method="post">
                                    <input type="hidden" name="wishlist_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="remove_from_wishlist" class="px-4 py-2 rounded-lg bg-[#6b7280] text-white font-bold shadow-lg hover:bg-[#4f46e5] transition flex items-center justify-center gap-2 group focus:ring-2 focus:ring-[#6b7280]">Remove from Wishlist</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-12">
                  <svg width="140" height="140" viewBox="0 0 140 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="20" y="40" width="30" height="70" rx="6" fill="#4f46e5"/>
                    <rect x="50" y="30" width="30" height="80" rx="6" fill="#0ea5e9"/>
                    <rect x="80" y="50" width="30" height="60" rx="6" fill="#a5b4fc"/>
                    <rect x="35" y="60" width="70" height="10" rx="3" fill="#10b981"/>
                    <rect x="35" y="80" width="70" height="10" rx="3" fill="#6b7280"/>
                    <rect x="35" y="100" width="70" height="10" rx="3" fill="#e0e7ff"/>
                    <rect x="35" y="120" width="70" height="6" rx="3" fill="#1f2937"/>
                  </svg>
                  <p class="mt-6 text-lg text-[#6b7280]">Your wishlist is empty.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html> 