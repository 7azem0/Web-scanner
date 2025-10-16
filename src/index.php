<?php
$servername = getenv("MYSQL_HOST");
$username = getenv("MYSQL_USER");
$password = getenv("MYSQL_PASSWORD");
$database = getenv("MYSQL_DB");

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Woking...Not good: " . $conn->connect_error);
}

echo "<h1>Working...Good enough</h1>";

$sql = "SELECT NOW() AS `current_time`;";
$result = $conn->query($sql);

if ($row = $result->fetch_assoc()) {
    echo "<p>Current time (from DB): " . $row['current_time'] . "</p>";
}


$conn->close();
?>
