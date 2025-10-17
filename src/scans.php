<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$servername = getenv("MYSQL_HOST");
$username = getenv("MYSQL_USER");
$password = getenv("MYSQL_PASSWORD");
$database = getenv("MYSQL_DB");

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$message = "";

// Handle URL submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $url = trim($_POST['url']);

    if (filter_var($url, FILTER_VALIDATE_URL)) {
        // Here you can call your scanning function or store in the DB
        // For now, just show success message
        $message = "<div class='alert alert-success text-center animate__animated animate__fadeIn'>✅ URL accepted: " . htmlspecialchars($url) . "</div>";

        // Example: insert scan record
        $stmt = $conn->prepare("INSERT INTO scans (Username, URL, Scan_Status, Created_At) VALUES (?, ?, 'Pending', NOW())");
        $stmt->bind_param("ss", $_SESSION['username'], $url);
        $stmt->execute();
        $stmt->close();
    } else {
        $message = "<div class='alert alert-danger text-center animate__animated animate__shakeX'>❌ Invalid URL!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Start a New Scan | Web Scanner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
<style>
body {
    background: radial-gradient(circle at top left, #0f172a, #1e293b);
    font-family: "Poppins", sans-serif;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
.navbar { background: rgba(25, 39, 52, 0.85); backdrop-filter: blur(15px); border-radius: 15px; box-shadow: 0 8px 30px rgba(0,0,0,0.3); }
.navbar-brand { font-weight: 600; color: #38bdf8 !important; }
.btn-outline-light { color: #e2e8f0; border-color: #e2e8f0; }
.btn-outline-light:hover { background-color: #38bdf8; color: #0f172a; border-color: #38bdf8; }
.glass-card {
    background: rgba(25, 39, 52, 0.85); 
    backdrop-filter: blur(15px); 
    border-radius: 20px; 
    padding: 40px; 
    max-width: 500px; 
    margin: 50px auto; 
    box-shadow: 0 8px 40px rgba(0,0,0,0.4); 
    animation: fadeIn 0.8s ease-in-out;
}
@keyframes fadeIn { from { opacity:0; transform: translateY(10px);} to {opacity:1; transform: translateY(0);} }
.glass-card h3 { color: #66b2ff; font-weight:600; margin-bottom:25px; text-align:center; }
.form-control { background-color: rgba(255, 255, 255, 0.66); border: 1px solid rgba(255,255,255,0.15); color:#f1f1f1; border-radius:12px; }
.form-control:focus { background-color: rgba(255, 255, 255, 1); border-color:#66b2ff; box-shadow:0 0 10px rgba(102,178,255,0.4); outline:none; }
::placeholder { color: rgba(255,255,255,0.6); }
.btn-submit { background: linear-gradient(135deg,#1d4ed8,#2563eb); border:none; color:#fff; font-weight:600; border-radius:12px; transition: all 0.3s ease; width: 100%; padding: 10px 0; }
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.3); }
footer { text-align:center; margin-top:auto; margin-bottom:20px; color:#a0b3c0; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="index.php">Web Scanner</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <div class="d-flex gap-2 align-items-center">
        <a href="index.php" class="btn btn-outline-light btn-sm">Dashboard</a>
        <a href="logout.php" class="btn btn-light btn-sm text-dark fw-semibold">Logout</a>
      </div>
    </div>
  </div>
</nav>

<div class="glass-card">
    <h3>Start a New Scan</h3>
    <?php echo $message; ?>
    <form method="POST" action="">
        <div class="mb-3">
            <input type="url" name="url" class="form-control" placeholder="Enter URL to scan" required>
        </div>
        <button type="submit" class="btn btn-submit">Scan URL</button>
    </form>
</div>

<footer>
    <small>© 2025 Web Scanner | Built with PHP + Docker</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
