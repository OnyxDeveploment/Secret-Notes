<!DOCTYPE html>
<html lang="en">

<head>
    <title>404 Not Found</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #1E1E1E, #2C3E50, #34495E);
            background-size: 300% 300%;
            animation: gradientShift 10s ease infinite;
            color: #EDEDED;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .floating {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .back-button {
            background: linear-gradient(135deg, #7A33EE, #5a22bb);
            padding: 12px 24px;
            border-radius: 30px;
            color: white;
            font-size: 18px;
            text-decoration: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-button:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(122, 51, 238, 0.8);
        }


        @media (max-width: 768px) {
            h1 {
                font-size: 80px;
            }

            h2 {
                font-size: 28px;
            }

            p {
                font-size: 16px;
            }

            .back-button {
                font-size: 16px;
                padding: 10px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="text-center">
        <h1 class="text-9xl font-bold text-red-500 floating">404</h1>
        <h2 class="text-4xl font-semibold mt-4">Note Not Found</h2>
        <p class="text-lg mt-2 text-gray-300">The note you are looking for has expired or does not exist.</p>
        <a href="index.php" class="back-button mt-6">
            Back to Home
        </a>
    </div>
</body>

</html>