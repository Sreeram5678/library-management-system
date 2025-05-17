<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    die('Book ID not specified.');
}
$book_id = intval($_GET['id']);

// Get book details
$sql = "SELECT b.*, c.name as category FROM books b JOIN categories c ON b.category_id = c.id WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
if (!$book) {
    die('Book not found.');
}

// Get reviews
$sql = "SELECT * FROM book_reviews WHERE book_id = ? ORDER BY review_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$reviews = $stmt->get_result();

// Get borrowing history
$sql = "SELECT * FROM borrowed_books WHERE book_id = ? ORDER BY borrow_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$history = $stmt->get_result();

// Get recommendations
$sql = "SELECT id, title, author FROM books WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT 3";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $book['category_id'], $book_id);
$stmt->execute();
$recommendations = $stmt->get_result();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reviewer_name'], $_POST['rating'], $_POST['review'])) {
    $reviewer_name = $_POST['reviewer_name'];
    $rating = intval($_POST['rating']);
    $review = $_POST['review'];
    $sql = "INSERT INTO book_reviews (book_id, reviewer_name, rating, review) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isis", $book_id, $reviewer_name, $rating, $review);
    $stmt->execute();
    header("Location: book_details.php?id=$book_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - Details</title>
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
                <a href="characters.php" class="text-lg font-medium text-gray-700 hover:text-primary">Characters</a>
            </div>
        </nav>
        <main>
            <div class="bg-white rounded-2xl shadow-2xl p-8 flex flex-col md:flex-row gap-8 mb-8">
                <div class="flex-1">
                    <h2 class="text-2xl md:text-3xl font-bold text-[#1f2937] mb-2 font-poppins"><?php echo htmlspecialchars($book['title']); ?></h2>
                    <p class="text-[#6b7280] mb-1"><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                    <p class="text-[#6b7280] mb-1"><strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?></p>
                    <p class="text-[#6b7280] mb-1"><strong>ISBN:</strong> <?php echo !empty($book['isbn']) ? htmlspecialchars($book['isbn']) : 'N/A'; ?></p>
                    <p class="text-[#6b7280] mb-1"><strong>Description:</strong> <?php echo htmlspecialchars($book['description']); ?></p>
                    <p class="text-[#6b7280] mb-1"><strong>Status:</strong> <span class="inline-block bg-[#10b981]/10 text-[#10b981] rounded-full px-4 py-1 text-sm font-semibold"><?php echo ucfirst($book['status']); ?></span></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8">
                <h3 class="text-xl font-bold text-[#1f2937] mb-4">Reviews & Ratings</h3>
                <!-- Sentiment Legend -->
                <div class="mb-2 flex items-center gap-4 text-xs text-[#6b7280]">
                  <span><span class="mr-1">😊</span>Positive</span>
                  <span><span class="mr-1">😐</span>Neutral</span>
                  <span><span class="mr-1">😞</span>Negative</span>
                </div>
                <form method="post" class="flex flex-col md:flex-row gap-4 mb-6">
                    <input type="text" name="reviewer_name" placeholder="Your Name" required class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition" />
                    <select name="rating" required class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition">
                        <option value="">Rating</option>
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Good</option>
                        <option value="3">3 - Average</option>
                        <option value="2">2 - Poor</option>
                        <option value="1">1 - Terrible</option>
                    </select>
                    <textarea name="review" placeholder="Write your review..." required class="flex-1 px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-base focus:ring-2 focus:ring-[#4f46e5] outline-none transition"></textarea>
                    <button type="submit" class="px-6 py-2 rounded-lg bg-[#4f46e5] text-white font-bold shadow-lg hover:bg-[#0ea5e9] transition">Submit Review</button>
                </form>
                <?php if ($reviews->num_rows > 0): ?>
                    <div class="space-y-6">
                        <?php while($r = $reviews->fetch_assoc()): ?>
                            <div class="bg-[#f3f4f6] rounded-lg p-4 shadow flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <strong class="text-[#1f2937]"><?php echo htmlspecialchars($r['reviewer_name']); ?></strong>
                                    <span class="text-[#f59e42] font-bold">Rating: <?php echo $r['rating']; ?>/5</span>
                                    <span class="text-[#6b7280] text-xs ml-2"><?php echo date('F j, Y', strtotime($r['review_date'])); ?></span>
                                    <?php
                                    $sentiment_emoji = [
                                        'positive' => '😊',
                                        'neutral' => '😐',
                                        'negative' => '😞'
                                    ];
                                    ?>
                                    <?php if (!empty($r['sentiment'])): ?>
                                        <span class="ml-2 text-xs font-bold">
                                            <?php echo $sentiment_emoji[$r['sentiment']] ?? ''; ?> <?php echo ucfirst($r['sentiment']); ?> 
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-[#1f2937] mt-1"><?php echo nl2br(htmlspecialchars($r['review'])); ?></p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-8">
                      <svg width="140" height="140" viewBox="0 0 140 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="20" y="40" width="30" height="70" rx="6" fill="#4f46e5"/>
                        <rect x="50" y="30" width="30" height="80" rx="6" fill="#0ea5e9"/>
                        <rect x="80" y="50" width="30" height="60" rx="6" fill="#a5b4fc"/>
                        <rect x="35" y="60" width="70" height="10" rx="3" fill="#10b981"/>
                        <rect x="35" y="80" width="70" height="10" rx="3" fill="#6b7280"/>
                        <rect x="35" y="100" width="70" height="10" rx="3" fill="#e0e7ff"/>
                        <rect x="35" y="120" width="70" height="6" rx="3" fill="#1f2937"/>
                      </svg>
                      <p class="mt-6 text-lg text-[#6b7280]">No reviews yet. Be the first to review this book!</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8">
                <h3 class="text-xl font-bold text-[#1f2937] mb-4">Borrowing History</h3>
                <?php if ($history->num_rows > 0): ?>
                    <ul class="divide-y divide-[#e0e7ff]">
                        <?php while($h = $history->fetch_assoc()): ?>
                            <li class="py-3 flex flex-col md:flex-row md:items-center md:gap-4">
                                <span class="text-[#1f2937] font-semibold"><?php echo htmlspecialchars($h['borrower_name']); ?></span>
                                <span class="text-[#6b7280]">Borrowed on <?php echo date('F j, Y', strtotime($h['borrow_date'])); ?></span>
                                <span class="text-[#6b7280]">Due: <?php echo date('F j, Y', strtotime($h['due_date'])); ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-8">
                      <svg width="140" height="140" viewBox="0 0 140 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="20" y="40" width="30" height="70" rx="6" fill="#4f46e5"/>
                        <rect x="50" y="30" width="30" height="80" rx="6" fill="#0ea5e9"/>
                        <rect x="80" y="50" width="30" height="60" rx="6" fill="#a5b4fc"/>
                        <rect x="35" y="60" width="70" height="10" rx="3" fill="#10b981"/>
                        <rect x="35" y="80" width="70" height="10" rx="3" fill="#6b7280"/>
                        <rect x="35" y="100" width="70" height="10" rx="3" fill="#e0e7ff"/>
                        <rect x="35" y="120" width="70" height="6" rx="3" fill="#1f2937"/>
                      </svg>
                      <p class="mt-6 text-lg text-[#6b7280]">No borrowing history for this book.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8">
                <h3 class="text-xl font-bold text-[#1f2937] mb-4">Recommended Books</h3>
                <?php if ($recommendations->num_rows > 0): ?>
                    <ul class="divide-y divide-[#e0e7ff]">
                        <?php while($rec = $recommendations->fetch_assoc()): ?>
                            <li class="py-3 flex flex-col md:flex-row md:items-center md:gap-4">
                                <a href="book_details.php?id=<?php echo $rec['id']; ?>" class="text-[#4f46e5] font-semibold hover:underline"><?php echo htmlspecialchars($rec['title']); ?></a>
                                <span class="text-[#6b7280]">by <?php echo htmlspecialchars($rec['author']); ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-8">
                      <svg width="140" height="140" viewBox="0 0 140 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="20" y="40" width="30" height="70" rx="6" fill="#4f46e5"/>
                        <rect x="50" y="30" width="30" height="80" rx="6" fill="#0ea5e9"/>
                        <rect x="80" y="50" width="30" height="60" rx="6" fill="#a5b4fc"/>
                        <rect x="35" y="60" width="70" height="10" rx="3" fill="#10b981"/>
                        <rect x="35" y="80" width="70" height="10" rx="3" fill="#6b7280"/>
                        <rect x="35" y="100" width="70" height="10" rx="3" fill="#e0e7ff"/>
                        <rect x="35" y="120" width="70" height="6" rx="3" fill="#1f2937"/>
                      </svg>
                      <p class="mt-6 text-lg text-[#6b7280]">No recommendations at this time.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html> 