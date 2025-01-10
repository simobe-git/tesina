<?php
session_start();

// verifica se l'utente è loggato e se è un admin
if (!isset($_SESSION['username']) || $_SESSION['ruolo'] !== 'admin') {
    // se non è un admin, reindirizza al login
    header("Location: login.php");
    exit();
}

// funzione che mostra tutti gli utenti del file xml che hanno ruolo 'richiesta_admin'
function mostraRichiesteAdmin() {
    $xml_file = '../xml/utenti.xml';
    $xml = simplexml_load_file($xml_file);
    
    $username = $xml->xpath("//utente[ruolo='richiesta_admin']/username"); // array di username
    
    // stampa un form per ogni utente con ruolo 'richiesta_admin'
    echo "<form action='admin_dashboard.php' method='post'>";
    echo "<select name='username'>";
    foreach ($username as $user) {
        echo "<option value='$user'>$user</option>";
    }
    echo "</select>";
    echo "<button type='submit' name='accetta'>Accetta</button>";
    echo "<button type='submit' name='rifiuta'>Rifiuta</button>";
    echo "</form>";

}

// se si preme il pulsante 'rifiuta', cambia il ruolo dell'utente selezionato in 'cliente'
if (isset($_POST['rifiuta'])) {
    $xml_file = '../xml/utenti.xml';
    $xml = simplexml_load_file($xml_file);
    $username = $_POST['username'];
    $utente = $xml->xpath("//utente[username='$username']")[0];
    $utente->ruolo = 'cliente';
    $xml->asXML($xml_file);
    header("Location: admin_dashboard.php");
    exit();
}
// se si preme il pulsante 'accetta', cambia il ruolo dell'utente selezionato in 'admin'
if (isset($_POST['accetta'])) {
    $xml_file = '../xml/utenti.xml';
    $xml = simplexml_load_file($xml_file);
    $username = $_POST['username'];
    $utente = $xml->xpath("//utente[username='$username']")[0];
    $utente->ruolo = 'admin';
    $xml->asXML($xml_file);
    
    // modifichiamo lo stato utente nel database
    $query_utente = "UPDATE utenti SET tipo_utente = 'admin' WHERE username = '$username'";
    $result = mysqli_query($connessione, $query_utente);
    if (!$result) {
        die("Errore nella query $query_utente: " . mysqli_error($connessione));
    }
    
    header("Location: admin_dashboard.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
</head>
<body>
    <h1>Benvenuto nella Dashboard Admin</h1>
    <p>Qui puoi gestire le richieste di crediti, le FAQ, ecc.</p>
    <h3>Richieste Admin</h3>
    <?php mostraRichiesteAdmin(); ?>
    <a href="logout.php">Logout</a>
</body>
</html>
