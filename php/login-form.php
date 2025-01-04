<?php
session_start();
require_once("connessione.php");

function carica_utenti() {
    return simplexml_load_file('../xml/utenti.xml');
}

if(isset($_POST['login']) && $_SERVER["REQUEST_METHOD"] === "POST"){ 
    $email = mysqli_real_escape_string($connessione, $_POST['email']);     // previene SQL injection (problemi)
    $password = mysqli_real_escape_string($connessione, $_POST['password']);

    $query_utente = "SELECT username FROM utenti WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($connessione, $query_utente);

    if(mysqli_num_rows($result) === 1){
        $row = mysqli_fetch_assoc($result);
        $username = $row['username'];
        $_SESSION['username'] = $username;

        // carichiamo il ruolo dal file XML
        $utenti = carica_utenti();
        $ruolo_trovato = false;

        foreach ($utenti->utente as $utente) {
            if ((string)$utente->username === $username) {
                $_SESSION['ruolo'] = (string)$utente->ruolo;
                $ruolo_trovato = true;
                break;
            }
        }

        if (!$ruolo_trovato) {
            $_SESSION['ruolo'] = 'cliente'; // è il ruolo predefinito se non trovato nel file XML
        }

        $_SESSION['statoLogin'] = true;

        // controlla se c'è un URL di reindirizzamento salvato
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);     // se c'è rimuove l'URL salvato
            header("Location: " . $redirect);
        } else {
            // reindirizzamento in base al ruolo
            if ($_SESSION['ruolo'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: home.php");
            }
        }
        exit();
    } else {
        // in caso di credenziali errate
        header("Location: login.php?error=1");
        exit();
    }
} else {
    // accesso non autorizzato: andiamo alla login
    header("Location: login.php");
    exit();
}
?>