<?php
require_once 'config.php';

// Get only currently borrowed books (where return_date is NULL)
$sql = "SELECT b.*, c.name as category_name, bb.borrow_date, bb.borrower_name, bb.due_date,
        DATEDIFF(CURRENT_DATE, bb.due_date) as days_overdue
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.id 
    JOIN borrowed_books bb ON b.id = bb.book_id 
    WHERE b.status = 'borrowed' 
    AND bb.return_date IS NULL
    ORDER BY bb.due_date ASC";
$result = $conn->query($sql);
$borrowed_books = [];
$overdue_count = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (strtotime($row['due_date']) < time()) {
            $overdue_count++;
        }
        $borrowed_books[] = $row;
    }
}

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
    <title>Borrowed Books - LibraryX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="script.js" defer></script>
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
                <a href="borrowed.php" class="text-lg font-semibold text-[#4f46e5] border-b-2 border-[#4f46e5] pb-1">
                    Borrowed Books
                    <?php if ($overdue_count > 0): ?>
                        <span class="ml-2 inline-block bg-[#e11d48] text-white px-2 py-1 rounded-full text-xs font-bold animate-pulse"><?php echo $overdue_count; ?> overdue</span>
                    <?php endif; ?>
                </a>
                <a href="history.php" class="text-lg font-medium text-[#1f2937] hover:text-[#4f46e5]">Borrowing History</a>
                <a href="wishlist.php" class="text-lg font-medium text-[#1f2937] hover:text-[#4f46e5]">Wishlist</a>
                <a href="characters.php" class="text-lg font-medium text-gray-700 hover:text-primary">Characters</a>
            </div>
        </nav>
        <main>
            <h2 class="text-2xl md:text-3xl font-bold text-[#1f2937] mb-6 border-b border-[#e0e7ff] pb-2 drop-shadow-lg">Borrowed Books</h2>
            <?php if ($overdue_count > 0): ?>
                <div class="mb-6 px-6 py-3 rounded-lg bg-[#fee2e2] text-[#b91c1c] font-semibold text-lg shadow animate-pulse">
                    You have <?php echo $overdue_count; ?> overdue book<?php echo $overdue_count > 1 ? 's' : ''; ?>. Please return them as soon as possible.
                </div>
            <?php endif; ?>
            <div class="flex flex-col gap-8">
                <?php if (empty($borrowed_books)): ?>
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
                      <p class="mt-6 text-lg text-[#6b7280]">No books are currently borrowed.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($borrowed_books as $book): ?>
                        <?php 
                            $is_overdue = strtotime($book['due_date']) < time();
                            $days_overdue = $is_overdue ? floor((time() - strtotime($book['due_date'])) / (60 * 60 * 24)) : 0;
                            $is_warning = !$is_overdue && strtotime($book['due_date']) - time() < 3 * 24 * 60 * 60; // 3 days warning
                        ?>
                        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-xl mx-auto w-full flex flex-col gap-2 <?php echo $is_overdue ? 'border-l-8 border-[#e11d48] bg-[#fff8f8]' : ''; ?>">
                            <span class="inline-block mb-2 px-3 py-1 rounded-full text-xs font-bold <?php
                                if ($book['type'] == 'book') echo 'bg-[#e0e7ff] text-[#4f46e5]';
                                elseif ($book['type'] == 'article') echo 'bg-[#fef9c3] text-[#b45309]';
                                elseif ($book['type'] == 'magazine') echo 'bg-[#fce7f3] text-[#be185d]';
                                elseif ($book['type'] == 'research_paper') echo 'bg-[#d1fae5] text-[#047857]';
                            ?>">
                                <?php echo $type_icons[$book['type'] ?? 'book'] ?? ucfirst($book['type'] ?? 'book'); ?>
                            </span>
                            <h3 class="text-xl font-bold text-[#1f2937] mb-1 font-poppins flex items-center gap-2">
                                <a href="book_details.php?id=<?php echo $book['id']; ?>" class="hover:text-[#4f46e5] transition underline underline-offset-4 decoration-[#0ea5e9]/60"><?php echo htmlspecialchars($book['title']); ?></a>
                                <?php if ($is_overdue): ?>
                                    <span class="inline-block bg-[#e11d48] text-white px-3 py-1 rounded-full text-xs font-bold animate-pulse">Overdue</span>
                                <?php endif; ?>
                            </h3>
                            <p class="italic text-[#6b7280] mb-1">by <?php echo htmlspecialchars($book['author']); ?></p>
                            <p class="text-[#4f46e5] font-medium mb-1">📚 <?php echo htmlspecialchars($book['category_name']); ?></p>
                            <p class="text-[#6b7280] text-sm mb-2">ISBN: <?php echo !empty($book['isbn']) ? htmlspecialchars($book['isbn']) : 'N/A'; ?></p>
                            <p class="text-[#6b7280] text-sm mb-2">Borrowed by: <?php echo htmlspecialchars($book['borrower_name']); ?></p>
                            <p class="text-[#6b7280] text-sm mb-2">Borrowed on: <?php echo date('Y-m-d H:i', strtotime($book['borrow_date'])); ?></p>
                            <p class="text-[#6b7280] text-sm mb-2">
                                Due date: 
                                <?php 
                                    if ($is_overdue) {
                                        echo '<span class="text-[#e11d48] font-bold">' . date('Y-m-d', strtotime($book['due_date'])) . '</span>';
                                        echo '<span class="ml-2 text-[#e11d48] font-semibold">Overdue by ' . $days_overdue . ' day' . ($days_overdue != 1 ? 's' : '') . '</span>';
                                    } elseif ($is_warning) {
                                        echo '<span class="text-[#f59e42] font-bold">' . date('Y-m-d', strtotime($book['due_date'])) . ' (Due soon)</span>';
                                    } else {
                                        echo '<span class="text-[#10b981] font-bold">' . date('Y-m-d', strtotime($book['due_date'])) . '</span>';
                                    }
                                ?>
                            </p>
                            <button class="return-btn px-6 py-2 rounded-lg bg-[#4f46e5] text-white font-bold shadow-lg hover:bg-[#0ea5e9] transition mt-2 w-full" data-book-id="<?php echo $book['id']; ?>">Return Book</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 