<?php
require_once 'config.php';

// Get all borrowing history
$sql = "SELECT bb.*, b.title, b.author, b.isbn, c.name as category 
        FROM borrowed_books bb 
        JOIN books b ON bb.book_id = b.id 
        JOIN categories c ON b.category_id = c.id 
        ORDER BY bb.borrow_date DESC";
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
    <title>Borrowing History - LibraryX</title>
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
            <h2 class="text-2xl md:text-3xl font-bold text-[#1f2937] mb-6 border-b border-[#e0e7ff] pb-2 drop-shadow-lg">Borrowing History</h2>
            <?php if ($result->num_rows > 0): ?>
                <div class="flex flex-col gap-8">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="bg-white rounded-2xl shadow-2xl p-8 flex flex-col md:flex-row md:items-center md:gap-8">
                            <div class="flex-1">
                                <?php
                                $type = $row['type'] ?? 'book';
                                ?>
                                <span class="inline-block mb-2 px-3 py-1 rounded-full text-xs font-bold <?php
                                    if ($type == 'book') echo 'bg-[#e0e7ff] text-[#4f46e5]';
                                    elseif ($type == 'article') echo 'bg-[#fef9c3] text-[#b45309]';
                                    elseif ($type == 'magazine') echo 'bg-[#fce7f3] text-[#be185d]';
                                    elseif ($type == 'research_paper') echo 'bg-[#d1fae5] text-[#047857]';
                                ?>">
                                    <?php echo $type_icons[$type] ?? ucfirst($type); ?>
                                </span>
                                <h3 class="text-xl font-bold text-[#1f2937] mb-1 font-poppins"><?php echo htmlspecialchars($row['title']); ?></h3>
                                <p class="italic text-[#6b7280] mb-1">by <?php echo htmlspecialchars($row['author']); ?></p>
                                <p class="text-[#4f46e5] font-medium mb-1">📚 <?php echo htmlspecialchars($row['category']); ?></p>
                                <p class="text-[#6b7280] text-sm mb-2">ISBN: <?php echo !empty($row['isbn']) ? htmlspecialchars($row['isbn']) : 'N/A'; ?></p>
                            </div>
                            <div class="flex-1">
                                <p class="text-[#6b7280] text-sm mb-2"><strong>Borrower:</strong> <?php echo htmlspecialchars($row['borrower_name']); ?></p>
                                <p class="text-[#6b7280] text-sm mb-2"><strong>Borrowed:</strong> <?php echo date('F j, Y', strtotime($row['borrow_date'])); ?></p>
                                <?php if ($row['due_date']): ?>
                                    <p class="text-[#6b7280] text-sm mb-2"><strong>Due:</strong> <?php echo date('F j, Y', strtotime($row['due_date'])); ?></p>
                                <?php endif; ?>
                                <?php if ($row['return_date']): ?>
                                    <p class="text-[#10b981] text-sm font-bold mb-2"><strong>Returned:</strong> <?php echo date('F j, Y', strtotime($row['return_date'])); ?></p>
                                <?php else: ?>
                                    <p class="text-[#4f46e5] text-sm font-bold mb-2">Currently Borrowed</p>
                                <?php endif; ?>
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
                  <p class="mt-6 text-lg text-[#6b7280]">No borrowing history found.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html> 