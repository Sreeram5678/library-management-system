<?php
require_once 'config.php';

// Get overall statistics
$stats = [
    'total_books' => $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'],
    'available_books' => $conn->query("SELECT COUNT(*) as count FROM books WHERE status = 'available'")->fetch_assoc()['count'],
    'borrowed_books' => $conn->query("SELECT COUNT(*) as count FROM books WHERE status = 'borrowed'")->fetch_assoc()['count'],
    'overdue_books' => $conn->query("SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NULL AND due_date < CURRENT_DATE")->fetch_assoc()['count']
];

// Get monthly borrowing trends (last 6 months)
$monthly_trends = $conn->query("
    SELECT 
        DATE_FORMAT(borrow_date, '%Y-%m') as month,
        COUNT(*) as borrow_count,
        COUNT(CASE WHEN return_date IS NOT NULL THEN 1 END) as return_count
    FROM borrowed_books
    WHERE borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(borrow_date, '%Y-%m')
    ORDER BY month ASC
");

// Get top borrowers
$top_borrowers = $conn->query("
    SELECT 
        borrower_name,
        COUNT(*) as total_borrows,
        COUNT(CASE WHEN return_date IS NULL THEN 1 END) as current_borrows,
        COUNT(CASE WHEN return_date IS NULL AND due_date < CURRENT_DATE THEN 1 END) as overdue_books
    FROM borrowed_books
    GROUP BY borrower_name
    ORDER BY total_borrows DESC
    LIMIT 5
");

// Get popular categories
$popular_categories = $conn->query("
    SELECT c.name, COUNT(b.id) as count 
    FROM categories c 
    LEFT JOIN books b ON c.id = b.category_id 
    GROUP BY c.id 
    ORDER BY count DESC 
    LIMIT 5
");

// Get popular tags
$popular_tags = $conn->query("
    SELECT t.name, COUNT(bt.book_id) as count 
    FROM tags t 
    LEFT JOIN book_tags bt ON t.id = bt.tag_id 
    GROUP BY t.id 
    ORDER BY count DESC 
    LIMIT 5
");

// Get recent borrowing activity
$recent_activity = $conn->query("
    SELECT b.title, bb.borrower_name, bb.borrow_date, bb.due_date, bb.return_date
    FROM borrowed_books bb
    JOIN books b ON bb.book_id = b.id
    ORDER BY bb.borrow_date DESC
    LIMIT 10
");

// Get overdue books
$overdue_books = $conn->query("
    SELECT b.title, bb.borrower_name, bb.due_date,
           DATEDIFF(CURRENT_DATE, bb.due_date) as days_overdue
    FROM borrowed_books bb
    JOIN books b ON bb.book_id = b.id
    WHERE bb.return_date IS NULL AND bb.due_date < CURRENT_DATE
    ORDER BY bb.due_date ASC
");

// Category distribution over time (last 6 months)
$category_dist = $conn->query("
    SELECT DATE_FORMAT(bb.borrow_date, '%Y-%m') as month, c.name as category, COUNT(*) as count
    FROM borrowed_books bb
    JOIN books b ON bb.book_id = b.id
    JOIN categories c ON b.category_id = c.id
    WHERE bb.borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY month, c.id
    ORDER BY month ASC, c.name ASC
");
$cat_months = [];
$cat_names = [];
$cat_data = [];
while($row = $category_dist->fetch_assoc()) {
    $cat_months[$row['month']] = true;
    $cat_names[$row['category']] = true;
    $cat_data[$row['category']][$row['month']] = $row['count'];
}
$cat_months = array_keys($cat_months);
$cat_names = array_keys($cat_names);

// Active users over time (last 6 months)
$active_users = $conn->query("
    SELECT DATE_FORMAT(borrow_date, '%Y-%m') as month, COUNT(DISTINCT borrower_name) as users
    FROM borrowed_books
    WHERE borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");
$active_months = [];
$active_counts = [];
while($row = $active_users->fetch_assoc()) {
    $active_months[] = date('M Y', strtotime($row['month'] . '-01'));
    $active_counts[] = $row['users'];
}

// Book turnover rate: average borrows per book per month (last 6 months)
$turnover = $conn->query("
    SELECT COUNT(*) as borrows, COUNT(DISTINCT book_id) as books
    FROM borrowed_books
    WHERE borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
")->fetch_assoc();
$turnover_rate = $turnover['books'] > 0 ? round($turnover['borrows'] / $turnover['books'] / 6, 2) : 0;

// Add queries for new stats
$total_categories = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
$total_tags = $conn->query("SELECT COUNT(*) as count FROM tags")->fetch_assoc()['count'];
$most_popular_book = $conn->query("SELECT b.title, COUNT(bb.id) as cnt FROM books b JOIN borrowed_books bb ON b.id = bb.book_id GROUP BY b.id ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
$most_active_borrower = $conn->query("SELECT borrower_name, COUNT(*) as cnt FROM borrowed_books GROUP BY borrower_name ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
$avg_borrow_duration = $conn->query("SELECT AVG(DATEDIFF(return_date, borrow_date)) as avg_days FROM borrowed_books WHERE return_date IS NOT NULL")->fetch_assoc()['avg_days'];
$most_borrowed_category = $conn->query("SELECT c.name, COUNT(bb.id) as cnt FROM categories c JOIN books b ON c.id = b.category_id JOIN borrowed_books bb ON b.id = bb.book_id GROUP BY c.id ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
$most_overdue_book = $conn->query("SELECT b.title, DATEDIFF(CURRENT_DATE, bb.due_date) as days_overdue FROM borrowed_books bb JOIN books b ON bb.book_id = b.id WHERE bb.return_date IS NULL AND bb.due_date < CURRENT_DATE ORDER BY days_overdue DESC LIMIT 1")->fetch_assoc();
$most_wishlisted_book = $conn->query("SELECT b.title, COUNT(w.id) as cnt FROM books b JOIN wishlist w ON b.id = w.book_id GROUP BY b.id ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Statistics - LibraryX</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dark-mode.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
            text-align: center;
            transition: box-shadow 0.25s, transform 0.18s, background 0.18s;
            cursor: pointer;
            min-height: 110px;
            max-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            word-break: break-word;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: scale(1.04);
            box-shadow: 0 8px 24px rgba(0,0,0,0.18);
        }
        .stat-card h3 {
            color: #666;
            font-size: 1.05rem;
            margin-bottom: 0.3rem;
            white-space: normal;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 95%;
        }
        .stat-card .number {
            font-size: 2.1rem;
            font-weight: 700;
            word-break: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 95%;
        }
        .stat-card .number[title] {
            cursor: pointer;
        }
        .chart-container {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            margin-bottom: 2rem;
        }
        .activity-list {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-item .title {
            font-weight: 600;
            color: #2c3e50;
        }
        .activity-item .meta {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }
        .overdue-item {
            color: #c62828;
        }
        .borrowers-list {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            margin-bottom: 2rem;
        }
        .borrower-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .borrower-item:last-child {
            border-bottom: none;
        }
        .borrower-info {
            flex: 1;
        }
        .borrower-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.3rem;
        }
        .borrower-stats {
            color: #666;
            font-size: 0.9rem;
        }
        .borrower-stats span {
            margin-right: 1rem;
        }
        .borrower-stats .overdue {
            color: #c62828;
        }
        [data-theme="dark"] .stat-card,
        [data-theme="dark"] .chart-container,
        [data-theme="dark"] .activity-list,
        [data-theme="dark"] .borrowers-list {
            background: var(--card-bg);
        }
        [data-theme="dark"] .stat-card h3 {
            color: var(--text-secondary);
        }
        [data-theme="dark"] .stat-card .number {
            color: var(--text-primary);
        }
        [data-theme="dark"] .activity-item {
            border-bottom-color: var(--border-color);
        }
        [data-theme="dark"] .activity-item .title {
            color: var(--text-primary);
        }
        [data-theme="dark"] .activity-item .meta {
            color: var(--text-secondary);
        }
        [data-theme="dark"] .borrower-item {
            border-bottom-color: var(--border-color);
        }
        [data-theme="dark"] .borrower-name {
            color: var(--text-primary);
        }
        [data-theme="dark"] .borrower-stats {
            color: var(--text-secondary);
        }
        .stat-card.total {
            background: #3498db;
            color: #fff;
        }
        .stat-card.available {
            background: #2ecc71;
            color: #fff;
        }
        .stat-card.borrowed {
            background: #e67e22;
            color: #fff;
        }
        .stat-card.overdue {
            background: #e74c3c;
            color: #fff;
        }
        .stat-card.turnover {
            background: #f1c40f;
            color: #fff;
        }
        [data-theme="dark"] .stat-card.total {
            background: #217dbb;
        }
        [data-theme="dark"] .stat-card.available {
            background: #229954;
        }
        [data-theme="dark"] .stat-card.borrowed {
            background: #ba6a13;
        }
        [data-theme="dark"] .stat-card.overdue {
            background: #c0392b;
        }
        [data-theme="dark"] .stat-card.turnover {
            background: #b7950b;
        }
        .stat-card.total:hover { background: #217dbb; }
        .stat-card.available:hover { background: #229954; }
        .stat-card.borrowed:hover { background: #ba6a13; }
        .stat-card.overdue:hover { background: #c0392b; }
        .stat-card.turnover:hover { background: #b7950b; }
        [data-theme="dark"] .stat-card.total:hover { background: #176090; }
        [data-theme="dark"] .stat-card.available:hover { background: #186a3b; }
        [data-theme="dark"] .stat-card.borrowed:hover { background: #935116; }
        [data-theme="dark"] .stat-card.overdue:hover { background: #922b21; }
        [data-theme="dark"] .stat-card.turnover:hover { background: #7d6608; }
        .stats-pyramid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(2, 120px);
            gap: 1.2rem;
            justify-items: center;
            margin-bottom: 2rem;
        }
        .stats-pyramid .stat-card { width: 100%; max-width: 220px; min-width: 160px; }
        @media (max-width: 900px) {
            .stats-pyramid { grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(4, 120px); }
            .stats-pyramid .stat-card { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="nav-bar">
            <h1>LibraryX</h1>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="borrowed.php">Borrowed Books</a>
                <a href="history.php">Borrowing History</a>
                <a href="wishlist.php">Wishlist</a>
                <a href="stats.php" class="active">Statistics</a>
            </div>
        </nav>

        <main>
            <h2>Library Statistics</h2>
            
            <div class="stats-pyramid">
                <div class="stat-card total"><h3>Total Books</h3><div class="number"><?php echo $stats['total_books']; ?></div></div>
                <div class="stat-card available"><h3>Available Books</h3><div class="number"><?php echo $stats['available_books']; ?></div></div>
                <div class="stat-card borrowed"><h3>Borrowed Books</h3><div class="number"><?php echo $stats['borrowed_books']; ?></div></div>
                <div class="stat-card overdue"><h3>Overdue Books</h3><div class="number"><?php echo $stats['overdue_books']; ?></div></div>
                <div class="stat-card" style="background:#8e44ad;color:#fff;"><h3>Total Categories</h3><div class="number"><?php echo $total_categories; ?></div></div>
                <div class="stat-card" style="background:#16a085;color:#fff;"><h3>Total Tags</h3><div class="number"><?php echo $total_tags; ?></div></div>
                <div class="stat-card" style="background:#f39c12;color:#fff;"><h3>Most Popular Book</h3><div class="number" title="<?php echo htmlspecialchars($most_popular_book['title'] ?? '-'); ?>"><?php echo htmlspecialchars($most_popular_book['title'] ?? '-'); ?></div></div>
                <div class="stat-card" style="background:#6c3483;color:#fff;"><h3>Most Wishlisted Book</h3><div class="number" title="<?php echo htmlspecialchars($most_wishlisted_book['title'] ?? '-'); ?>"><?php echo htmlspecialchars($most_wishlisted_book['title'] ?? '-'); ?></div></div>
            </div>

            <div class="chart-container">
                <canvas id="monthlyTrendsChart"></canvas>
            </div>

            <div class="borrowers-list">
                <h3>Top Borrowers</h3>
                <?php while($borrower = $top_borrowers->fetch_assoc()): ?>
                    <div class="borrower-item">
                        <div class="borrower-info">
                            <div class="borrower-name"><?php echo htmlspecialchars($borrower['borrower_name']); ?></div>
                            <div class="borrower-stats">
                                <span>Total: <?php echo $borrower['total_borrows']; ?></span>
                                <span>Current: <?php echo $borrower['current_borrows']; ?></span>
                                <?php if ($borrower['overdue_books'] > 0): ?>
                                    <span class="overdue">Overdue: <?php echo $borrower['overdue_books']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>

            <div class="chart-container">
                <canvas id="tagChart"></canvas>
            </div>

            <div class="activity-list">
                <h3>Recent Activity</h3>
                <?php while($activity = $recent_activity->fetch_assoc()): ?>
                    <div class="activity-item <?php echo strtotime($activity['due_date']) < time() && !$activity['return_date'] ? 'overdue-item' : ''; ?>">
                        <div class="title"><?php echo htmlspecialchars($activity['title']); ?></div>
                        <div class="meta">
                            Borrowed by <?php echo htmlspecialchars($activity['borrower_name']); ?> on 
                            <?php echo date('Y-m-d', strtotime($activity['borrow_date'])); ?>
                            <?php if ($activity['return_date']): ?>
                                (Returned on <?php echo date('Y-m-d', strtotime($activity['return_date'])); ?>)
                            <?php else: ?>
                                (Due: <?php echo date('Y-m-d', strtotime($activity['due_date'])); ?>)
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="chart-container">
                <canvas id="categoryDistChart"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="activeUsersChart"></canvas>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
    <script src="dark-mode.js"></script>
    <script>
        // Monthly Trends Chart
        const monthlyCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [<?php 
                    $months = [];
                    $borrows = [];
                    $returns = [];
                    while($trend = $monthly_trends->fetch_assoc()) {
                        $months[] = "'" . date('M Y', strtotime($trend['month'] . '-01')) . "'";
                        $borrows[] = $trend['borrow_count'];
                        $returns[] = $trend['return_count'];
                    }
                    echo implode(',', $months);
                ?>],
                datasets: [{
                    label: 'Books Borrowed',
                    data: [<?php echo implode(',', $borrows); ?>],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Books Returned',
                    data: [<?php echo implode(',', $returns); ?>],
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Borrowing Trends'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    $labels = [];
                    $data = [];
                    while($cat = $popular_categories->fetch_assoc()) {
                        $labels[] = "'" . addslashes($cat['name']) . "'";
                        $data[] = $cat['count'];
                    }
                    echo implode(',', $labels);
                ?>],
                datasets: [{
                    label: 'Books per Category',
                    data: [<?php echo implode(',', $data); ?>],
                    backgroundColor: '#3498db',
                    borderColor: '#2980b9',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Popular Categories'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Tag Chart
        const tagCtx = document.getElementById('tagChart').getContext('2d');
        new Chart(tagCtx, {
            type: 'pie',
            data: {
                labels: [<?php 
                    $labels = [];
                    $data = [];
                    while($tag = $popular_tags->fetch_assoc()) {
                        $labels[] = "'" . addslashes($tag['name']) . "'";
                        $data[] = $tag['count'];
                    }
                    echo implode(',', $labels);
                ?>],
                datasets: [{
                    data: [<?php echo implode(',', $data); ?>],
                    backgroundColor: [
                        '#3498db',
                        '#2ecc71',
                        '#e74c3c',
                        '#f1c40f',
                        '#9b59b6'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Popular Tags'
                    }
                }
            }
        });

        // Category Distribution Over Time (Stacked Bar)
        const catDistCtx = document.getElementById('categoryDistChart').getContext('2d');
        new Chart(catDistCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($m){return date('M Y', strtotime($m.'-01'));}, $cat_months)); ?>,
                datasets: [
                    <?php foreach($cat_names as $i => $cat): ?>{
                        label: '<?php echo addslashes($cat); ?>',
                        data: [
                            <?php foreach($cat_months as $month): ?>
                                <?php echo isset($cat_data[$cat][$month]) ? $cat_data[$cat][$month] : 0; ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: '<?php echo ["#3498db","#2ecc71","#e74c3c","#f1c40f","#9b59b6","#16a085","#e67e22","#34495e"][($i)%8]; ?>',
                        stack: 'Stack 0'
                    },<?php endforeach; ?>
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Category Distribution Over Time'
                    },
                },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        // Active Users Over Time
        const activeUsersCtx = document.getElementById('activeUsersChart').getContext('2d');
        new Chart(activeUsersCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($active_months); ?>,
                datasets: [{
                    label: 'Active Users',
                    data: <?php echo json_encode($active_counts); ?>,
                    borderColor: '#8e44ad',
                    backgroundColor: 'rgba(142,68,173,0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Active Users Over Time'
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    </script>
</body>
</html> 