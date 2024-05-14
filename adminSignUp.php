<?php

require_once './login.php';

$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) {
    die ($conn->connect_error);
}

function validateForm($fullname, $username, $password, $usertype) {
    $fail = validateFullname($fullname);
    $fail .= validateUsername($username);
    $fail .= validatePassword($password);
    $fail .= validateUserType($usertype);

    if ($fail === "") {
        return "";
    } else {
        return $fail;
    }
}

function validateFullname($field) {
    return ($field === "") ? "Please enter your full name. " : "";
}

function validateUserType($field){
    return ($field === "") ? "Please enter a type of user: Admin or User. " : "";
}

function validateUsername($field) {
    if ($field === "") {
        return "Please enter your username. ";
    } elseif (strlen($field) < 5) {
        return "Username must be at least 5 characters long. ";
    } elseif (preg_match('/[^a-zA-Z0-9_-]/', $field)) {
        return "Only a-z, A-Z, 0-9, - and _ are allowed in username. ";
    }
    return "";
}

function validatePassword($field) {
    if ($field === "") {
        return "No password entered. ";
    } elseif (strlen($field) < 8) {
        return "Password must be at least 8 characters long. ";
    } elseif (!preg_match('/[a-z]/', $field) || !preg_match('/[A-Z]/', $field) || !preg_match('/[0-9]/', $field)) {
        return "Password requires at least one lowercase letter, one uppercase letter, and one digit. ";
    }
    return "";
}

echo <<<_END
<html> 
    <head> 
        <meta charset="UTF-8"> 
        <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
        <title>Online Virus Checker</title> 
        <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'> 
        <style> 
            *{ font-family: 'Inter'; } 
            .container{ 
                margin: 0 auto; 
                padding: 50px; 
                display: flexbox; 
                height: fit-content; 
                width: fit-content; 
            } 
            .logo-div{ 
                width: fit-content; 
                height: fit-content; 
                margin: 15px; 
            } 
            #logo-name { 
                font-size: 36px; 
                font-weight: 200; 
            } 
            .admin-login-txt{ 
                font-size: 22px; 
                text-align: center; 
            } 
            .login-div{ 
                display: grid; 
                border: 1px black solid; 
                padding: 50px; 
                margin: auto; 
                border-radius: 10px; 
            } 
            .login-labels{ 
                margin-bottom: 5px; 
            } 
            .login-boxes{ 
                width: 300px; 
                height: 40px; 
                border: 1px black solid; 
                padding: 10px; 
                border-radius: 12px; 
                margin-bottom: 20px; 
            } 
            #submit-btn{ 
                background-color: #B8DBE0; 
                border: none; 
                padding: 5px; 
                font-size: 16px; 
                border-radius: 20px; 
                width: 100px; 
                margin: auto;
            }

            .user-type-label{ 
                text-align: center;
                margin-bottom: 5px; 
            }

            .user-type{ 
                background-color: #b6e3b9; 
                border: none; 
                padding: 5px; 
                font-size: 16px; 
                border-radius: 10px; 
                width: 100px; 
                margin: auto; 
            }
            .user-type-div{
                display: flex;
                margin-top: 15px;
                margin-bottom: 30px;
            }

            #usertype{
                margin: auto;
                font-size: 18px;
            }

        </style> 
    </head>
    <body> 
        <div class="logo-div"> 
            <h3 id="logo-name">Online Virus Checker</h3> 
        </div> 
        
        <div class="container"> 
            <form method='post' action='adminSignUp.php' enctype="multipart/form-data" 
            onsubmit="return validateForm(this)"> 
            <div class="login-div"> 
                <h6 class="admin-login-txt">Sign Up</h6> 
                <label class="login-labels">Full name</label> 
                <input class="login-boxes" type="text" name="fullname" 
                maxlength="20" placeholder="full name">
                <label class="login-labels">Username</label> 
                <input class="login-boxes" type="text" name="username" 
                maxlength="20" placeholder="username"> 
                <label class="login-labels">Password</label> 
                <input class="login-boxes" type="password" name="password" 
                maxlength="20" placeholder="password">
                
                <label class="user-type-label">Type of user:</label> 
                <div class='user-type-div'>
                    <select name="usertype" id="usertype">
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <button id="submit-btn" type='submit'>Sign up</button>
            </div>
            </form> 
        </div>
_END;

if(isset($_POST['fullname']) && isset($_POST['username']) && isset($_POST['password']) && isset($_POST['usertype'])){ // for admin sign up
    $validations = validateForm($_POST['fullname'], $_POST['username'], $_POST['password'], $_POST['usertype']);
    if($validations !== ""){
        die("<p style='text-align: center; color: red;'>$validations<p></body>");
    } else {
        signup($conn, $_POST['fullname'], $_POST['username'], $_POST['password'], $_POST['usertype']);
    }
} 

function signup($conn, $fullname, $username, $pword, $type){

    $un = sanitizeMySQL($conn, $username);
    $pw_clean = sanitizeMySQL($conn, $pword);
    $password = hash_pw($pw_clean);

    // inserting via placeholders for added protection
    $stmt = $conn->prepare('INSERT INTO credentials VALUES(?,?,?)');
    $stmt->bind_param('sss', $un, $password, $type);

    $result = $stmt->execute();

    if (!$result){
        $stmt->close();
        die ("<p>Error in account creation. Please try again!</p></body>");
    } else {
        $stmt->close();
        $url = "";
        if($type === 'admin'){
            $url = 'adminlogin.php';
        } else if($type == 'user'){
            $url = 'userlogin.php';
        }

        // continue to the first page
        die("<p style='text-align: center;'><a href='$url'>Welcome, $fullname! Click here to login...</a></p></body>");
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
