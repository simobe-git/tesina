<?php

session_start();
// non viene eseguito il controllo sullo stato login poiché un utente 
// può accedere al catalogo in modo anonimo ma per effettuare acquisti 
// dovrà necessariamente identificarsi

require_once("connessione.php");

$sql = "SELECT * FROM videogiochi";
$result = mysqli_query($connessione,$sql);

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

    <div class="product-grid">
        <?php
            if(mysqli_num_rows($result) > 0){

                while($row = mysqli_fetch_assoc($result)){
                    
                    // stampiamo solo giochi che hanno uno sconto
                    if($row['prezzo_attuale'] != $row['prezzo_originale']){

                        echo '<div class="product-item">';
                        
                        // mostriamo l'immagine richiamando il link nel database
                        echo '<img src="' . $row['immagine'] . '" alt="' . $row['nome'] . '">'; 
                        echo '<h3>' . $row['nome'] . '</h3>';
                        echo '<p class="price">';
                        echo '<span class="current-price">€ ' . $row['prezzo_attuale'] . '</span>';
                        echo ' <span class="original-price">€ ' . $row['prezzo_originale'] . '</span>';
                        echo '</p>';
                        echo '<form method="POST" action="carrello.php">
                            <input type="hidden" name="codice_gioco" value="' . $row['codice'] . '">
                            <button type="submit" name="aggiungi" class="btn-acquista">Aggiungi al Carrello</button>
                        </form>';
                        echo '</div>';
                    }
                }
            }else{
                echo '<p>Nessun prodotto trovato</p>';
            }
        ?>
    </div>
    
</body>
</html>
