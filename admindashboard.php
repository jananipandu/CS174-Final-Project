<?php

session_start();

require_once './login.php';

$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) {
    die($conn->connect_error);
}


if (isset($_SESSION['username'])) {
    echo <<<_END
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Virus Checker</title>
    <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>
    <style>
        * {
            font-family: 'Inter';
        }

        .container {
            margin: 0 auto;
            padding: 50px;
            display: flexbox;
            height: fit-content;
            width: fit-content;
        }

        .logo-div {
            width: fit-content;
            height: fit-content;
            margin: 15px;
        }

        #logo-name {
            font-size: 36px;
            font-weight: 200;
        }

        .admin-dash-label {
            text-align: left;
            font-size: 28px;
            margin: 20px;
            font-weight: 100;
        }

        .upload-container {
            margin: auto;
            width: fit-content;
            height: fit-content;
            font-size: 16px;
            display: grid;
            gap: 10px;
        }

        #divider {
            size: "pixels";
            margin: 20px;
        }

        .btn {
            margin-right: 20px;
            background-color: #B8DBE0;
            width: 240px;
            height: 60px;
            font-size: 22px;
            border-radius: 20px;
        }

        .prompt {
            font-size: 20px;
            font-weight: 500;
            text-align: center;
        }

        #upload {
            font-size: 16px;
            display: none;
        }

        #submit-btn {
            background-color: #B8DBE0;
            border: none;
            padding: 5px;
            font-size: 16px;
            top: 50%;
            left: 50%;
            border-radius: 20px;
            width: 100px;
            margin: auto;
        }

        #logout-btn {
            background-color: #B8DBE0;
            border: none;
            padding: 5px;
            font-size: 16px;
            border-radius: 20px;
            width: 100px;
            margin: auto;
        }

        #malware-input {
            width: 250px;
            height: 30px;
            margin-bottom: 20px;
            border-radius: 15px;
            padding: 15px;
        }

        #inserted {
            width: 200px;
            text-align: center;
            word-wrap: break-word;
            margin-bottom: 15px;
            margin: auto;
        }

        .header-div {
            width: 100%;
            display: flex;
            flex-direction: row;
            gap: 1000px;
        }

        #error-msg{
            margin-top: 50px;
            color: red
        }

    </style>
   <script>
        function logout() {
            fetch('admindashboard.php', { 
                method: 'POST',
                body: new URLSearchParams({
                    'logoutBtn': true  // needed to send this in to register as logout
                })
            })
            .then(response => {
                if (response.ok) {
                    window.location.href = 'homepage.html';
                } else {
                    return "Logout failed.";
                }
            })
            .catch(error => {
                console.log('Error:', error)
            });
        }

        function onUpload(form) {
            let fail = "";
            const errorMsgElement = document.getElementById('error-msg');

            if (form.upload.value === "") {
                fail = "Please upload a file! ";
            }

            fail += validateName(form.malwareName.value);

            if (fail !== "") {
                errorMsgElement.innerHTML = fail;
                return false;
            }

            return true;
        }

        function onFileUpload(field) {
            const filename = field.files[0].name;
            const fileNameInsert = document.getElementById('inserted');
            fileNameInsert.innerHTML = "Successfully inserted " + filename + "!";
        }

        function validateName(field) {
            if (field === "") {
                return "No name entered. Please enter a name.";
            } else if (field.length < 5) {
                return "Malware names must be at least 5 characters long.";
            } else if (!/[a-z]/.test(field) || !/[A-Z]/.test(field)) {
                return "Malware names must contain only letters (a-z and A-Z).";
            }

            return "";
        }
   </script>
</head>

<body>
    <div class="logo-div">
        <h3 id="logo-name">Online Virus Checker</h3>
    </div>
    <div class="header-div">
        <p class="admin-dash-label">Admin Dashboard</p>
        <button id="logout-btn" type='submit' name="logoutBtn" onclick="logout()">logout</button>
    </div>

    <hr id="divider">
    <div class="container">
        <form method='post' action="admindashboard.php" enctype="multipart/form-data" onsubmit="return onUpload(this)">
            <p class="prompt">Submit a Malware File and Name</p>
            <div class="upload-container">
                <label for="upload" class="upload-container" style="cursor: pointer;">
                    <img id="upload-img" src="/onlineviruschecker/Upward Arrow.jpg" />
                </label>
                <input id="upload" type='file' name='fileupload' title=" " onchange="onFileUpload(this)" size='20'>
                <p id="inserted"></p>
                <label id="malware-label">Malware name</label>
                <input id="malware-input" type="text" name="malwareName" placeholder="Can contain a-z A-Z or 0-9">
            </div>
            <input id="submit-btn" class="upload-container" type='submit' value='Upload'>
        </form>
        <p id="error-msg"></p>
    </div>

</html>

_END;

    if (isset($_POST['logoutBtn'])) {
        logout();
        header("Location: homepage.html");
    }

    if (isset($_FILES['fileupload']) && isset($_POST['malwareName'])) {
        $name = $_FILES['fileupload']['name'];
        $malware_name = $_POST['malwareName'];

        // santizing the filename just in case of any code present
        $name = strtolower(preg_replace("/[^A-Za-z0-9.]/", "", $name));

        move_uploaded_file($_FILES['fileupload']['tmp_name'], $name);
        // opening a file in the binary mode to read the bytes
        $fh = fopen($_FILES['fileupload']['tmp_name'], 'rb');

        // reads the first 20 bytes of the file, wrap in try catch soon
        $first20 = fread($fh, 20);
        insertIntoDB($conn, $first20, $malware_name);
    }

} else {
    echo "<p style='text-align: center;'>Looks like you aren't signed in. <a href='adminlogin.php'>Please sign in here!</a></p>";
}

function logout() {
    session_destroy();
    session_regenerate_id(true);
}

function insertIntoDB($conn, $first20, $malware_name) {
    $malware_name = sanitizeMySQL($conn, $malware_name);

    // inserting via placeholders for added protection
    $stmt = $conn->prepare('INSERT INTO malware VALUES(?,?)');
    $stmt->bind_param('ss', $malware_name, $first20);

    $result = $stmt->execute();

    if (!$result) {
        $stmt->close();
        die("<p>Could not insert file into our systems. Please try again.</p></body>");
    } else {
        $stmt->close();

        // continue to the first page
        echo "<p style='text-align: center;'>Succesfully inserted $malware_name!</p>";
    }


}

function sanitizeString($var)
{
    $var = stripslashes($var);
    $var = strip_tags($var);
    $var = htmlentities($var);
    return $var;
}

// used from the Lec. 10 slides for sanitizing the MySQL and other strings
function sanitizeMySQL($conn, $var)
{
    $var = $conn->real_escape_string($var);
    $var = sanitizeString($var);
    return $var;
}

?>
