<?php

$servername = "localhost";
$username = "logger";
$password = "logger";
$database = "remotelogger";
$table = "log";

// Set this to true for setup to be able to run
$enable_setup = true;

if ($enable_setup && $_GET["setup"] === "true") {
    
    $conn = new mysqli($servername, $username, $password);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    echo "Connected to database<br/>";
    
    $sql = "CREATE DATABASE IF NOT EXISTS " . $database;
    if ($conn->query($sql) === TRUE) {
        echo "Database created or already existed<br/>";
    } else {
        die("Error creating database: " . $conn->error . "<br/>");
    }
    
    $conn->close();
    
    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create table
    $create_table = "CREATE TABLE IF NOT EXISTS " . $table . " (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
    data TEXT NOT NULL,
    page VARCHAR(1024) NOT NULL,
    stack TEXT NOT NULL,
    sender VARCHAR(30) NOT NULL,
    useragent VARCHAR(120) NOT NULL,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table) === TRUE) {
        echo "Table created or already existed<br/>";
    } else {
        die("Error creating table: " . $conn->error . "<br/>");
    }
    
    $conn->close();
} else if ($_GET["list"] === "true") {

    if ($_GET["token"] !== "abcudef") {
        die("Wrong token");
    }
    
    echo 
"<html>
<head>
    <title>Log view</title>
    <script src=\"https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.6.4/beautify.js\"></script>
</head>
<body>";
    
    $conn = new mysqli($servername, $username, $password, $database);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $query = "SELECT * FROM " . $table;
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        echo "<table>";
        while($row = $result->fetch_assoc()) {
            echo "<tr><td>ID: <code>" . $row["id"] . "</code></td><td>IP: <code>" . $row["sender"] . "</code></td><td>Time: <code>" . $row["created"] . "</code></td><td>Useragent: <code>" . $row["useragent"] . "</code></td></tr>";
            echo "<tr><td>Page:</td><td colspan=\"3\"><code>" . $row["page"] . "</code></td></tr>";
            echo "<tr><td>Data:</td><td colspan=\"3\" id=\"data" . $row["id"] . "\" style=\"max-width: 100px; overflow: auto; white-space: nowrap;\">";
            echo "<pre class=\"logdata\">" . $row["data"] . "</pre></td></tr>";
            echo "<tr><td>Stack:</td><td colspan=\"3\" style=\"max-width: 100px; overflow: auto; white-space: nowrap;\">";
            echo "<pre>" . $row["stack"] . "</pre></td></tr>";
        }
        echo "</table>";
    } else {
        die("No rows found");
    }
    
    echo
'
<script type="text/javascript">
    var elements = document.getElementsByClassName("logdata");
    for (var i = 0; i < elements.length; i++) {
        var js = js_beautify(elements[i].innerHTML);
        elements[i].innerHTML = js;
    }
</script>
</body>
</html>';
} else {
    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (array_key_exists("data", $_POST) === TRUE && array_key_exists("page", $_POST) === TRUE) {
        $data = $_POST["data"];
        $page = $_POST["page"];
        $stack = $_POST["stack"];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $sender = $_SERVER['REMOTE_ADDR'];
        
        if (strlen($data) > 65534) {
            $data = substr($data, 0, 65534);
        }
        if (strlen($stack) > 65534) {
            $stack = substr($stack, 0, 65534);
        }
        
        $insert = "INSERT INTO " . $table . " (data, page, sender, useragent, stack)" .
            " VALUES ('" . $conn->real_escape_string($data) . "', '" . 
                $conn->real_escape_string($page) . "', '". $conn->real_escape_string($sender) . "', '" . $conn->real_escape_string($user_agent) . "', '" . 
                $conn->real_escape_string($stack) . "')";
        
        if ($conn->query($insert) === TRUE) {
            echo "Inserted data";
        } else {
            http_response_code(400);
            die("Unable to insert data: " . $conn->error);
        }
    } else {
        http_response_code(400);
        die("No \"data\", \"page\" or \"stack\" POST argument");
    }
}


$conn->close();

?>
