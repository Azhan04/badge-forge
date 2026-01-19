<?php
session_start();
include 'db.php';

// Include PHPMailer via Composer
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message_content = trim($_POST['message']);
    
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message_content)) {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'azhanmohammed04@gmail.com'; // Your Gmail address
                $mail->Password   = 'aich vawa thvy gutb';   // Your App Password (16 characters)
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->Timeout    = 30; // Increase timeout

                // Recipients
                $mail->setFrom($email, $name);
                $mail->addAddress('azhanmohammed04@gmail.com'); // Your Gmail address (where you want to receive)

                // Content
                $mail->isHTML(false);
                $mail->Subject = "Contact Form Submission: $subject";
                $mail->Body    = "Name: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message_content\n\nSent from: College FAQ System";

                $mail->send();
                $message = 'Your message has been sent successfully! We will get back to you soon.';
                // Clear form fields after successful submission
                $name = '';
                $email = '';
                $subject = '';
                $message_content = '';
            } catch (Exception $e) {
                $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                
                // Additional debugging info
                if ($mail->SMTPDebug > 0) {
                    $error .= "\nSMTP Debug Output: " . $mail->smtp_debug_output;
                }
            }
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - College FAQ System</title>
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
                        <a href="contact.php" class="text-white bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all">Contact</a>
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
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Contact Us</h1>
            <p class="text-xl text-gray-600">Have questions or need assistance? Fill out the form below and we'll get back to you as soon as possible.</p>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg mb-8">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg mb-8">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-md p-8">
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="name">
                            Full Name *
                        </label>
                        <input type="text" name="name" id="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                               required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="email">
                            Email Address *
                        </label>
                        <input type="email" name="email" id="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                               required>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="subject">
                        Subject *
                    </label>
                    <input type="text" name="subject" id="subject" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="message">
                        Message *
                    </label>
                    <textarea name="message" id="message" rows="6" 
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                              required><?php echo isset($message_content) ? htmlspecialchars($message_content) : ''; ?></textarea>
                </div>
                
                <div class="pt-4">
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 px-4 rounded-lg font-medium hover:from-blue-700 hover:to-indigo-700 transition-all transform hover:scale-105">
                        <i class="fas fa-paper-plane mr-2"></i>Send Message
                    </button>
                </div>
            </form>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="bg-blue-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-envelope text-blue-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Email</h3>
                <p class="text-gray-600">support@college.edu</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="bg-blue-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-phone text-blue-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Phone</h3>
                <p class="text-gray-600">+1 (555) 123-4567</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="bg-blue-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-map-marker-alt text-blue-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Location</h3>
                <p class="text-gray-600">123 College Street, City, State</p>
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
    </script>
</body>
</html>