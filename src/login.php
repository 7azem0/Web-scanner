<?php
session_start();

if (isset($_SESSION["username"])) {
    header("location: index.php");
    exit;
}

$servername = getenv("MYSQL_HOST");
$username_db = getenv("MYSQL_USER");
$password_db = getenv("MYSQL_PASSWORD");
$database = getenv("MYSQL_DB");

$conn = new mysqli($servername, $username_db, $password_db, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login_input = trim($_POST["username"]); 
    $password_input = $_POST["password"];

    if (empty($login_input) || empty($password_input)) {
        $message = "<div class='alert alert-danger text-center mt-3 animate__animated animate__shakeX'>❌ Error: Username/Email and Password are required.</div>";
    } else {
        $stmt = $conn->prepare("SELECT Username, Password FROM users WHERE Username = ? OR Email = ?");
        $stmt->bind_param("ss", $login_input, $login_input); 
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($username_from_db, $hashed_password_from_db);
            $stmt->fetch();

            if (password_verify($password_input, $hashed_password_from_db)) {

                session_regenerate_id(true);
                $_SESSION["username"] = $username_from_db; 

                header("location: index.php");
                exit;
            } else {
                $message = "<div class='alert alert-danger text-center mt-3 animate__animated animate__shakeX'>❌ Error: Invalid credentials.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger text-center mt-3 animate__animated animate__shakeX'>❌ Error: Invalid credentials.</div>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Web Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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

        .login-card {
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

        .login-card h3 {
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
            padding-right: 40px;
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
        
        .toggle-password-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0b3c0;
            cursor: pointer;
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
    </style>
</head>
<body>
    <div class="login-card animate__animated animate__fadeInDown">
        <h3>Welcome Back!</h3>
        <form method="POST" action="">
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Username or Email" required>
            </div>
            <div class="mb-3 position-relative">
                <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                <i class="bi bi-eye-slash toggle-password-icon" id="togglePassword"></i>
            </div>
            <button type="submit" class="btn btn-custom w-100 py-2">Login</button>
        </form>

        <?php echo $message; ?>

        <small>Don't have an account? <a href="/register.php">Register here</a></small>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>