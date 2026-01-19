<?php
// Redirect to login page if not logged in
session_start();
include 'db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get categories for filter
$pdo = getConnection();
$stmt = $pdo->query("SELECT * FROM categories ORDER BY cat_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get 5 FAQs from different categories (random selection) - Fixed query
$cat_ids_stmt = $pdo->query("
    SELECT DISTINCT cat_id 
    FROM faqs 
    GROUP BY cat_id 
    ORDER BY RAND() 
    LIMIT 5
");
$cat_ids = $cat_ids_stmt->fetchAll(PDO::FETCH_COLUMN);
$cat_ids_str = implode(',', $cat_ids);

$faq_query = "SELECT f.*, c.cat_name 
              FROM faqs f 
              LEFT JOIN categories c ON f.cat_id = c.cat_id 
              WHERE f.cat_id IN (" . $cat_ids_str . ")
              ORDER BY f.created_at DESC
              LIMIT 5";

if (empty($cat_ids)) {
    $stmt = $pdo->query("SELECT f.*, c.cat_name FROM faqs f LEFT JOIN categories c ON f.cat_id = c.cat_id ORDER BY f.created_at DESC LIMIT 5");
} else {
    $stmt = $pdo->query($faq_query);
}
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Search functionality for FAQ list page
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Pagination for search results
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Build query based on search and category - FIXED: Use table aliases
$where_clause = "WHERE f.faq_id IS NOT NULL"; // Start with a valid condition
$params = [];

if (!empty($search)) {
    $where_clause .= " AND (f.question LIKE ? OR f.answer LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category > 0) {
    $where_clause .= " AND f.cat_id = ?";
    $params[] = $category;
}

// Get total count for search results
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM faqs f LEFT JOIN categories c ON f.cat_id = c.cat_id $where_clause");
$count_stmt->execute($params);
$total_faqs = $count_stmt->fetchColumn();
$total_pages = ceil($total_faqs / $limit);

// Get search results with pagination - FIXED: Use table aliases
if (!empty($search) || $category > 0) {
    $stmt = $pdo->prepare("SELECT f.*, c.cat_name FROM faqs f LEFT JOIN categories c ON f.cat_id = c.cat_id $where_clause ORDER BY f.created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $search_faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the search
    if (!empty($search)) {
        logSearch($search, $total_faqs, $_SESSION['user_id']);
    }
} else {
    $search_faqs = [];
}

// Get popular FAQs
$popular_faqs = getPopularFAQs(5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College FAQ System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#64748b',
                        success: '#10b981',
                        danger: '#ef4444',
                        warning: '#f59e0b',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-question-circle text-white text-2xl mr-3"></i>
                    <a href="index.php" class="text-white text-xl font-bold">College FAQ</a>
                </div>
                
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="index.php" class="text-white bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all">Home</a>
                        <a href="faq_list.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all">All FAQs</a>
                        <a href="contact.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all">Contact</a>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <?php if (isLoggedIn()): ?>
                        <div class="relative">
                            <button id="dropdownButton" class="flex items-center text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all">
                                <i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            
                            <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user-circle mr-2"></i>My Profile
                                </a>
                                <a href="bookmarks.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-bookmark mr-2"></i>My Bookmarks
                                </a>
                                <?php if (isAdmin()): ?>
                                    <a href="admin_dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-cog mr-2"></i>Admin Panel
                                    </a>
                                <?php endif; ?>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-3">
                <div class="text-center mb-12">
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h1>
                    <p class="text-xl text-gray-600">Find answers to common questions about our college</p>
                </div>

                <!-- Google-like Search Bar -->
                <form method="GET" class="mb-12">
                    <div class="flex flex-col md:flex-row gap-4 max-w-4xl mx-auto">
                        <div class="flex-1 relative">
                            <div class="relative">
                                <input type="text" name="search" placeholder="Search FAQs..." value="<?php echo htmlspecialchars($search); ?>" 
                                       class="w-full px-12 py-4 text-lg border border-gray-300 rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-lg"></i>
                            </div>
                        </div>
                        <div class="w-full md:w-auto">
                            <select name="category" class="w-full md:w-auto px-4 py-4 text-lg border border-gray-300 rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['cat_id']; ?>" <?php echo $category == $cat['cat_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['cat_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>

                <!-- Search Results or Featured FAQs -->
                <?php if (!empty($search) || $category > 0): ?>
                    <!-- Search Results -->
                    <h3 class="text-2xl font-bold text-gray-900 mb-8">Search Results</h3>
                    <?php if (empty($search_faqs)): ?>
                        <div class="bg-blue-50 border border-blue-200 text-blue-700 px-6 py-4 rounded-lg mb-8 text-center">
                            <i class="fas fa-info-circle mr-2"></i>No FAQs found matching your search criteria.
                        </div>
                    <?php else: ?>
                        <div class="space-y-6 mb-8">
                            <?php foreach ($search_faqs as $faq): ?>
                                <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                                    <div class="p-6">
                                        <h5 class="text-xl font-semibold text-gray-900 mb-3 flex items-center">
                                            <i class="fas fa-question-circle text-blue-600 mr-3"></i>
                                            <?php echo htmlspecialchars($faq['question']); ?>
                                        </h5>
                                        <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($faq['answer']); ?></p>
                                        <div class="flex justify-between items-center text-sm text-gray-500">
                                            <span class="flex items-center">
                                                Category: 
                                                <span class="ml-2 bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">
                                                    <?php echo htmlspecialchars($faq['cat_name']); ?>
                                                </span>
                                            </span>
                                            <span><?php echo date('M d, Y', strtotime($faq['created_at'])); ?></span>
                                        </div>
                                        
                                        <!-- Rating and Bookmark Controls -->
                                        <div class="mt-4 flex justify-between items-center">
                                            <div class="flex space-x-4">
                                                <button onclick="rateFAQ(<?php echo $faq['faq_id']; ?>, 'like')" class="flex items-center space-x-1 text-green-600 hover:text-green-800">
                                                    <i class="fas fa-thumbs-up"></i>
                                                    <span><?php echo $faq['likes'] ?? 0; ?></span>
                                                </button>
                                                <button onclick="rateFAQ(<?php echo $faq['faq_id']; ?>, 'dislike')" class="flex items-center space-x-1 text-red-600 hover:text-red-800">
                                                    <i class="fas fa-thumbs-down"></i>
                                                    <span><?php echo $faq['dislikes'] ?? 0; ?></span>
                                                </button>
                                            </div>
                                            <button onclick="toggleBookmark(<?php echo $faq['faq_id']; ?>)" class="text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-bookmark <?php echo isBookmarked($faq['faq_id'], $_SESSION['user_id']) ? 'text-yellow-500' : ''; ?>"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav class="flex justify-center space-x-2 mb-8">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>" 
                                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all">
                                        Previous
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>" 
                                       class="px-4 py-2 <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg transition-all">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>" 
                                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Featured FAQs (5 from different categories) -->
                    <h3 class="text-2xl font-bold text-gray-900 mb-8">Featured FAQs</h3>
                    <?php if (empty($faqs)): ?>
                        <div class="bg-blue-50 border border-blue-200 text-blue-700 px-6 py-4 rounded-lg mb-8 text-center">
                            <i class="fas fa-info-circle mr-2"></i>No FAQs available.
                        </div>
                    <?php else: ?>
                        <div class="space-y-6 mb-8">
                            <?php foreach ($faqs as $faq): ?>
                                <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                                    <div class="p-6">
                                        <h5 class="text-xl font-semibold text-gray-900 mb-3 flex items-center">
                                            <i class="fas fa-question-circle text-blue-600 mr-3"></i>
                                            <?php echo htmlspecialchars($faq['question']); ?>
                                        </h5>
                                        <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($faq['answer']); ?></p>
                                        <div class="flex justify-between items-center text-sm text-gray-500">
                                            <span class="flex items-center">
                                                Category: 
                                                <span class="ml-2 bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">
                                                    <?php echo htmlspecialchars($faq['cat_name']); ?>
                                                </span>
                                            </span>
                                            <span><?php echo date('M d, Y', strtotime($faq['created_at'])); ?></span>
                                        </div>
                                        
                                        <!-- Rating and Bookmark Controls -->
                                        <div class="mt-4 flex justify-between items-center">
                                            <div class="flex space-x-4">
                                                <button onclick="rateFAQ(<?php echo $faq['faq_id']; ?>, 'like')" class="flex items-center space-x-1 text-green-600 hover:text-green-800">
                                                    <i class="fas fa-thumbs-up"></i>
                                                    <span><?php echo $faq['likes'] ?? 0; ?></span>
                                                </button>
                                                <button onclick="rateFAQ(<?php echo $faq['faq_id']; ?>, 'dislike')" class="flex items-center space-x-1 text-red-600 hover:text-red-800">
                                                    <i class="fas fa-thumbs-down"></i>
                                                    <span><?php echo $faq['dislikes'] ?? 0; ?></span>
                                                </button>
                                            </div>
                                            <button onclick="toggleBookmark(<?php echo $faq['faq_id']; ?>)" class="text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-bookmark <?php echo isBookmarked($faq['faq_id'], $_SESSION['user_id']) ? 'text-yellow-500' : ''; ?>"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Popular FAQs -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h5 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-fire mr-2 text-orange-500"></i>
                        Popular FAQs
                    </h5>
                    <div class="space-y-3">
                        <?php foreach ($popular_faqs as $popular_faq): ?>
                            <div class="flex items-start space-x-2">
                                <i class="fas fa-star text-yellow-500 mt-1"></i>
                                <a href="#faq-<?php echo $popular_faq['faq_id']; ?>" class="text-sm text-gray-700 hover:text-blue-600 truncate flex-1">
                                    <?php echo htmlspecialchars(substr($popular_faq['question'], 0, 60)) . (strlen($popular_faq['question']) > 60 ? '...' : ''); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h5 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-chart-bar mr-2 text-blue-500"></i>
                        Quick Stats
                    </h5>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total FAQs:</span>
                            <span class="font-medium"><?php echo $pdo->query("SELECT COUNT(*) FROM faqs")->fetchColumn(); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Categories:</span>
                            <span class="font-medium"><?php echo $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Searches Today:</span>
                            <span class="font-medium"><?php echo $pdo->query("SELECT COUNT(*) FROM search_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn(); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dropdown functionality
        const dropdownButton = document.getElementById('dropdownButton');
        const dropdownMenu = document.getElementById('dropdownMenu');
        
        dropdownButton.addEventListener('click', function(e) {
            e.preventDefault();
            dropdownMenu.classList.toggle('hidden');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.add('hidden');
            }
        });

        // Rating function
        function rateFAQ(faqId, rating) {
            fetch('rate_faq.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `faq_id=${faqId}&rating=${rating}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload to update ratings
                } else {
                    alert('Error rating FAQ: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error rating FAQ');
            });
        }

        // Bookmark function
        function toggleBookmark(faqId) {
            fetch('toggle_bookmark.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `faq_id=${faqId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload to update bookmark status
                } else {
                    alert('Error toggling bookmark: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error toggling bookmark');
            });
        }
    </script>
</body>
</html>