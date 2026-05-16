<?php
// Login Page
require_once __DIR__ . '/includes/auth.php';

// Already logged in? Redirect to dashboard
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login($username, $password)) {
        header('Location: /');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marrow C2 - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0a0a0a', 800: '#111111', 700: '#1a1a1a', 600: '#222222', 500: '#333333' },
                        accent: '#22c55e',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #111 50%, #0a0a0a 100%);
        }

        .glow-green {
            box-shadow: 0 0 30px rgba(34, 197, 94, 0.3);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center">
    <div class="bg-dark-700 border border-dark-500 rounded-3xl p-10 w-full max-w-md">
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🦴</div>
            <h1 class="text-2xl font-bold text-white">Marrow C2</h1>
            <p class="text-gray-500 text-sm mt-1">Command & Control Panel</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl mb-6 text-center text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-gray-400 text-sm mb-2">Username</label>
                <input type="text" name="username" required autofocus
                    class="w-full bg-dark-800 border border-dark-500 rounded-xl px-4 py-3 text-white focus:border-accent focus:outline-none transition-colors"
                    placeholder="Enter username">
            </div>

            <div>
                <label class="block text-gray-400 text-sm mb-2">Password</label>
                <input type="password" name="password" required
                    class="w-full bg-dark-800 border border-dark-500 rounded-xl px-4 py-3 text-white focus:border-accent focus:outline-none transition-colors"
                    placeholder="Enter password">
            </div>

            <button type="submit"
                class="w-full bg-accent hover:bg-accent/90 text-black font-bold py-3 rounded-xl transition-all glow-green">
                Login
            </button>
        </form>
    </div>
</body>

</html>