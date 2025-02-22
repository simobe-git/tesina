<?php 

session_start();
if(isset($_SESSION['statoLogin'])){
    header('Location: home.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/menu.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="login-container">
        <div class="login-form">
            <h2>SIGN <span class="highlight">IN</span></h2>
            <form action="login-form.php" method="POST">
                
                <!-- Messaggi di errore se credenziali sbagliati o utente bannato -->
                <?php if(isset($_GET['error']) && ($_GET['error'] == '1')){ ?>
                    <div class="error-message">
                        Email o password non validi
                    </div>
                <?php }elseif(isset($_GET['error']) && ($_GET['error'] == 'banned')){ ?>
                    <div class="error-message">
                        Il tuo account è stato bannato <br> Motivo: <?php echo $_GET['motivo'] ?>
                    </div>
                <?php } ?>

                <div class="form-group">
                    <input type="email" id="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                <div class="form-group check-forgot">
                    <a href="reset-password.php">Password dimenticata?</a>
                </div>
                <button type="submit" name="login" class="cta-button">SIGN IN</button>
                <p class="signup-link">Non sei iscritto? <a href="registration.php">Sign Up</a></p>
            </form>
        </div>
        <div class="login-image">
            <img src="../isset/background-login.jpg" alt="background">
        </div>
    </div>
</body>
</html>
