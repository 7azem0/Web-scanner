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
    $email = trim($_POST["email"]);
    $pass = password_hash($_POST["password"], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (Username, Email, Password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $user, $email, $pass);

    if ($stmt->execute()) {
        $_SESSION["message"] = "<div class='alert alert-success text-center mt-3 animate__animated animate__fadeIn'>✅ Registration successful!</div>";
    } else {
        $_SESSION["message"] = "<div class='alert alert-danger text-center mt-3 animate__animated animate__shakeX'>❌ Error: " . htmlspecialchars($stmt->error) . "</div>";
    }

    $stmt->close();

    // 🌀 Redirect to same page (clears POST data)
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit();
}

if (isset($_SESSION["message"])) {
    $message = $_SESSION["message"];
    unset($_SESSION["message"]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | Web Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

    <style>
        body {
            font-family: "Poppins", sans-serif;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 30% 20%, #0f2027, #081f27ff, #182f39ff);
            color: #e0e0e0;
        }

        .register-card {
            background: rgba(25, 39, 52, 0.85);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.4);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .register-card h3 {
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
            color: #66b2ff;
        }

        .form-control {
            background-color: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f1f1f1;
            border-radius: 12px;
        }

        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.12);
            border-color: #66b2ff;
            box-shadow: 0 0 10px rgba(102, 178, 255, 0.4);
            outline: none;
        }

        ::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .btn-custom {
            background: linear-gradient(135deg, #1e90ff, #0066cc);
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            background: linear-gradient(135deg, #3399ff, #0077e6);
            box-shadow: 0 0 12px rgba(30, 144, 255, 0.5);
            transform: translateY(-2px);
        }

        .btn-custom:active {
            transform: scale(0.98);
        }

        small {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #a0b3c0;
        }

        small a {
            color: #66b2ff;
            text-decoration: none;
            font-weight: 500;
        }

        small a:hover {
            text-decoration: underline;
        }

        /* 🕒 Fade-out animation for message */
        .alert {
            transition: opacity 1s ease-out;
        }
    </style>
</head>
<body>
    <div class="register-card animate__animated animate__fadeInDown">
        <h3>Create an Account</h3>
        <form method="POST" action="">
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email (optional)">
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-custom w-100 py-2">Register</button>
        </form>

        <?php echo $message; ?>

        <small>Already have an account? <a href="#">Login here</a></small>
    </div>

    <script>
        // 🕒 Make the message fade out after 3 seconds
        setTimeout(() => {
            const alertBox = document.querySelector('.alert');
            if (alertBox) {
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 1000);
            }
        }, 3000);
    </script>
</body>
</html>
