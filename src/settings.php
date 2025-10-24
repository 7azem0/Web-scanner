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
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$message = "";
$active_accordion = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $current_username = $_SESSION['username'];
    
    try {
        $stmt_check = $conn->prepare("SELECT Password FROM users WHERE Username = ?");
        $stmt_check->bind_param("s", $current_username);
        $stmt_check->execute();
        $stmt_check->bind_result($hashed_password_from_db);
        $stmt_check->fetch();
        $stmt_check->close();
    } catch (mysqli_sql_exception $e) {
         $message = "<div class='alert alert-danger animate__animated animate__shakeX'>Error fetching user data.</div>";
         $hashed_password_from_db = null; 
    }


    if (isset($_POST['action']) && $_POST['action'] == 'change_name' && $hashed_password_from_db) {
        $active_accordion = "name";
        $new_username = trim($_POST['new_username']);
        $current_pass_for_name = $_POST['current_password_for_name'];

        if (password_verify($current_pass_for_name, $hashed_password_from_db)) {
            if (!empty($new_username)) {
                try {
                    $stmt_update_name = $conn->prepare("UPDATE users SET Username = ? WHERE Username = ?");
                    $stmt_update_name->bind_param("ss", $new_username, $current_username);
                    $stmt_update_name->execute();
                    $stmt_update_name->close();

                    $stmt_update_scans = $conn->prepare("UPDATE scans SET Username = ? WHERE Username = ?");
                    $stmt_update_scans->bind_param("ss", $new_username, $current_username);
                    $stmt_update_scans->execute();
                    $stmt_update_scans->close();

                    $_SESSION['username'] = $new_username;
                    $message = "<div class='alert alert-success animate__animated animate__fadeIn'>Username updated successfully!</div>";

                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) {
                        $message = "<div class='alert alert-danger animate__animated animate__shakeX'>This username is already taken.</div>";
                    } else {
                        $message = "<div class='alert alert-danger animate__animated animate__shakeX'>An error occurred updating username.</div>";
                    }
                     if (isset($stmt_update_name) && $stmt_update_name instanceof mysqli_stmt) $stmt_update_name->close();
                     if (isset($stmt_update_scans) && $stmt_update_scans instanceof mysqli_stmt) $stmt_update_scans->close();
                }
            } else {
                $message = "<div class='alert alert-danger animate__animated animate__shakeX'>New username cannot be empty.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger animate__animated animate__shakeX'>Incorrect password.</div>";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == 'change_pass' && $hashed_password_from_db) {
        $active_accordion = "pass";
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if (password_verify($current_pass, $hashed_password_from_db)) {
            if ($new_pass == $confirm_pass) {
                if (!empty($new_pass)) {
                    try{
                        $new_hashed_pass = password_hash($new_pass, PASSWORD_BCRYPT);
                        $stmt_update = $conn->prepare("UPDATE users SET Password = ? WHERE Username = ?");
                        $stmt_update->bind_param("ss", $new_hashed_pass, $current_username);
                        $stmt_update->execute();
                        $stmt_update->close();
                        $message = "<div class='alert alert-success animate__animated animate__fadeIn'>Password updated successfully!</div>";
                    } catch (mysqli_sql_exception $e) {
                         $message = "<div class='alert alert-danger animate__animated animate__shakeX'>An error occurred updating password.</div>";
                         if (isset($stmt_update) && $stmt_update instanceof mysqli_stmt) $stmt_update->close();
                    }
                } else {
                    $message = "<div class='alert alert-danger animate__animated animate__shakeX'>New password cannot be empty.</div>";
                }
            } else {
                $message = "<div class='alert alert-danger animate__animated animate__shakeX'>New passwords do not match.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger animate__animated animate__shakeX'>Incorrect current password.</div>";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == 'update_email' && $hashed_password_from_db) {
        $active_accordion = "email";
        $new_email = trim($_POST['new_email']);
        $current_pass_for_email = $_POST['current_password_for_email'];

        if (password_verify($current_pass_for_email, $hashed_password_from_db)) {
             try {
                if (!empty($new_email) && filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                    $stmt_update_email = $conn->prepare("UPDATE users SET Email = ? WHERE Username = ?");
                    $stmt_update_email->bind_param("ss", $new_email, $current_username);
                    $stmt_update_email->execute();
                    $stmt_update_email->close();
                    $message = "<div class='alert alert-success animate__animated animate__fadeIn'>Email updated successfully!</div>";
                } else if (empty($new_email)){
                     $stmt_update_email = $conn->prepare("UPDATE users SET Email = NULL WHERE Username = ?");
                     $stmt_update_email->bind_param("s", $current_username);
                     $stmt_update_email->execute();
                     $stmt_update_email->close();
                     $message = "<div class='alert alert-success animate__animated animate__fadeIn'>Email removed successfully!</div>";
                } else {
                    $message = "<div class='alert alert-danger animate__animated animate__shakeX'>Invalid email address.</div>";
                }
             } catch (mysqli_sql_exception $e) {
                 if ($e->getCode() == 1062) {
                     $message = "<div class='alert alert-danger animate__animated animate__shakeX'>This email is already taken.</div>";
                 } else {
                    $message = "<div class='alert alert-danger animate__animated animate__shakeX'>An error occurred updating email.</div>";
                 }
                 if (isset($stmt_update_email) && $stmt_update_email instanceof mysqli_stmt) $stmt_update_email->close();
             }
        } else {
            $message = "<div class='alert alert-danger animate__animated animate__shakeX'>Incorrect password.</div>";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == 'delete_account' && $hashed_password_from_db) {
        $active_accordion = "delete";
        $current_pass_for_delete = $_POST['current_password_for_delete'];

        if (password_verify($current_pass_for_delete, $hashed_password_from_db)) {
            try {
                $stmt_user_pic = $conn->prepare("SELECT profile_picture_path FROM users WHERE Username = ?");
                $stmt_user_pic->bind_param("s", $current_username);
                $stmt_user_pic->execute();
                $stmt_user_pic->bind_result($pic_path_to_delete);
                $stmt_user_pic->fetch();
                $stmt_user_pic->close();

                $stmt_delete_scans = $conn->prepare("DELETE FROM scans WHERE Username = ?");
                $stmt_delete_scans->bind_param("s", $current_username);
                $stmt_delete_scans->execute();
                $stmt_delete_scans->close();

                if ($pic_path_to_delete && file_exists($pic_path_to_delete)) {
                    unlink($pic_path_to_delete);
                }

                $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE Username = ?");
                $stmt_delete_user->bind_param("s", $current_username);
                $stmt_delete_user->execute();
                $stmt_delete_user->close();

                session_destroy();
                header("location: index.php?message=Account+deleted+successfully");
                exit;
            } catch (mysqli_sql_exception $e) {
                 $message = "<div class='alert alert-danger animate__animated animate__shakeX'>An error occurred during account deletion.</div>";
                 if (isset($stmt_user_pic) && $stmt_user_pic instanceof mysqli_stmt) $stmt_user_pic->close();
                 if (isset($stmt_delete_scans) && $stmt_delete_scans instanceof mysqli_stmt) $stmt_delete_scans->close();
                 if (isset($stmt_delete_user) && $stmt_delete_user instanceof mysqli_stmt) $stmt_delete_user->close();
            }
        } else {
            $message = "<div class='alert alert-danger animate__animated animate__shakeX'>Incorrect password. Cannot delete account.</div>";
        }
    }
}


function getGravatar($email, $size = 40) {
    $email_string = $email ?? '';
    $email_lower = strtolower(trim($email_string));
    $hash = md5($email_lower);
    return "https://www.gravatar.com/avatar/$hash?s=$size&d=identicon";
}
$userEmail = "";
$profilePicturePath = null;
try {
    $stmt_email = $conn->prepare("SELECT Email, profile_picture_path FROM users WHERE Username = ?");
    $stmt_email->bind_param("s", $_SESSION['username']); $stmt_email->execute();
    $stmt_email->bind_result($userEmail, $profilePicturePath); $stmt_email->fetch(); $stmt_email->close();
} catch (mysqli_sql_exception $e) {
     error_log("Error fetching user email/picture path: " . $e->getMessage());
}


$displayProfilePicture = "";
if ($profilePicturePath && file_exists($profilePicturePath)) {
    $displayProfilePicture = $profilePicturePath;
} else {
    $displayProfilePicture = getGravatar($userEmail);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - Web Scanner</title>
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

    .username-truncate {
        display: inline-block;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }

    .settings-container {
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }
    .accordion-item {
        background-color: rgba(30,41,59,0.85);
        border: 1px solid #3d4f66;
        margin-bottom: 10px;
        border-radius: 15px !important;
        overflow: hidden;
    }
    .accordion-header .accordion-button {
        background-color: rgba(30,41,59,0.95);
        color: #e2e8f0;
        font-weight: 500;
        font-size: 1.1rem;
        box-shadow: none;
    }
    .accordion-header .accordion-button:not(.collapsed) {
        background-color: #1e2b3b;
        color: #38bdf8;
    }
    .accordion-header .accordion-button::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23e2e8f0'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    }
    .accordion-body {
        padding: 25px 30px;
    }

    .form-label {
        font-weight: 500;
        color: #e2e8f0;
    }
    .form-control {
        background-color: rgba(0,0,0,0.3);
        border: 1px solid #4a5a70;
        color: #e2e8f0;
        padding-right: 40px;
    }
    .form-control:focus {
        background-color: rgba(0,0,0,0.4);
        border-color: #38bdf8;
        box-shadow: 0 0 0 0.25rem rgba(56,189,248,.25);
        color: #e2e8f0;
    }
    .btn-primary {
        background-color: #2563eb;
        border: none;
    }

    .toggle-password-icon {
        position: absolute;
        right: 15px;
        bottom: 12px;
        color: #e2e8f0;
        cursor: pointer;
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
                 <li><a class="dropdown-item" href="my_account.php">My Account</a></li>
                <li><a class="dropdown-item" href="settings.php">Settings</a></li>
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

    <h1 class="text-center text-light mt-4">Security Settings</h1>

    <div class="settings-container">
        <div class="mt-3">
            <?php echo $message; ?>
        </div>

        <div class="accordion mt-3" id="settingsAccordion">

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingName">
                    <button class="accordion-button <?php echo ($active_accordion != 'name') ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseName" aria-expanded="<?php echo ($active_accordion == 'name') ? 'true' : 'false'; ?>" aria-controls="collapseName">
                        Change Name
                    </button>
                </h2>
                <div id="collapseName" class="accordion-collapse collapse <?php echo ($active_accordion == 'name') ? 'show' : ''; ?>" aria-labelledby="headingName" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        <form method="POST" action="settings.php">
                            <div class="mb-3">
                                <label class="form-label">Current Username: <?php echo htmlspecialchars($_SESSION['username']); ?></label>
                            </div>
                            <div class="mb-3">
                                <label for="new_username" class="form-label">New Username</label>
                                <input type="text" class="form-control" id="new_username" name="new_username" required>
                            </div>
                            <div class="mb-3 position-relative">
                                <label for="current_password_for_name" class="form-label">Current Password (for security)</label>
                                <input type="password" class="form-control" id="current_password_for_name" name="current_password_for_name" required>
                                <i class="bi bi-eye-slash toggle-password-icon" data-target-id="current_password_for_name"></i>
                            </div>
                            <button type="submit" name="action" value="change_name" class="btn btn-primary">Update Name</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button <?php echo ($active_accordion != 'pass') ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePass" aria-expanded="<?php echo ($active_accordion == 'pass') ? 'true' : 'false'; ?>" aria-controls="collapsePass">
                        Change Password
                    </button>
                </h2>
                <div id="collapsePass" class="accordion-collapse collapse <?php echo ($active_accordion == 'pass') ? 'show' : ''; ?>" aria-labelledby="headingOne" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        <form method="POST" action="settings.php">
                            <div class="mb-3 position-relative">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <i class="bi bi-eye-slash toggle-password-icon" data-target-id="current_password"></i>
                            </div>
                            <div class="mb-3 position-relative">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <i class="bi bi-eye-slash toggle-password-icon" data-target-id="new_password"></i>
                            </div>
                            <div class="mb-3 position-relative">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <i class="bi bi-eye-slash toggle-password-icon" data-target-id="confirm_password"></i>
                            </div>
                            <button type="submit" name="action" value="change_pass" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button <?php echo ($active_accordion != 'email') ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEmail" aria-expanded="<?php echo ($active_accordion == 'email') ? 'true' : 'false'; ?>" aria-controls="collapseEmail">
                        Update Email Address
                    </button>
                </h2>
                <div id="collapseEmail" class="accordion-collapse collapse <?php echo ($active_accordion == 'email') ? 'show' : ''; ?>" aria-labelledby="headingTwo" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        <form method="POST" action="settings.php">
                            <div class="mb-3">
                                <label class="form-label">Current Email: <?php echo htmlspecialchars($userEmail ?? 'Not Set'); ?></label>
                            </div>
                            <div class="mb-3">
                                <label for="new_email" class="form-label">New Email Address</label>
                                <input type="email" class="form-control" id="new_email" name="new_email" placeholder="Enter new email or leave blank to remove">
                            </div>
                            <div class="mb-3 position-relative">
                                <label for="current_password_for_email" class="form-label">Current Password (for security)</label>
                                <input type="password" class="form-control" id="current_password_for_email" name="current_password_for_email" required>
                                <i class="bi bi-eye-slash toggle-password-icon" data-target-id="current_password_for_email"></i>
                            </div>
                            <button type="submit" name="action" value="update_email" class="btn btn-primary">Update Email</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button text-danger <?php echo ($active_accordion != 'delete') ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDelete" aria-expanded="<?php echo ($active_accordion == 'delete') ? 'true' : 'false'; ?>" aria-controls="collapseDelete">
                        Delete Account
                    </button>
                </h2>
                <div id="collapseDelete" class="accordion-collapse collapse <?php echo ($active_accordion == 'delete') ? 'show' : ''; ?>" aria-labelledby="headingThree" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        <form method="POST" action="settings.php" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                            <p class="text-danger"><strong>Warning:</strong> This action is permanent and cannot be undone. All your scans and account data will be permanently deleted.</p>
                            <div class="mb-3 position-relative">
                                <label for="current_password_for_delete" class="form-label">Type Your Password to Confirm</label>
                                <input type="password" class="form-control" id="current_password_for_delete" name="current_password_for_delete" required>
                                <i class="bi bi-eye-slash toggle-password-icon" data-target-id="current_password_for_delete"></i>
                            </div>
                            <button type="submit" name="action" value="delete_account" class="btn btn-danger">Delete My Account Permanently</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="text-center mt-4">
        <a href="index.php" class="btn btn-outline-light">&larr; Back to Dashboard</a>
    </div>

</div>

<footer class="text-center mt-4 mb-3">
    <small>© 2025 Web Scanner | Built with PHP + Docker</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleIcons = document.querySelectorAll('.toggle-password-icon');

    toggleIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target-id');
            const targetInput = document.getElementById(targetId);

            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                this.classList.remove('bi-eye-slash');
                this.classList.add('bi-eye');
            } else {
                targetInput.type = 'password';
                this.classList.remove('bi-eye');
                this.classList.add('bi-eye-slash');
            }
        });
    });
});
</script>
</body>
</html>