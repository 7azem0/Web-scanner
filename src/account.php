<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("location: login.php");
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

function getGravatar($email, $size = 40) {
    $email = strtolower(trim((string)($email ?? '')));
    $hash = md5($email);
    return "https://www.gravatar.com/avatar/$hash?s=$size&d=identicon";
}

$userEmail = "";
$profilePicturePath = null;
$stmt_user_data = $conn->prepare("SELECT Email, profile_picture_path FROM users WHERE Username = ?");
$stmt_user_data->bind_param("s", $_SESSION['username']);
$stmt_user_data->execute();
$stmt_user_data->bind_result($userEmail, $profilePicturePath);
$stmt_user_data->fetch();
$stmt_user_data->close();

$displayProfilePicture = "";
if ($profilePicturePath && file_exists($profilePicturePath)) {
    $displayProfilePicture = $profilePicturePath;
} else {
    $displayProfilePicture = getGravatar($userEmail);
}

$allScans = [];
$stmt = $conn->prepare("SELECT Scan_ID, URL, Scan_Status, Created_At FROM scans WHERE Username = ? ORDER BY Created_At DESC");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $allScans[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Past Scans - Web Scanner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background: radial-gradient(circle at top left, #0f172a, #1e293b); font-family: "Poppins", sans-serif; min-height: 100vh; }
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
    .scan-grid { margin-top: 30px; display:grid; grid-template-columns:repeat(auto-fill, minmax(250px,1fr)); gap:20px; }
    .scan-card {
        background: rgba(30,41,59,0.85);
        padding: 15px 20px;
        border-radius: 12px;
        box-shadow:0 4px 20px rgba(0,0,0,0.25);
        color:#e2e8f0;
        transition:0.2s;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .scan-card:hover { transform: translateY(-3px); box-shadow:0 10px 30px rgba(0,0,0,0.35); }
    .scan-card .btn-view {
        margin-top: auto;
    }
    .scan-card h5 {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    .btn-view { background: linear-gradient(135deg,#1d4ed8,#2563eb); border:none; color:#fff; font-weight:600; border-radius:8px; padding:4px 8px; font-size:0.85rem; }
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
                <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                <li><a class="dropdown-item" href="my_account.php">My Account</a></li>
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
    <h1 class="text-center text-light mt-4">All Past Scans</h1>

    <?php if(!empty($allScans)): ?>
        <div class="scan-grid">
            <?php foreach($allScans as $scan): ?>
            <div class="scan-card">
                <h5><?php echo htmlspecialchars($scan['URL']); ?></h5>
                <p>Status: <strong><?php echo htmlspecialchars($scan['Scan_Status']); ?></strong></p>
                <p>Date: <?php echo date("d M Y H:i", strtotime($scan['Created_At'])); ?></p>
                <a href="scan_details.php?id=<?php echo $scan['Scan_ID']; ?>" class="btn btn-view">View Details</a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-center text-light mt-4">You have not performed any scans yet.</p>
    <?php endif; ?>
</div>

<footer class="text-center mt-4 mb-3">
    <small>© 2025 Web Scanner | Built with PHP + Docker</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>