<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensaje'])) {
    $message = trim($_POST['mensaje']);
    $lifetime = isset($_POST['custom_time']) && $_POST['tiempo_vida'] === 'custom' ? (int) $_POST['custom_time'] : (int) $_POST['tiempo_vida'];
    $note_password = !empty($_POST['note_password']) ? password_hash($_POST['note_password'], PASSWORD_BCRYPT) : null;

    $note_id = bin2hex(random_bytes(16));

    if (empty($message)) {
        $error = "The note message cannot be empty.";
    } elseif (strlen($message) > 5000) {
        $error = "The note message cannot exceed 5000 characters.";
    } elseif ($lifetime <= 0) {
        $error = "Please enter a valid lifetime greater than zero.";
    } else {
        $expiry_time = date('Y-m-d H:i:s', time() + $lifetime);

        $stmt = $pdo->prepare("INSERT INTO notes (user_id, note_id, message, password, expiry_time, status) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$user_id, $note_id, $message, $note_password, $expiry_time]);

        $note_url = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/note.php?id={$note_id}";
        $success = "Note created! <a href='{$note_url}' class='text-blue-400 underline'>View Note</a>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['burn_note_id'])) {
    $burn_note_id = $_POST['burn_note_id'];

    $stmt = $pdo->prepare("UPDATE notes SET status = 1 WHERE note_id = ? AND user_id = ?");
    $stmt->execute([$burn_note_id, $user_id]);

    echo json_encode(['success' => true]);
    exit();
}

$current_time = date('Y-m-d H:i:s');
$pdo->prepare("UPDATE notes SET status = 1 WHERE expiry_time <= :current_time AND status = 0")
    ->execute(['current_time' => $current_time]);

$stmt = $pdo->prepare("SELECT note_id, message, expiry_time, password FROM notes WHERE user_id = ? AND status = 0 ORDER BY expiry_time ASC");
$stmt->execute([$user_id]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Hidden Note</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-900 text-white min-h-screen flex flex-col items-center p-6">
    <h1 class="text-4xl font-bold mb-4">Hidden Note</h1>
    <p class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!
        <a href="logout.php" class="text-red-400 underline">Logout</a>
    </p>

    <?php if (isset($success)): ?>
        <div class="bg-green-500 text-white p-3 rounded mb-4 w-full max-w-3xl"><?= $success ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="bg-red-500 text-white p-3 rounded mb-4 w-full max-w-3xl"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" class="bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-3xl">
        <label for="mensaje" class="block text-lg font-semibold mb-2">Write your note:</label>
        <textarea name="mensaje" id="noteInput" rows="5" maxlength="5000" required
            class="w-full p-3 bg-gray-700 rounded text-white"></textarea>

        <label for="note_password" class="block text-lg font-semibold mt-4 mb-2">Optional Password:</label>
        <input type="password" name="note_password" placeholder="Enter a password (optional)"
            class="w-full p-3 bg-gray-700 rounded text-white">

        <label for="tiempo_vida" class="block text-lg font-semibold mt-4 mb-2">Note lifetime:</label>
        <select name="tiempo_vida" id="tiempo_vida" onchange="toggleCustomTime()"
            class="w-full p-3 bg-gray-700 rounded text-white">
            <option value="3600">1 Hour</option>
            <option value="21600">6 Hours</option>
            <option value="86400">1 Day</option>
            <option value="259200">3 Days</option>
            <option value="604800">1 Week</option>
            <option value="custom">Custom</option>
        </select>

        <input type="number" name="custom_time" id="custom_time" style="display: none;"
            placeholder="Custom time in seconds" min="1" max="2592000"
            class="w-full p-3 mt-2 bg-gray-700 rounded text-white">

        <input type="submit" value="Create Note"
            class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer">
    </form>

    <div class="w-full max-w-3xl mt-8">
        <h2 class="text-2xl font-bold mb-4">Your Active Notes</h2>
        <?php if ($notes): ?>
            <?php foreach ($notes as $note):
                $note_url = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/note.php?id=" . $note['note_id'];
                ?>
                <div class="bg-gray-800 p-4 rounded-lg mb-4 shadow" id="note-<?= $note['note_id'] ?>">
                    <p class="mb-2"><?= htmlspecialchars($note['message']) ?></p>
                    <p class="text-sm text-gray-400">Expires at: <?= htmlspecialchars($note['expiry_time']) ?></p>
                    <?php if ($note['password']): ?>
                        <p class="text-yellow-400">🔒 Password Protected</p>
                    <?php endif; ?>

                    <a href="<?= $note_url ?>" class="text-blue-400 underline">View Note</a>
                    <button class="ml-4 bg-green-500 hover:bg-green-600 text-white py-1 px-3 rounded"
                        onclick="copyNoteUrl('<?= $note_url ?>')">Copy Note URL</button>
                    <button class="ml-4 bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded"
                        onclick="burnNote('<?= $note['note_id'] ?>')">🔥 Burn This Note</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No active notes available.</p>
        <?php endif; ?>
    </div>

    <script>
        function toggleCustomTime() {
            const dropdown = document.getElementById('tiempo_vida');
            const customInput = document.getElementById('custom_time');
            customInput.style.display = dropdown.value === 'custom' ? 'block' : 'none';
        }

        function copyNoteUrl(url) {
            navigator.clipboard.writeText(url)
                .then(() => Swal.fire('Copied!', 'Note URL copied to clipboard!', 'success'))
                .catch(() => Swal.fire('Failed!', 'Failed to copy note URL.', 'error'));
        }

        function burnNote(noteId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Burning a secret will delete it before it has been received.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, burn it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `burn_note_id=${noteId}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Burned!',
                                    text: 'The note has been burned successfully.',
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Failed!', 'Failed to burn the note.', 'error');
                            }
                        })
                        .catch(() => Swal.fire('Error!', 'An error occurred while burning the note.', 'error'));

                }
            });
        }
    </script>
</body>

</html>