<?php
session_start();
require_once 'config.php';

// Handle adding to wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_wishlist'])) {
    $book_id = $_POST['book_id'];
    $borrower_name = $_SESSION['full_name'];
    
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
$sql = "SELECT w.*, b.title, b.author, b.isbn, b.status, c.name as category, b.type, b.available_copies, b.copies 
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_book'])) {
    $book_id = $_POST['book_id'];
    $borrower_name = $_SESSION['full_name'];
    $due_date = date('Y-m-d H:i:s', strtotime('+7 days'));
}

// Fetch borrowing history for this user
$history_sql = "SELECT b.title, b.author, bb.borrow_date, bb.due_date, bb.return_date
                FROM borrowed_books bb
                JOIN books b ON bb.book_id = b.id
                WHERE bb.borrower_name = ?
                ORDER BY bb.borrow_date DESC";
$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("s", $_SESSION['full_name']);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
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
        <?php include 'navbar.php'; ?>
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
                                <p class="text-[#6b7280] text-sm mb-2">Available copies: <?php echo $row['available_copies']; ?> / <?php echo $row['copies']; ?></p>
                                <p class="inline-block bg-[#10b981]/10 text-[#10b981] rounded-full px-4 py-1 text-sm font-semibold mb-2"><?php echo ucfirst($row['status']); ?></p>
                            </div>
                            <div class="mt-auto flex flex-col gap-2">
                                <?php if ($row['status'] === 'available'): ?>
                                    <form action="index.php" method="post" class="flex flex-col gap-2">
                                        <input type="hidden" name="book_id" value="<?php echo $row['book_id']; ?>">
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

            <!-- Modern Borrowing History Section -->
            <div class="mt-12 mb-8">
              <h2 class="text-2xl font-bold text-[#4f46e5] mb-4 flex items-center gap-2"><svg class="w-6 h-6 inline-block text-[#4f46e5]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/></svg> Borrowing History</h2>
              <?php if ($history_result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                  <table class="min-w-full bg-white rounded-xl shadow-md">
                    <thead>
                      <tr class="bg-[#e0e7ff] text-[#4f46e5]">
                        <th class="py-3 px-4 text-left">Title</th>
                        <th class="py-3 px-4 text-left">Author</th>
                        <th class="py-3 px-4 text-left">Borrowed</th>
                        <th class="py-3 px-4 text-left">Due</th>
                        <th class="py-3 px-4 text-left">Returned</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while($h = $history_result->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-[#f8fafc] transition">
                          <td class="py-2 px-4 font-semibold"><?php echo htmlspecialchars($h['title']); ?></td>
                          <td class="py-2 px-4"><?php echo htmlspecialchars($h['author']); ?></td>
                          <td class="py-2 px-4"><?php echo date('M d, Y', strtotime($h['borrow_date'])); ?></td>
                          <td class="py-2 px-4"><?php echo $h['due_date'] ? date('M d, Y', strtotime($h['due_date'])) : '-'; ?></td>
                          <td class="py-2 px-4">
                            <?php if ($h['return_date']): ?>
                              <span class="inline-flex items-center gap-1 text-green-600 font-bold"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> <?php echo date('M d, Y', strtotime($h['return_date'])); ?></span>
                            <?php else: ?>
                              <span class="inline-flex items-center gap-1 text-red-500 font-bold"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg> Not returned</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="text-gray-500 text-center py-8">No borrowing history yet.</div>
              <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html> 