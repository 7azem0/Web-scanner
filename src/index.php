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

// Function to get Gravatar URL
function getGravatar($email, $size = 40) {
    $email = strtolower(trim($email));
    $hash = md5($email);
    return "https://www.gravatar.com/avatar/$hash?s=$size&d=identicon";
}

// Fetch user's email if logged in
$userEmail = "";
if(isset($_SESSION['username'])) {
    $stmt = $conn->prepare("SELECT Email FROM users WHERE Username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $stmt->bind_result($userEmail);
    $stmt->fetch();
    $stmt->close();
}

// Initialize variables
$recentScans = [];
$totalScans = $successScans = $failedScans = $pendingscans = 0;

// Only try to fetch scans if user is logged in
if(isset($_SESSION['username'])) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'scans'");
    if($tableCheck && $tableCheck->num_rows > 0) {
        $stmt = $conn->prepare("SELECT Scan_ID, URL, Scan_Status, Created_At FROM scans WHERE Username = ? ORDER BY Created_At DESC LIMIT 5");
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $recentScans[] = $row;
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT 
                                    COUNT(*) AS total,
                                    SUM(CASE WHEN Scan_Status='Pending' THEN 1 ELSE 0 END) AS pending,
                                    SUM(CASE WHEN Scan_Status='Success' THEN 1 ELSE 0 END) AS success,
                                    SUM(CASE WHEN Scan_Status='Failed' THEN 1 ELSE 0 END) AS failed

                                FROM scans WHERE Username=?");
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $stmt->bind_result($totalScans, $pendingscans, $successScans, $failedScans);
        $stmt->fetch();
        $stmt->close();
    } else {
        $tableMissingMessage = "The scans table does not exist yet. Metrics and recent scans will appear after the first scan.";
    }
}

// Vulnerabilities data
$vulns = [
    "A01 — Broken Access Control" => [
        "Attempt unauthorized access, URL tampering, forced role escalation.",
        "Target endpoint, request/response showing unauthorized data.",
        "Enforce authorization server-side, validate ownership and sessions."
    ],
    "A02 — Didn't decide yet" => [
        " ",
        " ",
        " "
    ],
    "A03 — Didn't decide yet" => [
        " ",
        " ",
        " "
    ],
    "A04 — Didn't decide yet" => [
        " ",
        " ",
        " "
    ],
    "A05 — Didn't decide yet" => [
        " ",
        " ",
        " "
    ],
    "A06 — Didn't decide yet" => [
        " ",
        " ",
        " "
    ],
    "A07 — Didn't decide yet" => [
        " ",
        " ",
        " "
    ],
    "A08 — Didn't decide yet" => [
        " ",
        " ",
        " "
    ],
    "A09 — Didn't decide yet" => [
        " ",
        " ",
        " "
    ],
    "A10 — Didn't decide yet" => [
        " ",
        " ",
        " "
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Web Scanner Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
<style>
body { background: radial-gradient(circle at top left, #0f172a, #1e293b); font-family: "Poppins", sans-serif; min-height: 100vh; }
.navbar { background: rgba(25,39,52,0.85); backdrop-filter: blur(12px); border-radius: 15px; margin:0px; box-shadow: 0 8px 25px rgba(0,0,0,0.3); }
.navbar-brand { font-weight: 600; color: #a3d0e3ff !important; }
.btn-outline-light { color: #e2e8f0; border-color: #e2e8f0; }
.btn-outline-light:hover { background-color: #38bdf8; color: #0f172a; border-color: #38bdf8; }
.dropdown-menu { min-width: 180px; background-color: rgba(25,39,52,0.95); border: none; backdrop-filter: blur(10px); border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
.dropdown-item { color: #e2e8f0; }
.dropdown-item:hover { background-color: #38bdf8; color: #0f172a; }
.glass-card { background: rgba(0, 0, 0, 0.41); backdrop-filter: blur(12px); border-radius: 20px; padding: 20px; margin-top: 20px; animation: fadeIn 0.8s ease-in-out; color:#e2e8f0; }
@keyframes fadeIn { from { opacity:0; transform: translateY(10px);} to {opacity:1; transform: translateY(0);} }
.metric-bar { display: flex; gap: 15px; margin-top: 20px; flex-wrap: wrap; justify-content: center; }
.metric-card { background: rgba(30,41,59,0.85); backdrop-filter: blur(10px); border-radius: 12px; padding: 15px 20px; text-align: center; color: #e2e8f0; flex: 1 1 150px; max-width: 200px; box-shadow:0 4px 20px rgba(0,0,0,0.25); transition:0.2s; }
.metric-card:hover { transform: translateY(-3px); box-shadow:0 10px 30px rgba(0,0,0,0.35); }
.scan-grid { margin-top: 30px; display:grid; grid-template-columns:repeat(auto-fill, minmax(220px,1fr)); gap:15px; }
.scan-card { background: rgba(30,41,59,0.85); padding: 15px 20px; border-radius: 12px; box-shadow:0 4px 20px rgba(0,0,0,0.25); color:#e2e8f0; transition:0.2s; }
.scan-card:hover { transform: translateY(-3px); box-shadow:0 10px 30px rgba(0,0,0,0.35); }
.btn-view { background: linear-gradient(135deg,#1d4ed8,#2563eb); border:none; color:#fff; font-weight:600; border-radius:8px; padding:4px 8px; font-size:0.85rem; }
.intro-section { background: rgba(30,41,59,0.85); backdrop-filter: blur(12px); border-radius: 20px; padding: 20px; margin-top:20px; box-shadow:0 8px 25px rgba(0,0,0,0.3); color:#e2e8f0; }
.intro-section h2 { color:#38bdf8; text-align:center; margin-bottom:15px; font-size:1.5rem; }
.vuln-card { background: rgba(185, 221, 255, 0.08); padding:10px 12px; border-radius:10px; margin-bottom:12px; box-shadow:0 4px 20px rgba(1, 1, 1, 0.25); font-size:0.85rem; }
.vuln-card h5 { color:#66b2ff; font-weight:500; font-size:1rem; margin-bottom:5px; }
.vuln-card p { color: #e2e8f0; margin-bottom:4px; }
.source-link { color:#38bdf8; text-decoration:none; font-weight:500; }
.source-link:hover { text-decoration:underline; }
footer { color:#a0b3c0; margin-top:30px; font-size:0.85rem; }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="#">Web Scanner</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <div class="d-flex gap-2 align-items-center">
        <?php if(isset($_SESSION['username'])): ?>
            <div class="dropdown">
              <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                <img src="<?php echo getGravatar($userEmail); ?>" alt="Profile" style="width:35px; height:35px; border-radius:50%; margin-right:8px;">
                <?php echo htmlspecialchars($_SESSION['username']); ?>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="account.php">Past Scans</a></li>
                <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
              </ul>
            </div>
        <?php else: ?>
            <a href="register.php" class="btn btn-outline-light btn-sm">Register</a>
            <a href="login.php" class="btn btn-light btn-sm text-dark fw-semibold">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div class="container">

<!-- Introduction Section -->
<div class="intro-section animate__animated animate__fadeIn">
    <h2>Welcome to Web Scanner</h2>
    <p>Our platform tests the URLs you provide against common web security vulnerabilities. Each scan simulates real-world attacks and highlights potential weaknesses, helping you secure your web applications.</p>

    <div class="row">
        <?php
        $i=0;
        foreach($vulns as $title => $data):
        ?>
        <div class="col-md-6">
            <div class="accordion vuln-card" id="vulnAccordion<?php echo $i; ?>">
                <div class="accordion-item bg-transparent border-0">
                    <h2 class="accordion-header" id="heading<?php echo $i; ?>">
                        <button class="accordion-button collapsed bg-transparent text-light py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $i; ?>">
                            <?php echo $title; ?>
                        </button>
                    </h2>
                    <div id="collapse<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#vulnAccordion<?php echo $i; ?>">
                        <div class="accordion-body py-2 px-3" style="font-size:0.85rem;">
                            <p><strong>Test:</strong> <?php echo $data[0]; ?></p>
                            <p><strong>Evidence:</strong> <?php echo $data[1]; ?></p>
                            <p><strong>Remediation:</strong> <?php echo $data[2]; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php $i++; endforeach; ?>
    </div>

    <p class="text-center mt-3">Read more about our methodology: 
        <a class="source-link" href="https://owasp.org/www-project-top-ten/" target="_blank">OWASP Web Security Testing Guide</a>
    </p>
</div>

<!-- Dashboard -->
<div class="glass-card text-center">
    <h3>Database Connection</h3>
    <?php
    $sql = "SELECT NOW() AS currentTime;";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $time = reset($row);
        echo "<div class='alert alert-success'>✅ Working | Current DB Time: $time</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Failed</div>";
    }
    ?>
</div>

<?php if(isset($_SESSION['username'])): ?>
    <?php if(isset($tableMissingMessage)): ?>
        <p class="text-center text-light mt-4"><?php echo $tableMissingMessage; ?></p>
    <?php else: ?>
        <div class="metric-bar">
            <div class="metric-card"><h4><?php echo $totalScans; ?></h4><p>Total Scans</p></div>
            <div class="metric-card"><h4><?php echo $pendingscans; ?></h4><p>Pending</p></div>
            <div class="metric-card"><h4><?php echo $successScans; ?></h4><p>Successful</p></div>
            <div class="metric-card"><h4><?php echo $failedScans; ?></h4><p>Failed</p></div>
        </div>
<!-- START NEW SCAN BUTTON -->
<div class="text-center mt-3">
    <a href="scans.php" class="btn btn-view" style="padding: 10px 20px; font-size: 1rem;">Start New Scan</a>
</div>
        <?php if(!empty($recentScans)): ?>
        <h3 class="text-center text-light mt-4">Recent Scans</h3>
        <div class="scan-grid">
            <?php foreach($recentScans as $scan): ?>
            <div class="scan-card">
                <h5><?php echo htmlspecialchars($scan['URL']); ?></h5>
                <p>Status: <strong><?php echo htmlspecialchars($scan['Scan_Status']); ?></strong></p>
                <p>Date: <?php echo date("d M Y H:i", strtotime($scan['Created_At'])); ?></p>
                <a href="scan_details.php?id=<?php echo $scan['Scan_ID']; ?>" class="btn btn-view">View Details</a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="text-center text-light mt-4">No scans available yet.</p>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

</div>
<footer class="text-center mt-4 mb-3">
    <small>© 2025 Web Scanner | Built with PHP + Docker</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
