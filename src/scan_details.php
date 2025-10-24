<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit;
}

$scan_id_to_show = 0;
if (isset($_GET['id'])) {
    $scan_id_to_show = intval($_GET['id']);
}

if ($scan_id_to_show == 0) {
    header("location: account.php");
    exit;
}

$servername = getenv("MYSQL_HOST");
$username_db = getenv("MYSQL_USER");
$password_db = getenv("MYSQL_PASSWORD");
$database = getenv("MYSQL_DB");

$conn = new mysqli($servername, $username_db, $password_db, $database);
if ($conn->connect_error) {
    die("Database connection failed: ". $conn->connect_error);
}

function getGravatar($email, $size = 40) {
    $email = strtolower(trim((string)$email)); 
    $hash = md5($email);
    return "https://www.gravatar.com/avatar/$hash?s=$size&d=identicon";
}

$userEmail = "";
$profilePicturePath = null;
$stmt_user_data = $conn->prepare("SELECT Email, profile_picture_path FROM users WHERE Username = ?");
$stmt_user_data->bind_param("s", $_SESSION['username']); $stmt_user_data->execute();
$stmt_user_data->bind_result($userEmail, $profilePicturePath); $stmt_user_data->fetch(); $stmt_user_data->close();

$displayProfilePicture = "";
if ($profilePicturePath && file_exists($profilePicturePath)) {
    $displayProfilePicture = $profilePicturePath;
} else {
    $displayProfilePicture = getGravatar($userEmail);
}

$scan_details = null;
$stmt = $conn->prepare("SELECT Scan_ID, URL, Scan_Status, Created_At FROM scans WHERE Scan_ID = ? AND Username = ?");
$stmt->bind_param("is", $scan_id_to_show, $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 1) {
    $scan_details = $result->fetch_assoc();
}
$stmt->close();

if ($scan_details === null) {
    header("location: account.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scan Details - <?php echo htmlspecialchars($scan_details['URL']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background: radial-gradient(circle at top left, #0f172a, #1e2b3b); font-family: "Poppins", sans-serif; min-height: 100vh; color: #e2e8f0; }
    .navbar { background: rgba(25,39,52,0.85); backdrop-filter: blur(12px); border-radius: 15px; margin:0px; box-shadow: 0 8px 25px rgba(0,0,0,0.3); position: relative; z-index: 1000; }
    .navbar-brand { font-weight: 600; color: #a3d0e3ff !important; }
    .dropdown-menu { min-width: 180px; background-color: rgba(25,39,52,0.95); border: none; backdrop-filter: blur(10px); border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
    .dropdown-item { color: #e2e8f0; }
    .dropdown-item:hover { background-color: #38bdf8; color: #0f172a; }
    .username-truncate {
        display: inline-block;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }
    .details-card {
        background: rgba(30,41,59,0.85); 
        padding: 25px 30px; 
        border-radius: 15px; 
        box-shadow:0 8px 30px rgba(0,0,0,0.3);
        margin-top: 30px;
    }
    .details-card h1 {
        color: #38bdf8;
        font-size: 1.8rem;
        word-wrap: break-word;
    }
    .details-card p {
        font-size: 1.1rem;
        color: #cdd8e3;
    }
    .details-card .badge {
        font-size: 1rem;
        padding: 8px 12px;
    }
    
    .results-section {
        margin-top: 30px;
    }
    
    footer { color:#a0b3c0; margin-top:30px; font-size:0.85rem; }
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
        <?php if(isset($_SESSION['username'])): ?>
            <div class="dropdown">
              <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                <img src="<?php echo htmlspecialchars($displayProfilePicture); ?>" alt="Profile" style="width:35px; height:35px; border-radius:50%; margin-right:8px; object-fit: cover;">
                <span class="username-truncate"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="account.php">Past Scans</a></li>
                <li><a class="dropdown-item" href="account.php">Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
              </ul>
            </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div class="container">

    <div class="details-card">
        <p class="mb-2 text-secondary">Scan ID: <?php echo $scan_details['Scan_ID']; ?></p>
        
        <h1><?php echo htmlspecialchars($scan_details['URL']); ?></h1>
        
        <hr>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                <p class="mb-1"><strong>Status:</strong></p>
                <span class="badge 
                    <?php 
                        switch($scan_details['Scan_Status']) {
                            case 'Success': echo 'bg-success'; break;
                            case 'Failed': echo 'bg-danger'; break;
                            default: echo 'bg-primary'; 
                        }
                    ?>
                "><?php echo htmlspecialchars($scan_details['Scan_Status']); ?></span>
            </div>
            <div>
                <p class="mb-1 text-end"><strong>Date Scanned:</strong></p>
                <p class="mb-0 text-end"><?php echo date("d M Y, h:i A", strtotime($scan_details['Created_At'])); ?></p>
            </div>
        </div>
    </div>

    <div class="results-section">
        <h2 class="text-center text-light">Scan Results</h2>
        <div class="details-card text-center">
            <p>Scan results and detected vulnerabilities will appear here once the scan is complete.</p>
        </div>
    </div>
    
    <div class="text-center mt-4">
        <a href="account.php" class="btn btn-outline-light">&larr; Back to All Scans</a>
    </div>

</div>

<footer class="text-center mt-4 mb-3">
    <small>© 2025 Web Scanner | Built with PHP + Docker</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>