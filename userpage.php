<?php

session_start();
require_once './login.php';

$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) {
    die ($conn->connect_error);
}

if (isset($_SESSION['username'])) {
echo <<< _END
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

        .upload-container {
            margin: auto;
            width: fit-content;
            height: fit-content;
            font-size: 16px;
            display: grid;
            gap: 10px;
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
        }

        #inserted {
            width: fit-content;
            text-align: center;
            word-wrap: break-word;
            margin-bottom: 15px;
        }

        .infected-div{
            margin: auto;
            width: 60%;
            padding: 20px;
            background-color: rgb(237, 179, 157);
            border-radius: 25px;
        }

        .putative-infected-div{
            margin: auto;
            width: 60%;
            padding: 20px;
            background-color: rgb(239, 233, 198);
            border-radius: 25px;
        }

        .type{
            font-size: 20px;
            font-weight: 400;
            
        }

        #errormsg{
            color: red;
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

        .header-div {
            width: 100%;
            display: flex;
            flex-direction: row;
            gap: 1000px;
        }

        .user-dash-label{
            text-align: left;
            font-size: 28px;
            margin: 20px;
            font-weight: 100;
        }
    </style>
    <script> 
        function logout() {
            fetch('userpage.php', { 
                method: 'POST',
                body: new URLSearchParams({
                    'returnBtn': true  // needed to send this in to register as logout
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

        function onUpload(field) {
            if (field.upload.value == "") {
                var errElem = document.getElementById('errormsg');
                errElem.innerHTML = "File is not inserted! Please upload a file!"; 
                return false;
            } else {
                return true;
            }
        }
    
        function onFileUpload(field) {
            var filename = field.files[0].name;
            var fileNameInsert = document.getElementById('inserted');
            fileNameInsert.innerHTML = "Successfully inserted " + filename + "!";
        } 
    </script>
</head>

<body>
    <div class="logo-div">
        <h3 id="logo-name">Online Virus Checker</h3>
    </div>
    <div class="header-div">
        <p class="user-dash-label">User Dashboard</p>
        <button id="logout-btn" type='submit' name="returnBtn" onclick="logout()">logout</button>
    </div>
    <hr id="divider">
    <div class="container">
        <form method='post' action='userpage.php' enctype="multipart/form-data" onsubmit="return onUpload(this)">
            <p class="prompt">Select a file to inspect for viruses</p>
            <div class="upload-container"> <label for="upload" class="upload-container" style="cursor: pointer;"> <img
                        id="upload-img" src="/onlineviruschecker/Upward Arrow.jpg" /> </label> <input id="upload"
                    type='file' name='fileupload' title=" " onchange="onFileUpload(this)" size='10'>
                <p id="inserted"></p>
            </div> <input id="submit-btn" class="upload-container" type='submit' value='Upload'>
        </form>
        <p id="errormsg"></p>
    </div>
_END;

if(isset($_FILES['fileupload'])){
    $name = $_FILES['fileupload']['name'];

    // santizing the filename just in case of any code present
    $name = strtolower(preg_replace("/[^A-Za-z0-9.]/", "", $name)); 

    move_uploaded_file($_FILES['fileupload']['tmp_name'], $name);

    // opening a file in the binary mode to read the bytes
    $fh = fopen($_FILES['fileupload']['tmp_name'], 'rb');

    // reads the first 20 bytes of the file, wrap in try catch soon
    $filebytes = fread($fh, filesize($_FILES['fileupload']['tmp_name']));
    $infected = checkMalware($conn, $filebytes);

    if($infected){
        // show infected file dialog
        echo "<div class='infected-div'> <p class='type'>🚨 Infected File</p> <p class='description'>This file contains a malware signature that is recognized in our system. Please delete it immediately and get your device checked.</p> </div></body>";
    } else {
        echo "<div class='putative-infected-div'> <p class='type'>⚠️ Putative Infected File</p> <p class='description'>This file does not contain any malware signatures according to our system. However, we recommend you get expert opinions on this file.</p> </div></body>";
    }
}

if($_POST['returnBtn']){
    logout();
    header("Location: homepage.html");
}

} else {
    echo "<p style='text-align: center;'>Looks like you aren't signed in. <a href='userlogin.php'>Please sign in here!</a></p>";
}


function logout() {
    session_destroy();
    session_regenerate_id(true);
}

function checkMalware($conn, $filebytes){
    $query = "SELECT SIGNATURE FROM malware";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
       // checks all of the malware signatures in the database against the file contents in bytes
       while($row = $result->fetch_assoc()){
            $bytesig = $row['SIGNATURE'];
            
            if(strpos($filebytes, $bytesig) !== false){
                return true; // if the malware sequences exist in the file, it returns true
            }
       }
       return false;
    } else {
       die("<p>There is an error with the database. Please try again.</p></body>");
    }
}
?>



