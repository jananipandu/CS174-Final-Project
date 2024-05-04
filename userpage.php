<?php
ini_set('display_errors', 1);
require_once './login.php';

$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) {
    die ($conn->connect_error);
}

echo <<< _END
<html> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Online Virus Checker</title> <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'> <style> *{ font-family: 'Inter'; } .container{ margin: 0 auto; padding: 50px; display: flexbox; height: fit-content; width: fit-content; } .logo-div{ width: fit-content; height: fit-content; margin: 15px; } #logo-name { font-size: 36px; font-weight: 200; } .upload-container { margin: auto; width: fit-content; height: fit-content; font-size: 16px; display: grid; gap: 10px; } .btn{ margin-right: 20px; background-color: #B8DBE0; width: 240px; height: 60px; font-size: 22px; border-radius: 20px; } .prompt{ font-size: 20px; font-weight: 500; } #upload{ font-size: 16px; display: none; } #submit-btn{ background-color: #B8DBE0; border: none; padding: 5px; font-size: 16px; top: 50%; left: 50%; border-radius: 20px; width: 100px; } #inserted{ width: fit-content; text-align: center; word-wrap: break-word; margin-bottom: 15px; } </style> <script> function onUpload(field){ if(field.upload.value == "") alert("Please choose a file!"); } function onFileUpload(field){ var filename = field.files[0].name; var fileNameInsert = document.getElementById('inserted'); fileNameInsert.innerHTML = "Successfully inserted " + filename + "!"; } </script> </head> <body> <div class="logo-div"> <h3 id="logo-name">Online Virus Checker</h3> </div> <div class="container"> <form method='post' action='userpage.php' enctype="multipart/form-data" onsubmit="return onUpload(this)"> <p class="prompt">Select a file to inspect for viruses</p> <div class="upload-container"> <label for="upload"  class="upload-container" style="cursor: pointer;"> <img id="upload-img" src="/onlineviruschecker/Upward Arrow.jpg"/> </label> <input id="upload" type='file' name='fileupload' title=" "  onchange="onFileUpload(this)" size='10'> <p id="inserted"></p> </div> <input id="submit-btn" class="upload-container" type='submit' value='Upload' > </form> </div> 
_END;

if(isset($_FILES['upload'])){
    $name = $_FILES['upload']['name'];

    // santizing the filename just in case of any code present
    $name = strtolower(preg_replace("/[^A-Za-z0-9.]/", "", $name)); 

    move_uploaded_file($_FILES['upload']['tmp_name'], $name);

    // opening a file in the binary mode to read the bytes
    $fh = fopen($_FILES['upload']['tmp_name'], 'rb');

    // reads the first 20 bytes of the file, wrap in try catch soon
    $first20 = fread($fh, 20);
    $infected = checkMalware($conn, $first20);

    if($infected){
        // show infected file dialog
        echo "<div class='infected-div'> <p class='type'>🚨 Infected File</p> <p class='description'>This file contains a malware signature that is recognized in our system. Please delete it immediately and get your device checked.</p> </div></body>";
    } else {
        echo "<div class='putative-infected-div'> <p class='type'>⚠️ Putative Infected File</p> <p class='description'>This file does not contain any malware signatures according to our system. However, we recommend you get expert opinions on this file.</p> </div></body>";
    }
}

function checkMalware($conn, $first20){
    var_dump($first20);
    $stmt = $conn->prepare('SELECT * FROM malware WHERE malware.signature=?');
    $stmt->bind_param('s', $first20);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result && $result->num_rows > 0) {
       return true;
    } else {
       return false;
    }
}
?>

