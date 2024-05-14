<?php

require_once './login.php';

$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) {
    die ($conn->connect_error);
}

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

        .admin-login-txt {
            font-size: 22px;
            text-align: center;
        }

        .login-div {
            display: grid;
            border: 1px black solid;
            padding: 50px;
            margin: auto;
            border-radius: 10px;
        }

        .login-labels {
            margin-bottom: 5px;
        }

        .login-boxes {
            width: 300px;
            height: 40px;
            border: 1px black solid;
            padding: 10px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        #submit-btn {
            background-color: #B8DBE0;
            border: none;
            padding: 5px;
            font-size: 16px;
            border-radius: 20px;
            width: 100px;
            margin: auto;
        }
    </style>
    <script> function validateForm(form) { fail = ""; if (form.username.value == "") { fail += "Please enter your username.\n" } if (form.password.value == "") { fail += "Please enter your password.\n" } if (fail != "") { alert(fail); } } </script>
</head>

<body>
    <div class="logo-div">
        <h3 id="logo-name">Online Virus Checker</h3>
    </div>
    <div class="container">
        <form method='post' action='userlogin.php' enctype="multipart/form-data" onsubmit="return onUpload(this)">
            <div class="login-div">
                <h6 class="admin-login-txt">User Login</h6> <label class="login-labels">Username</label> <input
                    class="login-boxes" type="text" name="username" maxlength="20" placeholder="username"> <label
                    class="login-labels">Password</label> <input class="login-boxes" type="password" name="password"
                    maxlength="20" placeholder="password"> 
                <p style='text-align: center; font-size: 12px;'>Don't have an account? <a href='adminSignUp.php'>Sign up here!</a></p>
                <input id="submit-btn" type="submit" name="login"
                    value="login">
            </div>
        </form>
        
    </div>
</body>
_END;

if(isset($_POST['username']) && isset($_POST['password'])){ // for login
    login($conn, $_POST['username'], $_POST['password']);
} 

function login($conn, $un, $pword){
    $type='user';

    $un_temp = sanitizeMySQL($conn, $un);
    $pw_temp = sanitizeMySQL($conn, $pword);

    $stmt = $conn->prepare('SELECT * FROM credentials WHERE username=? AND usertype=?');
    $stmt->bind_param('ss', $un_temp, $type);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        die($conn->error);
    } elseif ($result->num_rows) {

        $row = $result->fetch_assoc();
        $result->close();

        $pwhash= hash_pw($pw_temp);

        if ($pwhash == $row['PASSWORD']){

            session_start();
            $_SESSION['username'] = $un_temp;

            header("Location: userpage.php");

        } else {
            die("<p style='text-align: center; color: red;'>Invalid username/password combination! Please try again.</p>");
        }
    } else {
        die("<p style='text-align: center; color: red'>Invalid username/password combination! Please try again.</p>");
    }
    
}

function hash_pw($pw){
    $salt1 = "h*&jp";
    $salt2 = "@u!s";

    return hash('ripemd128', "$salt1$pw$salt2");
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
