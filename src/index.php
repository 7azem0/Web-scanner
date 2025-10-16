<?php
$servername = getenv("MYSQL_HOST");
$username = getenv("MYSQL_USER");
$password = getenv("MYSQL_PASSWORD");
$database = getenv("MYSQL_DB");

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Scanner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <span class="navbar-brand mb-0 h1">Web Scanner</span>
  </div>
</nav>

<div class="container">
    <div class="card shadow-sm p-4 mb-4">
        <h3 class="mb-3">Database Connection</h3>
        <?php
        $sql = "SELECT NOW() AS currentTime;";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    $time = reset($row); 
    echo "<div class='alert alert-success'>Working | Current DB Time: $time</div>";

        } else {
            echo "<div class='alert alert-danger'>Failed</div>";
        }
        ?>
    </div>

    
</div>

<footer class="text-center mt-5 mb-3 text-muted">
    <small>© 2025 Web Scanner | Built with PHP + Docker</small>
</footer>

</body>
</html>
