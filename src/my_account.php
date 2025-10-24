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
    die("Database connection failed: ". $conn->connect_error);
}

$message = "";
$current_username = $_SESSION['username'];

$user_data = null;
$current_email = null;
$current_phone = null;
$current_profile_picture_path = null;

$stmt_fetch = $conn->prepare("SELECT Email, phone_number, profile_picture_path FROM users WHERE Username = ?");
$stmt_fetch->bind_param("s", $current_username);
$stmt_fetch->execute();
$stmt_fetch->bind_result($current_email, $current_phone, $current_profile_picture_path);
$stmt_fetch->fetch();
$stmt_fetch->close();


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'upload_profile_picture') {

    $target_dir = "uploads/profile_pictures/";
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0775, true);
    }

    if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));

        if (in_array($file_extension, $allowed_types)) {
            $new_filename = uniqid('profile_', true) . "." . $file_extension;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                
                if ($current_profile_picture_path && file_exists($current_profile_picture_path)) {
                    unlink($current_profile_picture_path);
                }

                $stmt_update_pic = $conn->prepare("UPDATE users SET profile_picture_path = ? WHERE Username = ?");
                $stmt_update_pic->bind_param("ss", $target_file, $current_username);
                if ($stmt_update_pic->execute()) {
                     $_SESSION['upload_message'] = "<div class='alert alert-success animate__animated animate__fadeIn'>Profile picture updated successfully!</div>"; 
                     header("Location: my_account.php"); 
                     exit;                            
                } else {
                     $message = "<div class='alert alert-danger animate__animated animate__shakeX'>Database error updating picture path.</div>";
                }
                $stmt_update_pic->close();
            } else {
                $message = "<div class='alert alert-danger animate__animated animate__shakeX'>Sorry, there was an error uploading your file.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger animate__animated animate__shakeX'>Only JPG, JPEG, PNG & GIF files are allowed.</div>";
        }
    } else {
         $message = "<div class='alert alert-danger animate__animated animate__shakeX'>Please select an image file to upload or file upload error occurred.</div>";
    }
}

if (isset($_SESSION['upload_message'])) {
    $message = $_SESSION['upload_message'];
    unset($_SESSION['upload_message']);
}


function getGravatar($email, $size = 40) {
    $email_string = $email ?? ''; 
    $email_lower = strtolower(trim($email_string));
    $hash = md5($email_lower);
    return "https://www.gravatar.com/avatar/$hash?s=$size&d=identicon";
}

$displayProfilePicture = "";
if ($current_profile_picture_path && file_exists($current_profile_picture_path)) {
    $displayProfilePicture = $current_profile_picture_path;
} else {
    $displayProfilePicture = getGravatar($current_email);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account - Web Scanner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body { background: radial-gradient(circle at top left, #0f172a, #1e2b3b); font-family: "Poppins", sans-serif; min-height: 100vh; color: #e2e8f0; }
    .navbar { background: rgba(25,39,52,0.85); backdrop-filter: blur(12px); border-radius: 15px; margin:0px; box-shadow: 0 8px 25px rgba(0,0,0,0.3); position: relative; z-index: 1000; }
    .navbar-brand { font-weight: 600; color: #a3d0e3ff !important; }
    .dropdown-menu { min-width: 180px; background-color: rgba(25,39,52,0.95); border: none; backdrop-filter: blur(10px); border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
    .dropdown-item { color: #e2e8f0; }
    .dropdown-item:hover { background-color: #38bdf8; color: #0f172a; }
    .username-truncate { display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle; }
    .account-card { background: rgba(30,41,59,0.85); padding: 25px 30px; border-radius: 15px; box-shadow:0 8px 30px rgba(0,0,0,0.3); margin-top: 20px; max-width: 600px; margin-left: auto; margin-right: auto; }
    .form-label { font-weight: 500; color: #e2e8f0; }
    .form-control { background-color: rgba(0,0,0,0.3); border: 1px solid #4a5a70; color: #e2e8f0; padding-right: 15px; } 
    .form-control:focus { background-color: rgba(0,0,0,0.4); border-color: #38bdf8; box-shadow: 0 0 0 0.25rem rgba(56,189,248,.25); color: #e2e8f0; }
    .form-control[readonly] { background-color: rgba(0,0,0,0.2); opacity: 0.7; cursor: display: alias; }
    .form-control[readonly]::placeholder { color: #a0b3c0; font-style: italic; font-size: 0.875em; } /* Style placeholder for readonly */
    .btn-primary { background-color: #2563eb; border: none; }
    .profile-pic-preview { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 2px solid #38bdf8; }
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

    <h1 class="text-center text-light mt-4">My Account Information</h1>

    <div class="account-card">
        <div class="mb-4">
            <?php echo $message; ?>
        </div>

        <div class="text-center mb-4">
             <img src="<?php echo htmlspecialchars($displayProfilePicture); ?>" alt="Profile Picture" class="profile-pic-preview">
        </div>

        <form method="POST" action="my_account.php" enctype="multipart/form-data" class="mb-4 border-bottom pb-4">
            <div class="mb-3">
                <label for="profile_picture" class="form-label">Change Profile Picture</label>
                <input class="form-control" type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif" required>
            </div>
            <button type="submit" name="action" value="upload_profile_picture" class="btn btn-primary">Upload New Picture</button> 
        </form>

        
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($current_username); ?>" readonly>
            <small class="form-text" style="color:#a0b3c0;">Go to Security Settings to change username.</small>
        </div>
        
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" 
                   value="<?php echo htmlspecialchars($current_email ?? ''); ?>" 
                   placeholder="<?php echo empty($current_email) ? 'No Email Added. Go To Security Settings.' : ''; ?>" 
                   readonly>
        </div>

        <div class="mb-3">
            <label for="phone_number" class="form-label">Phone Number</label>
            <input type="tel" class="form-control" id="phone_number" 
                   value="<?php echo htmlspecialchars($current_phone ?? ''); ?>" 
                   placeholder="<?php echo empty($current_phone) ? 'No Phone Number Added. Go To Security Settings.' : ''; ?>" 
                   readonly>
        </div>
            
        
    </div>
    
    <div class="text-center mt-4 d-flex justify-content-center gap-2">
        <a href="index.php" class="btn btn-outline-light">&larr; Back to Dashboard</a>
        <a href="settings.php" class="btn btn-outline-light">Security Settings &rarr;</a> 
    </div>

</div>

<footer class="text-center mt-4 mb-3">
    <small>© 2025 Web Scanner | Built with PHP + Docker</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>