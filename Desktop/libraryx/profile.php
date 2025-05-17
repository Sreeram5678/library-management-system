<?php
require_once 'config.php';

$name = isset($_GET['name']) ? trim($_GET['name']) : '';
$history = $wishlist = $reviews = [];

if ($name !== '') {
    // Borrowing history
    $sql = "SELECT bb.*, b.title, b.author FROM borrowed_books bb JOIN books b ON bb.book_id = b.id WHERE bb.borrower_name = ? ORDER BY bb.borrow_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $history = $stmt->get_result();

    // Wishlist
    $sql = "SELECT w.*, b.title, b.author FROM wishlist w JOIN books b ON w.book_id = b.id WHERE w.borrower_name = ? ORDER BY w.added_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $wishlist = $stmt->get_result();

    // Reviews
    $sql = "SELECT r.*, b.title FROM book_reviews r JOIN books b ON r.book_id = b.id WHERE r.reviewer_name = ? ORDER BY r.review_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $reviews = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - LibraryX</title>
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
                <a href="wishlist.php" class="text-lg font-medium text-[#1f2937] hover:text-[#4f46e5]">Wishlist</a>
                <a href="profile.php" class="text-lg font-semibold text-[#4f46e5] border-b-2 border-[#4f46e5] pb-1">Profile</a>
                <a href="characters.php" class="text-lg font-medium text-gray-700 hover:text-primary">Characters</a>
            </div>
        </nav>
        <main>
            <form class="mb-8" method="get" action="profile.php">
                <div class="flex gap-4 items-center">
                    <input type="text" name="name" placeholder="Enter your name" value="<?php echo htmlspecialchars($name); ?>" required
                           class="flex-1 px-6 py-3 rounded-xl border border-gray-200 focus:border-[#4f46e5] focus:ring-2 focus:ring-[#4f46e5]/20 outline-none transition-all duration-300 bg-white/80 backdrop-blur-sm">
                    <button type="submit" 
                            class="px-8 py-3 bg-gradient-to-r from-[#4f46e5] to-[#0ea5e9] text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-[#4f46e5]/20 transition-all duration-300 transform hover:-translate-y-0.5">
                        View Profile
                    </button>
                </div>
            </form>
            <?php if ($name !== ''): ?>
                <div class="grid gap-8">
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-8 border border-white/30">
                        <h3 class="text-2xl font-bold text-[#1f2937] mb-6 border-b border-[#e0e7ff] pb-2">Borrowing History</h3>
                        <?php if ($history && $history->num_rows > 0): ?>
                            <ul class="space-y-4">
                                <?php while($h = $history->fetch_assoc()): ?>
                                    <li class="p-4 bg-white/50 rounded-xl border border-gray-100 hover:border-[#4f46e5]/20 transition-all duration-300">
                                        <div class="font-semibold text-[#1f2937]"><?php echo htmlspecialchars($h['title']); ?></div>
                                        <div class="text-[#6b7280]">by <?php echo htmlspecialchars($h['author']); ?></div>
                                        <div class="text-sm text-[#4f46e5] mt-2">
                                            Borrowed: <?php echo date('F j, Y', strtotime($h['borrow_date'])); ?>
                                            <?php if ($h['return_date']): ?>
                                                <span class="text-[#10b981]">• Returned: <?php echo date('F j, Y', strtotime($h['return_date'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
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
                                <p class="mt-6 text-lg text-[#6b7280]">No borrowing history found.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-8 border border-white/30">
                        <h3 class="text-2xl font-bold text-[#1f2937] mb-6 border-b border-[#e0e7ff] pb-2">Wishlist</h3>
                        <?php if ($wishlist && $wishlist->num_rows > 0): ?>
                            <ul class="space-y-4">
                                <?php while($w = $wishlist->fetch_assoc()): ?>
                                    <li class="p-4 bg-white/50 rounded-xl border border-gray-100 hover:border-[#4f46e5]/20 transition-all duration-300">
                                        <div class="font-semibold text-[#1f2937]"><?php echo htmlspecialchars($w['title']); ?></div>
                                        <div class="text-[#6b7280]">by <?php echo htmlspecialchars($w['author']); ?></div>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
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
                                <p class="mt-6 text-lg text-[#6b7280]">No wishlist items found.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-8 border border-white/30">
                        <h3 class="text-2xl font-bold text-[#1f2937] mb-6 border-b border-[#e0e7ff] pb-2">Reviews</h3>
                        <?php if ($reviews && $reviews->num_rows > 0): ?>
                            <div class="space-y-6">
                                <?php while($r = $reviews->fetch_assoc()): ?>
                                    <div class="p-6 bg-white/50 rounded-xl border border-gray-100 hover:border-[#4f46e5]/20 transition-all duration-300">
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="font-semibold text-[#1f2937]"><?php echo htmlspecialchars($r['title']); ?></div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[#f59e0b] font-bold"><?php echo $r['rating']; ?>/5</span>
                                                <span class="text-[#6b7280] text-sm"><?php echo date('F j, Y', strtotime($r['review_date'])); ?></span>
                                            </div>
                                        </div>
                                        <p class="text-[#4b5563] leading-relaxed"><?php echo nl2br(htmlspecialchars($r['review'])); ?></p>
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
                                <p class="mt-6 text-lg text-[#6b7280]">No reviews found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html> 