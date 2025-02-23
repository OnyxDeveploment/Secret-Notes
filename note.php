<?php
require 'db.php';

if (isset($_GET['id']) && ctype_xdigit($_GET['id']) && strlen($_GET['id']) === 32) {
    $note_id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT id, message, expiry_time, status, password FROM notes WHERE note_id = :note_id LIMIT 1");
    $stmt->bindParam(':note_id', $note_id, PDO::PARAM_STR);
    $stmt->execute();
    $note = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($note) {
        $current_time = new DateTime();
        $expiry_time = new DateTime($note['expiry_time']);

        if ($current_time > $expiry_time || $note['status'] == 1) {
            expireNote($note_id);
            header('Location: 404.php');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
            $enteredPassword = trim($_POST['password']);

            if (!empty($note['password']) && !password_verify($enteredPassword, $note['password'])) {
                $error = "Incorrect password. Please try again.";
            } else {
                displayNoteContent($note['message'], $note_id);
                expireNote($note_id);
                exit();
            }
        }

        if (!empty($note['password'])) {
            displayPasswordForm($note_id, $error ?? null);
        } else {
            displayNoteContent($note['message'], $note_id);
            expireNote($note_id);
        }
    } else {
        header('Location: 404.php');
        exit();
    }
} else {
    header('Location: 404.php');
    exit();
}

function expireNote($note_id)
{
    global $pdo;
    $updateStmt = $pdo->prepare("UPDATE notes SET status = 1 WHERE note_id = :note_id");
    $updateStmt->bindParam(':note_id', $note_id, PDO::PARAM_STR);
    $updateStmt->execute();
}

function displayNoteContent($message, $note_id)
{
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <title>View Note</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body class="bg-gray-900 text-white h-screen flex items-center justify-center">
        <div class="bg-gray-800 p-8 rounded-lg shadow-lg text-center max-w-2xl w-full">
            <h1 class="text-3xl font-bold mb-4">Hidden Note</h1>
            <label for="noteContent" class="block text-lg mb-2">Your Note:</label>
            <div class="w-full p-3 bg-gray-700 rounded text-white mb-4 text-left whitespace-pre-wrap"><?= $safeMessage ?></div>

            <div class="flex justify-center space-x-4">
                <a href="index.php" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded">Back to Home</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function displayPasswordForm($note_id, $error = null)
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <title>Protected Note</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body class="bg-gray-900 text-white h-screen flex items-center justify-center">
        <div class="bg-gray-800 p-8 rounded-lg shadow-lg text-center max-w-md w-full">
            <h1 class="text-3xl font-bold mb-4">🔒 Protected Note</h1>
            <form method="post" class="space-y-4">
                <label for="password" class="block text-lg">Enter Password:</label>
                <input type="password" name="password" id="password" class="w-full p-3 bg-gray-700 rounded text-white" required>
                <?php if ($error): ?>
                    <p class="text-red-500"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">Unlock Note</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>
