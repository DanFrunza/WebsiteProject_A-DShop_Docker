<?php

include("database.php");
session_start();

///////////////////////////////////////

function createCheckUserProcedure($pdo) {
  try {
    
    $dropProcedureSQL = "DROP PROCEDURE IF EXISTS check_and_insert_user";
    $pdo->exec($dropProcedureSQL);

    
    $createProcedureSQL = "
    CREATE PROCEDURE check_and_insert_user(
        IN p_username VARCHAR(255),
        IN p_password VARCHAR(255),
        OUT p_user_exists INT
    )
    BEGIN
        DECLARE user_count INT;

        SELECT COUNT(*) INTO user_count
        FROM users
        WHERE user = p_username;

        IF user_count > 0 THEN
            SET p_user_exists = 1;
        ELSE
            SET p_user_exists = 0;
            INSERT INTO users (user, password) VALUES (p_username, p_password);
        END IF;
    END;
    ";
    $pdo->exec($createProcedureSQL);
  } catch (PDOException $e) {
    
    throw new Exception("Eroare la crearea procedurii: " . $e->getMessage());
  }
}


function createInsertUserTrigger($pdo) {
  try {
    
    $dropTriggerSQL = "DROP TRIGGER IF EXISTS insert_user_trigger";
    $pdo->exec($dropTriggerSQL);

    
    $createTriggerSQL = "
    CREATE TRIGGER insert_user_trigger AFTER INSERT ON users
    FOR EACH ROW
    BEGIN
      INSERT INTO user_creation_log (user_id, user, creation_date) VALUES (NEW.id, NEW.user, NOW());
    END;
    ";
    $pdo->exec($createTriggerSQL);
  } catch (PDOException $e) {
    
    throw new Exception("Eroare la crearea trigger-ului: " . $e->getMessage());
  }
}



//////////////////////////////////////////////////////////////////////


createCheckUserProcedure($pdo);
createInsertUserTrigger($pdo);

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $recaptchaResponse = $_POST['g-recaptcha-response'];

    // Verificați răspunsul reCAPTCHA
    $secretKey = "6LfpzRMpAAAAAG-ajMLxFCmjNyn5f3ZJMeser_r7"; // Înlocuiți cu cheia secretă reCAPTCHA
    $verifyUrl = "https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$recaptchaResponse}";
    $response = file_get_contents($verifyUrl);
    $responseData = json_decode($response);
    if ($responseData->success) {
        $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
        $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);

        if(empty($username)){
            $username_error= "<p class='error-message'>Please enter a username</p>";
        } elseif(empty($password)){
            $password_error= "<p class='error-message'>Please enter a password</p>";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $userExists = 0; 
            $stmt = $pdo->prepare("CALL check_and_insert_user(?, ?, @user_exists)");
            $stmt->execute([$username, $hash]); 
            $stmt->closeCursor();

            
            $sql = "SELECT @user_exists AS user_exists";
            $result = $pdo->query($sql);
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $userExists = $row['user_exists'];

            $sql = "SELECT * FROM users WHERE user = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if($userExists){
                if(!password_verify($password, $row["password"])){
                    $password_error="<p class='error-message'> Incorrect password</p>";
                } else {
                    $_SESSION['username'] = $username;
                    if (isset($_POST['remember']) && $_POST['remember'] == 'on') {
                        setcookie('remember_username', $username, time() + (86400 * 30), "/");
                    } else {
                        setcookie('remember_username', '', time() - 3600, "/");
                    }
                    header("Location: index.php");
                    exit();
                }
            } else {
                $inregistrare = "<p> Te-ai înregistrat </p>";
            }
        }
    } else {
        $invalid_rechapcha = "<p>Rechapcha invalid</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>A&DShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <script src="Scripts/SvgButtons.js"></script>
    <link rel="stylesheet" href="Css/stylelogin.css">

</head>
<body>
  <section class="vh-100">
    <div class="container-fluid h-custom">
      <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-md-9 col-lg-6 col-xl-5">
          <img src="Images/logo.png"
            class="img-fluid" alt="Sample image">
        </div>
        <div class="col-md-8 col-lg-6 col-xl-4 offset-xl-1">
          <form class="divlogin" method="post">
            <div>
              <p class="lead fw-normal mb-0 me-3">Sign in with</p>
              <button type="button" class="btn btn-primary btn-floating mx-1" onclick="openFacebook()" style="background-color: transparent;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="blue" class="bi bi-facebook" viewBox="0 0 16 16">
                  <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/>
                </svg>
              </button>
  
              <button type="button" class="btn btn-primary btn-floating mx-1" onclick="openInstagram()" style="background-color: transparent;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="pink" class="bi bi-instagram" viewBox="0 0 16 16">
                  <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z"/>
                </svg>
              </button>
  
              <button type="button" class="btn btn-primary btn-floating mx-1"onclick="openGoogle()" style="background-color: transparent;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="red" class="bi bi-google" viewBox="0 0 16 16">
                  <path d="M15.545 6.558a9.42 9.42 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.689 7.689 0 0 1 5.352 2.082l-2.284 2.284A4.347 4.347 0 0 0 8 3.166c-2.087 0-3.86 1.408-4.492 3.304a4.792 4.792 0 0 0 0 3.063h.003c.635 1.893 2.405 3.301 4.492 3.301 1.078 0 2.004-.276 2.722-.764h-.003a3.702 3.702 0 0 0 1.599-2.431H8v-3.08h7.545z"/>
                </svg>
              </button>
            </div>
  
            <div class="divider d-flex align-items-center my-4">
              <p class="text-center fw-bold mx-3 mb-0">Or</p>
            </div>
  
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" text-align:center>
                <div class="form__group field">
                <input type="text" class="form__field" placeholder="Name"  name="username" size=55 required>
                <label for="name" class="form__label">Username</label>
                </div><br>
                <div class="form__group field">
                <input type="password" class="form__field" placeholder="Name"  name="password" size=55 required>
                <label for="name" class="form__label">Password</label>
                </div><br>
                
                <div class="d-flex justify-content-between align-items-center">
                  <!-- Checkbox -->
                  <div class="form-check mb-0">
                    <input class="form-check-input me-2" type="checkbox" value="on" id="form2Example3" name="remember"/>
                    <label class="form-check-label" for="form2Example3">
                      Remember me
                    </label>
                  </div>
                </div>
                <br><br>
                <div class="captcha-container">
                  <div align="center" class="g-recaptcha"  data-theme="dark" data-badge="inline" data-sitekey="6LfpzRMpAAAAAI_jwTNMrFELO9g5u2aTwALUQNhA"></div>
                </div>
                <br>
                <div class="button_submit">
                <button class="button-82-pushable" role="button" type="submit" name="submit">
                  <span class="button-82-shadow"></span>
                  <span class="button-82-edge"></span>
                  <span class="button-82-front text">
                    Login/Register
                  </span>
                </button>
                </div>
                <div class="error-container">
                
                  <?php
                  
                  if (!empty($username_error)) {
                      echo "<p class='error-message'>$username_error</p>";
                  }
                  if (!empty($password_error)) {
                      echo "<p class='error-message'>$password_error</p>";
                  }
                  if (!empty($inregistrare)) {
                    echo "<p class='error-message'>$inregistrare</p>";
                  }
                  if (!empty($invalid_rechapcha)) {
                    echo "<p class='error-message'>$invalid_rechapcha</p>";
                  }
                  
                  ?>
                </div>
                

            </form>
          </form>
        </div>
      </div>
    </div>
  </section>
</body>
</html>

