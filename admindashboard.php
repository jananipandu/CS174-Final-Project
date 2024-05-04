<?php

session_start();

require_once './login.php';

$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) {
    die ($conn->connect_error);
}

if(isset($_SESSION['username'])){
echo <<<_END
<html> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Online Virus Checker</title> <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'> <style> * { font-family: 'Inter'; } .container { margin: 0 auto; padding: 50px; display: flexbox; height: fit-content; width: fit-content; } .logo-div { width: fit-content; height: fit-content; margin: 15px; } #logo-name { font-size: 36px; font-weight: 200; } .admin-dash-label { text-align: left; font-size: 28px; margin: 20px; font-weight: 100; } .upload-container { margin: auto; width: fit-content; height: fit-content; font-size: 16px; display: grid; gap: 10px; } #divider { size: "pixels"; margin: 20px } .btn { margin-right: 20px; background-color: #B8DBE0; width: 240px; height: 60px; font-size: 22px; border-radius: 20px; } .prompt { font-size: 20px; font-weight: 500; } #upload { font-size: 16px; display: none; } #submit-btn { background-color: #B8DBE0; border: none; padding: 5px; font-size: 16px; top: 50%; left: 50%; border-radius: 20px; width: 100px; } #malware-input { width: 250px; } #malware-input { width: 250px; height: 30px; margin-bottom: 20px; border-radius: 15px; padding: 15px; } #inserted { width: 200px; text-align: center; word-wrap: break-word; margin-bottom: 15px; margin: auto; } </style> <script> function onUpload(form) { let fail = ""; const errorMsgElement = document.getElementById('error-msg'); if (form.upload.value === "") { fail += "Please upload a file!\n"; alert(fail); return false; } fail += validateName(form.malwareName.value); if (fail !== "") { alert(fail); return false; } return true; } function onFileUpload(field) { const filename = field.files[0].name; const fileNameInsert = document.getElementById('inserted'); fileNameInsert.innerHTML = "Successfully inserted " + filename + "!"; } function validateName(field) { if (field === "") { return "No name entered. Please enter a name."; } else if (field.length < 5) { return "Malware names must be at least 5 characters long."; } else if (!/[a-z]/.test(field) || !/[A-Z]/.test(field) || !/[0-9]/.test(field)) { return "Malware names must contain at least one of each: a-z, A-Z, 0-9."; } return ""; } </script> </head> <body> <div class="logo-div"> <h3 id="logo-name">Online Virus Checker</h3> </div> <p class="admin-dash-label">Admin Dashboard</p> <hr id="divider"> <div class="container"> <form method='post' action="admindashboard.php" enctype="multipart/form-data" onsubmit="return onUpload(this)"> <p class="prompt">Submit a Malware File and Name</p> <div class="upload-container"> <label for="upload" class="upload-container" style="cursor: pointer;"> <img id="upload-img" src="/onlineviruschecker/Upward Arrow.jpg" /> </label> <input id="upload" type='file' name='fileupload' title=" " onchange="onFileUpload(this)" size='10'> <p id="inserted"></p> <label id="malware-label">Malware name</label> <input id="malware-input" type="text" name="malwareName" placeholder="Can contain a-z A-Z or 0-9"> </div> <input id="submit-btn" class="upload-container" type='submit' value='Upload'> </form> </div> </body>
_END;

    if(isset($_FILES['upload']) && isset($_POST['malwareName'])){
        $name = $_FILES['upload']['name'];
        $malware_name = $_POST['malware_name'];

        // santizing the filename just in case of any code present
        $name = strtolower(preg_replace("/[^A-Za-z0-9.]/", "", $name)); 

        move_uploaded_file($_FILES['upload']['tmp_name'], $name);
        // opening a file in the binary mode to read the bytes
        $fh = fopen($_FILES['upload']['tmp_name'], 'rb');

        // reads the first 20 bytes of the file, wrap in try catch soon
        $first20 = fread($fh, 20);
        insertIntoDB($conn, $first20, $malware_name);
    }

    logout();

} else {
    echo "<p>Looks like you aren't signed in. <a href='adminlogin.php'>Please sign in here!</a></p>";
}


function logout(){
    session_destroy();
    session_regenerate_id();
}

function insertIntoDB($conn, $first20, $malware_name){
    $malware_name = sanitizeMySQL($conn, $malware_name);

    // inserting via placeholders for added protection
    $stmt = $conn->prepare('INSERT INTO malware VALUES(?,?)');
    $stmt->bind_param('ss', $malware_name, $first20);

    $result = $stmt->execute();

    if (!$result){
        $stmt->close();
        die ("<p>Could not insert file into our systems. Please try again.</p>");
    } else {
        $stmt->close();

        // continue to the first page
        die("<p>Succesfully inserted $malware_name!</p>");
    }

    
}

function sanitizeString($var) {
    $var = stripslashes($var);
    $var = strip_tags($var);
    $var = htmlentities($var);
    return $var;
}

// used from the Lec. 10 slides for sanitizing the MySQL and other strings
function sanitizeMySQL($conn, $var) {
    $var = $conn->real_escape_string($var);
    $var = sanitizeString($var);
    return $var;
}



?>