<?php
session_start();
include 'db.php';
requireLogin();

$bookmarks = getUserBookmarks($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookmarks - College FAQ System</title>
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
                        <a href="index.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all">Home</a>
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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4 flex items-center justify-center">
                <i class="fas fa-bookmark text-yellow-500 mr-3"></i>
                My Bookmarked FAQs
            </h1>
            <p class="text-xl text-gray-600">Your saved frequently asked questions</p>
        </div>

        <?php if (empty($bookmarks)): ?>
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-6 py-8 rounded-lg text-center">
                <i class="fas fa-bookmark text-4xl text-blue-400 mb-4"></i>
                <p class="text-lg">You haven't bookmarked any FAQs yet.</p>
                <p class="mt-2">Start browsing FAQs and click the bookmark icon to save them here.</p>
                <a href="faq_list.php" class="inline-block mt-4 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-all">
                    Browse FAQs
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($bookmarks as $faq): ?>
                    <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
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
                                </div>
                                <button onclick="removeBookmark(<?php echo $faq['faq_id']; ?>)" class="ml-4 text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            
                            <!-- Rating Controls -->
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
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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

        // Remove bookmark function
        function removeBookmark(faqId) {
            if (confirm('Are you sure you want to remove this bookmark?')) {
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
                        alert('Error removing bookmark: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing bookmark');
                });
            }
        }
    </script>
</body>
</html>