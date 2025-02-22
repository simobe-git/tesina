<?php
session_start();
include('connessione.php');
/*
Operazioni che un admin può fare nella dashboard:
    - Vede/modifica i dati anagrafici, username e password degli utenti.
    - (FATTA) Disattiva (banna) e riattiva utenti.
    - (FATTA) Accetta richieste di crediti.
    - Eleva una domanda (e la risposta migliore, o quella scelta dall'admin) nelle FAQ.

La funzione per poter far diventare un utente un admin è stata rimossa, in quanto sembra non essere richiesta tale funzionalità.
*/

// verifica se l'utente è loggato e se è un admin
if (!isset($_SESSION['username']) || $_SESSION['ruolo'] !== 'admin') {
    // se non è un admin, reindirizza al login
    header("Location: login.php");
    exit();
}

// funzione per vedere/modificare i dati anagrafici, username e password degli utenti.
function modificaUtente(){

    global $connessione; //per le funzioni permette di accedere alla variabile $connessione dichiarata in connessione.php

    // query per ottenere tutti gli utenti
    $query = "SELECT email,username,nome,cognome,password FROM utenti WHERE tipo_utente = 'cliente'";
    $result = $connessione->query($query);
    
    if (!$result) {
        die("Errore nella query $query: " . mysqli_error($connessione));
    
    }else{

        // Stampa una scheda per ogni cliente
        echo "<div class='user-management'>";
        while ($utente = mysqli_fetch_assoc($result)) {
            $username = htmlspecialchars($utente['username']);
            $email = htmlspecialchars($utente['email']);
            $nome = htmlspecialchars($utente['nome']);
            $cognome = htmlspecialchars($utente['cognome']);
            

            echo "<div class='user-card'>";
            echo "<p>Username: $username</p>";
            echo "<p>Email: $email</p>";
            echo "<p>Nome: $nome</p>";
            echo "<p>Cognome: $cognome</p>";
            echo "<form action='admin_dashboard.php' method='post' class='user-management-form'>";
            echo "<input type='hidden' name='username' value='$username'>";
            echo "<button type='submit' name='modifica' class='user-management-button edit'>Modifica</button>";
            echo "</form>";
            echo "</div>"; 
        }
        echo "</div>";
    
    }
}


// funzione per la gestione di tutte le richieste di acquisto di crediti
function richiesteCrediti() {
    $xml_file = '../xml/richieste_crediti.xml';
    $xml = simplexml_load_file($xml_file);
    
    $richieste = $xml->xpath("//richiesta[status='in attesa']"); // array di richieste in attesa
    
    // stampa una scheda per ogni richiesta di crediti in attesa
    echo "<div class='credit-requests'>";
    foreach ($richieste as $richiesta) {
        $username = $richiesta->username;
        $crediti = $richiesta->crediti;
        echo "<div class='credit-request-card'>";
        echo "<p>Username: $username</p>";
        echo "<p>Crediti richiesti: $crediti</p>";
        echo "<form action='admin_dashboard.php' method='post' class='credit-request-form'>";
        echo "<input type='hidden' name='username' value='$username'>";
        echo "<input type='hidden' name='crediti' value='$crediti'>";
        echo "<button type='submit' name='approva' class='credit-request-button approve'>Approva</button>";
        echo "<button type='submit' name='rifiuta' class='credit-request-button reject'>Rifiuta</button>";
        echo "</form>";
        echo "</div>";
    }
    echo "</div>";
}


// funzione per disattivare (bannare) e riattivare utenti
function gestioneUtenti() {
    $xml_file = '../xml/utenti.xml';
    $xml = simplexml_load_file($xml_file);
    
    $utenti = $xml->xpath("//utente[ruolo='cliente' or ruolo='bannato']"); //dopo aver caricato il file xml xpath permette di fare la query
                                                                           // utente[ruolo='cliente'] seleziona gli utenti che hanno come figlio il ruolo cliente
                                                                           // xpath restituisce un array di oggetti SimpleXMLElement e viene passato alla variabile utenti
    // stampa una scheda per ogni utente
    echo "<div class='user-management'>";
    foreach ($utenti as $utente) {

        $username = $utente->username;
        $ruolo = $utente->ruolo;

        echo "<div class='user-card'>";
        echo "<p>Username: $username</p>";
        echo "<p>Ruolo: $ruolo</p>";

        //stampa lo status dell'utente (attivo o bannato)
        if ($ruolo == 'bannato') {
            echo "<p>Status: Bannato</p>";

            if (isset($utente->motivo_ban)) { // stampa il motivo del ban
                echo "<p>Motivo del ban: " . $utente->motivo_ban . "</p>";
            }
        } else {
            echo "<p>Status: Attivo</p>";
        }

        echo "<form action='admin_dashboard.php' method='post' class='user-management-form'>";
        echo "<input type='hidden' name='username' value='$username'>";

        // se l'utente è bannato, mostra il pulsante per riattivarlo, altrimenti mostra il pulsante per bannarlo
        if ($ruolo == 'bannato') {
            echo "<button type='submit' name='riattiva' class='user-management-button activate'>Riattiva</button>";
        } else {
            echo "<label for='motivo'>Motivo del ban:</label>";
            echo "<input type='text' name='motivo' required>";
            echo "<button type='submit' name='banna' class='user-management-button ban'>Banna</button>";
        }

        echo "</form>";
        echo "</div>";
    }
    echo "</div>";
}


// FUNZIONAMENTO DEI PULSANTI

// Banna utente
if (isset($_POST['banna'])) {
    $xml_file = '../xml/utenti.xml';
    $xml = simplexml_load_file($xml_file);
    $username = $_POST['username'];
    $motivo = $_POST['motivo'];
    
    // Trova l'utente e aggiorna il ruolo e il motivo del ban
    $utente = $xml->xpath("//utente[username='$username']")[0]; //dopo aver caricato il file xml xpath esegue la query 
                                                                //utente[username='$username'] che seleziona l'elemento utente con figlio username uguale a $username
                                                                //la funzione xpath restituisce un array di oggetti SimpleXML che corrispondono ai nodi selezionati.
                                                                //con [0] si accede al primo elemento dell'array ovvero utente che stiamo cercando
                                                                //se si toglie [0] $utente sarà un array di oggetti dunque se si vuole accedere alla proprietà $utente->ruolo si ha un errore
                                                                //poiché $utente non è un singolo

    $utente->ruolo = 'bannato';
    if (isset($utente->motivo_ban)) {
        $utente->motivo_ban = $motivo;
    } else {
        $utente->addChild('motivo_ban', $motivo);
    }
    $xml->asXML($xml_file);
    
    header("Location: admin_dashboard.php");
    exit();
}

// Riattiva utente
if (isset($_POST['riattiva'])) {
    $xml_file = '../xml/utenti.xml';
    $xml = simplexml_load_file($xml_file);
    $username = $_POST['username'];
    
    // Trova l'utente e aggiorna il ruolo
    $utente = $xml->xpath("//utente[username='$username']")[0];
    $utente->ruolo = 'cliente';
    unset($utente->motivo_ban); // Rimuovi il motivo del ban quando l'utente viene riattivato
    $xml->asXML($xml_file);
    
    header("Location: admin_dashboard.php");
    exit();
}

// Approvazione richiesta di crediti 
if (isset($_POST['approva'])) {
    $xml_file = '../xml/richieste_crediti.xml';
    $xml = simplexml_load_file($xml_file);
    $username = $_POST['username'];
    $crediti = $_POST['crediti'];
    
    // Trova la richiesta e aggiorna lo status
    $richiesta = $xml->xpath("//richiesta[username='$username' and crediti='$crediti' and status='in attesa']")[0];
    $richiesta->status = 'approvata';
    $xml->asXML($xml_file);
    
    // Aggiorna il numero di crediti dell'utente nel database
    $query_crediti = "UPDATE utenti SET crediti = crediti + $crediti WHERE username = '$username'";
    $result = mysqli_query($connessione, $query_crediti);
    if (!$result) {
        die("Errore nella query $query_crediti: " . mysqli_error($connessione));
    }
    
    header("Location: admin_dashboard.php");
    exit();
}

// Rifiuto richiesta di crediti
if (isset($_POST['rifiuta'])) {
    $xml_file = '../xml/richieste_crediti.xml';
    $xml = simplexml_load_file($xml_file);
    $username = $_POST['username'];
    $crediti = $_POST['crediti'];
    
    // Trova la richiesta e aggiorna lo status
    $richiesta = $xml->xpath("//richiesta[username='$username' and crediti='$crediti' and status='in attesa']")[0];
    $richiesta->status = 'rifiutata';
    $xml->asXML($xml_file);
    
    header("Location: admin_dashboard.php");
    exit();
}


// Modifica informazioni degli utenti
if(isset($_POST['modifica'])) {
    $username = $_POST['username'];

    // Query per ottenere i dati dell'utente dal database
    $query = "SELECT username, email, nome, cognome FROM utenti WHERE username = '$username'";
    $result = $connessione->query($query);

    if ($result && $result->num_rows === 1) {
        $utente = mysqli_fetch_assoc($result);
        $editUserData = $utente; //array dati dell'utente da modificare
        $showEditForm = true; //visualizza il form html per modificare i dati dell'utente
    } else {
        echo "<p>Errore: utente non trovato.</p>";
    }
}

// Salva le modifiche
if (isset($_POST['salva'])) {

    $username = $_POST['username'];
    $email = $_POST['email'];
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];

    // Query per aggiornare i dati dell'utente nel database
    $query = "UPDATE utenti SET email = '$email', nome = '$nome', cognome = '$cognome' WHERE username = '$username'";
    $result = $connessione->query($query);

    if ($result) {
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "<p>Errore nella query $query: " . mysqli_error($connessione) . "</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<!--Titolo con pulsante Logout -->
    <header class="header">
        <h1>Dashboard Admin</h1>
        <nav>
            <ul>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </nav>
    </header>

<!-- Funzionalità gestite -->
    <main class="dashboard-container">
        <div class="row">
            <!-- Richiesta degli utenti per diventare Admin-->
            <section class="users-management">
                <h2>Gestione Utenti</h2>
                <?php modificaUtente(); ?>
            </section>

            <!-- Creazione di una FAQ-->
            <section class="faq-management">
                <h2>Gestione FAQ</h2>
            </section>
        </div>
        <div class="row">
            <!-- Richieste acquisto numero personalizzati di crediti -->
            <section class="credits-management">
                <h2>Gestione Crediti</h2>
                <?php richiesteCrediti(); ?>
            </section>

            <!-- Ban o riattivazione account utente -->
            <section class="ban-management">
                <h2>Gestione Ban Utenti</h2>
                <?php gestioneUtenti(); ?>
            </section>
        </div>

        <!-- Modulo di modifica utente -->
        <?php if ($showEditForm): ?>
        <div class="user-edit-form">
            <form action="admin_dashboard.php" method="post">
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($editUserData['username']); ?>">
                <label for="email">Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($editUserData['email']); ?>" required>
                <label for="nome">Nome:</label>
                <input type="text" name="nome" value="<?php echo htmlspecialchars($editUserData['nome']); ?>" required>
                <label for="cognome">Cognome:</label>
                <input type="text" name="cognome" value="<?php echo htmlspecialchars($editUserData['cognome']); ?>" required>
                <button type="submit" name="salva" class="user-management-button save">Salva</button>
            </form>
        </div>
        <?php endif; ?>
    </main>
</body>
<script>
        function openEditForm(username, email, nome, cognome) {
            document.getElementById('edit-username').value = username;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-nome').value = nome;
            document.getElementById('edit-cognome').value = cognome;
            document.getElementById('popup-overlay').style.display = 'flex';
        }

        function closeEditForm() {
            document.getElementById('popup-overlay').style.display = 'none';
        }
    </script>
</html>
