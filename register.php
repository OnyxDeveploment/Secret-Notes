<?php
session_start();
require 'db.php';

define('MAX_REGISTER_ATTEMPTS', 5);
define('LOCKOUT_TIME', 300);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    if (isset($_SESSION['register_attempts']) && $_SESSION['register_attempts'] >= MAX_REGISTER_ATTEMPTS) {
        $last_attempt_time = $_SESSION['last_register_time'] ?? 0;
        $time_since_last_attempt = time() - $last_attempt_time;

        if ($time_since_last_attempt < LOCKOUT_TIME) {
            $error = "Too many attempts. Try again after " . (LOCKOUT_TIME - $time_since_last_attempt) . " seconds.";
        } else {
            $_SESSION['register_attempts'] = 0;
        }
    }

    if (empty($error)) {
        $username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
        $password = trim($_POST['password']);

        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $error = "Username must be 3-20 characters and contain only letters, numbers, and underscores.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = "Username already taken. Choose another.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $hashedPassword);

                if ($stmt->execute()) {
                    $_SESSION['register_attempts'] = 0;
                    $success = "Account created successfully! <a href='login.php' class='text-blue-400 underline'>Login here</a>";
                } else {
                    $error = "Failed to register. Please try again.";
                }
            }
        }
    }

    $_SESSION['register_attempts'] = ($_SESSION['register_attempts'] ?? 0) + 1;
    $_SESSION['last_register_time'] = time();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-900 text-white font-sans min-h-screen flex items-center justify-center">

<div class="bg-gray-800 p-8 rounded-lg shadow-lg max-w-md w-full">
    <h2 class="text-3xl font-bold mb-6 text-center">Register</h2>

    <?php if (isset($error)): ?>
        <div class="bg-red-500 text-white p-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="bg-green-500 text-white p-3 rounded mb-4"><?= $success ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="mb-4">
            <label for="username" class="block text-lg mb-2">Username</label>
            <input type="text" name="username" id="username" required
                   placeholder="3-20 chars, letters, numbers, underscores"
                   class="w-full p-3 bg-gray-700 rounded text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="password" class="block text-lg mb-2">Password</label>
            <input type="password" name="password" id="password" required
                   placeholder="At least 8 characters"
                   class="w-full p-3 bg-gray-700 rounded text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">
            Register
        </button>
    </form>

    <p class="mt-4 text-center">
        Already have an account? <a href="login.php" class="text-blue-400 hover:underline">Login here</a>
    </p>
</div>

</body>

</html>
