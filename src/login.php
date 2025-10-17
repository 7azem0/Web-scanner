<?php
session_start();

$servername = getenv("MYSQL_HOST");
$username = getenv("MYSQL_USER");
$password = getenv("MYSQL_PASSWORD");
$database = getenv("MYSQL_DB");

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = trim($_POST["username"]);
    $pass = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT Password FROM users WHERE Username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($pass, $hashed_password)) {
            // ✅ Successful login
            $_SESSION["username"] = $user;

            // Redirect to index after 1 second
            header("Refresh: 1; URL=index.php");
            $message = "<div class='alert alert-success text-center mt-3 animate__animated animate__fadeIn' id='fadeMessage'>
                            ✅ Login successful! Redirecting...
                        </div>";
        } else {
            $message = "<div class='alert alert-danger text-center mt-3 animate__animated animate__shakeX' id='fadeMessage'>
                            ❌ Incorrect password!
                        </div>";
        }
    } else {
        $message = "<div class='alert alert-warning text-center mt-3 animate__animated animate__shakeX' id='fadeMessage'>
                        ⚠️ User not found!
                    </div>";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Web Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

    <style>
        body {
            background: radial-gradient(circle at top left, #0f172a, #1e293b);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Poppins", sans-serif;
            color: white;
        }

        .login-card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            animation: fadeInDown 0.8s;
        }

        .login-card h3 {
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
            color: #38bdf8;
        }

        .form-control {
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #e2e8f0;
        }

        .form-control:focus {
            background-color: rgba(255,255,255,0.1);
            box-shadow: 0 0 0 0.2rem rgba(56,189,248,0.25);
            color: #fff;
        }

        ::placeholder {
            color: rgba(255,255,255,0.5);
        }

        .btn-custom {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            border: none;
            color: #fff;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
        }

        small {
            display: block;
            text-align: center;
            margin-top: 15px;
            opacity: 0.8;
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; display: none; }
        }
    </style>
</head>
<body>
    <div class="login-card animate__animated animate__fadeInDown">
        <h3>Login</h3>
        <form method="POST" action="">
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-custom w-100 py-2">Login</button>
        </form>
        <?php echo $message; ?>
        <small>Don't have an account? <a href="register.php" style="color: #38bdf8;">Register here</a></small>
    </div>

    <script>
        // Fade out alert after 3 seconds
        const msg = document.getElementById('fadeMessage');
        if (msg) {
            setTimeout(() => {
                msg.style.transition = 'opacity 1s ease';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 1000);
            }, 3000);
        }
    </script>
</body>
</html>
