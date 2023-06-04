<?php
require 'vendor/autoload.php';
require 'config.php';

use Symfony\Component\HttpFoundation\Request;

$comm = "";

$servername = "localhost:3307";
$username = "root";
$password = "1234567890";
$dbname = "aggregator_system_database";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) !== true) {
    $comm = "Error creating database: " . $conn->error;
    $conn->close();
    exit();
}

$conn->close();

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    username VARCHAR(50) NOT NULL,
    userpassword VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL
)";
if ($conn->query($sql) !== true) {
    $comm = "Error creating table: " . $conn->error;
    $conn->close();
    exit();
}

if (isset($_POST["register_submit"])) {
    $fullname = $_POST["register_fullname"];
    $username = $_POST["register_usernm"];
    $password = $_POST["register_password"];
    $verify_password = $_POST["verify_password"];
    $email = $_POST["register_email"];
    $phone_country_code = $_POST["phone_country_code"];
    $phone_number = $_POST["register_phone"];

    // Check if username, email, or phone number already exists in the database
    $check_sql = "SELECT * FROM users WHERE username = ? OR email = ? OR phone = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("sss", $username, $email, $phone_country_code . $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $existing_records = "";
        while ($row = $result->fetch_assoc()) {
            $existing_records .= "Username: " . $row["username"] . ", Email: " . $row["email"] . ", Phone: " . $row["phone"] . "<br>";
        }
        $comm = '<div class="card card-margin alert-margin">User with the same username, email, or phone number already exists. Existing records:<br>' . $existing_records . '</div>';
    } else {
        // Validate password complexity
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number = preg_match('@[0-9]@', $password);
        $special_chars = preg_match('@[^\w]@', $password);

        if (!$uppercase || !$lowercase || !$number || !$special_chars || strlen($password) < 8) {
            $comm = '<div class="card card-margin alert-margin">Password should be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.</div>';
        } elseif ($password !== $verify_password) {
            $comm = '<div class="card card-margin alert-margin">Password and Verify Password do not match.</div>';
        } else {
            // Hash the password securely
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Add the user to the database
            $insert_sql = "INSERT INTO users (fullname, username, userpassword, email, phone) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("sssss", $fullname, $username, $hashed_password, $email, $phone_country_code . $phone_number);
            if ($stmt->execute()) {
                $comm = '<div class="card card-margin alert-margin">Registration successful.</div>';
            } else {
                $comm = "Error: " . $insert_sql . "<br>" . $conn->error;
            }
        }
    }
}

$conn->close();
?>

<html>
<head>
    <link rel="stylesheet" href="css/app.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/custom.css">
    <link rel="stylesheet" href="css/loginstyle.css">
    <style>
        .card-margin {
            margin: 50px auto;
            max-width: 600px;
            margin-top: 50px;
            margin-bottom: 50px;
            margin-left: 50px;
            margin-right:50px;
        }
        .form {
            margin: 50px auto;
            max-width: 500px;
            margin-top: 50px;
            margin-bottom: 50px;
            margin-left: 50px;
            margin-right:50px;
        }
        .alert-margin {
            margin-top: 50px;
        }
        .password-match {
            color: green;
        }
        .password-mismatch {
            color: red;
        }
    </style>
</head>
<body>
    <form method="POST" action="Registration.php" class="form text-center">
        <div class="registerstyle body">
            <div class="card">REGISTER</div>
            <div class="card" style="padding: 10px;">
                <div class="row col-12">
                    <div class="form-group col-md-12">
                        <label>Full Name</label>
                        <input type="text" name="register_fullname" class="form-control" required="true" />
                    </div>
                    <div class="form-group col-md-12">
                        <label>User Name</label>
                        <input type="text" name="register_usernm" class="form-control" required="true" />
                    </div>
                    <div class="form-group col-md-12">
                        <label>Password</label>
                        <input type="password" name="register_password" id="password" class="form-control" required="true" />
                    </div>
                    <div class="form-group col-md-12">
                        <label>Verify Password</label>
                        <input type="password" name="verify_password" id="verify_password" class="form-control" required="true" />
                        <div id="password_match_message"></div>
                    </div>
                    <div class="form-group col-md-12">
                        <label>Email</label>
                        <input type="email" name="register_email" class="form-control" required="true" />
                    </div>
                    <div class="form-group col-md-12">
                        <label>Phone</label>
                        <div class="row">
                            <div class="col-md-4">
                                <select name="phone_country_code" class="form-control">
                                    <option value="+1">+1</option>
                                    <option value="+91">+91</option>
                                    <!-- Add more country codes as needed -->
                                </select>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="register_phone" class="form-control" required="true" />
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-12">
                        <input type="submit" name="register_submit" class="btn btn-danger btn-md" value="Register" />
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <?php echo $comm; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function () {
            $("#verify_password").keyup(function () {
                var password = $("#password").val();
                var verify_password = $(this).val();
                if (password === verify_password) {
                    $("#password_match_message").html("<span class='password-match'>Passwords match.</span>");
                } else {
                    $("#password_match_message").html("<span class='password-mismatch'>Passwords do not match.</span>");
                }
            });
        });
    </script>
</body>
</html>
