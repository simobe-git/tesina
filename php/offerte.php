<?php

session_start();
// non viene eseguito il controllo sullo stato login poiché un utente 
// può accedere al catalogo in modo anonimo ma per effettuare acquisti 
// dovrà necessariamente identificarsi

require_once("connessione.php");

// se utente è un admin lo reindirizziamo alla home
if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin') {
    header('Location: home.php');
    exit();
}

$query_offerte = "SELECT * FROM videogiochi WHERE prezzo_attuale <> prezzo_originale";
$risultato = mysqli_query($connessione,$query_offerte);

// gestione dell'aggiunta al carrello
if(isset($_POST['aggiungi_al_carrello']) && isset($_POST['codice_gioco'])) {
    if(!isset($_SESSION['username'])) {

        // se l'utente non è loggato, reindirizza al login
        header('Location: login.php');
        exit();
    }
    
    $codice_gioco = $_POST['codice_gioco'];
    $username = $_SESSION['username'];
    
    // query per inserire il gioco nel carrello
    $query = "INSERT IGNORE INTO carrello (username, codice_gioco) VALUES (?, ?)";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("si", $username, $codice_gioco);
    $stmt->execute();
    
    // reindirizza al carrello dopo l'aggiunta
    header('Location: carrello.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutti gli Articoli del Negozio</title>
    <link rel="stylesheet" href="../css/giochi.css">
    <link rel="stylesheet" href="../css/menu.css">
</head>
<body>
    <?php include('menu.php'); ?>

    <!-- titolo della pagina -->
    <header class="shop-header">
        <h1>Tutti gli Articoli</h1>
    </header>

    <!-- griglia dei videogiochi -->
    <div class="product-grid">
        <?php
        if ($risultato->num_rows > 0) {
            while ($gioco = $risultato->fetch_assoc()) {
                ?>
                <div class="product-item">
                    <a href="dettaglio_gioco.php?id=<?php echo $gioco['codice']; ?>">
                        <img src="<?php echo htmlspecialchars($gioco['immagine']); ?>" 
                             alt="<?php echo htmlspecialchars($gioco['nome']); ?>">
                    </a>
                    <h2><?php echo htmlspecialchars($gioco['nome']); ?></h2>
                    <p class="descrizione"><?php echo htmlspecialchars($gioco['descrizione']); ?></p>
                    
                    <div class="prezzi">
                        <div class="prezzo-container">
                            <div class="prezzo-originale"><?php echo $gioco['prezzo_originale']; ?> crediti</div>
                                <div class="prezzo-scontato">
                                    <?php echo $gioco['prezzo_attuale']; ?> crediti
                                </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="carrello.php">
                        <input type="hidden" name="codice_gioco" value="<?php echo $gioco['codice']; ?>">
                        <button type="submit" name="aggiungi" class="btn-acquista">Aggiungi al Carrello</button>
                    </form>
                </div>
            <?php }
        } else {
            echo "<p>Nessun gioco trovato nel catalogo.</p>";
        }
        ?>
    </div>
    
</body>
</html>
