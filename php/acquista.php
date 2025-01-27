<?php
session_start();
require_once('connessione.php');
require_once('funzioni_sconti_bonus.php');

// se utente è un admin lo reindirizziamo alla home
if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin') {
    header('Location: home.php');
    exit();
}

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// funzione per aggiornare i crediti dell'utente
function aggiornaCrediti($connessione, $username, $importo, $operazione = 'sottrai') {
    $query = "UPDATE utenti SET crediti = crediti " . 
             ($operazione === 'sottrai' ? '-' : '+') . 
             " ? WHERE username = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("ds", $importo, $username);
    return $stmt->execute();
}

// funzione per registrare l'acquisto in un file xml
function registraAcquisto($username, $codice_gioco, $prezzo_originale, $prezzo_pagato, $sconto, $bonus) {
    $xml_file = 'xml/acquisti.xml';
    
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
    } else {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><acquisti></acquisti>');
    }
    
    $acquisto = $xml->addChild('acquisto');
    $acquisto->addAttribute('id', time());
    $acquisto->addChild('username', $username);
    $acquisto->addChild('codice_gioco', $codice_gioco);
    $acquisto->addChild('prezzo_originale', $prezzo_originale);
    $acquisto->addChild('prezzo_pagato', $prezzo_pagato);
    $acquisto->addChild('sconto_applicato', $sconto);
    $acquisto->addChild('bonus_ottenuti', $bonus);
    $acquisto->addChild('data', date('Y-m-d'));
    
    $xml->asXML($xml_file);
}

// gestione dell'acquisto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['conferma_acquisto'])) {
    $codice_gioco = $_POST['codice_gioco'];
    
    // procediamo col recuperare informazioni sul gioco
    $query = "SELECT * FROM videogiochi WHERE codice = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("i", $codice_gioco);
    $stmt->execute();
    $gioco = $stmt->get_result()->fetch_assoc();
    
    if ($gioco) {
        // calcolo dello sconto applicabile
        $sconto = calcolaSconto($_SESSION['username'], $codice_gioco, $gioco['prezzo_originale']);
        $prezzo_finale = $sconto['prezzo_finale'];
        
        // e verifica dei crediti disponibili
        $query = "SELECT crediti FROM utenti WHERE username = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $crediti_disponibili = $stmt->get_result()->fetch_assoc()['crediti'];
        
        if ($crediti_disponibili >= $prezzo_finale) {
            // sottrai crediti per l'acquisto
            if (aggiornaCrediti($connessione, $_SESSION['username'], $prezzo_finale, 'sottrai')) {
                // applicazione del bonus se disponibile
                $bonus = getBonusDisponibili($codice_gioco);
                $bonus_crediti = !empty($bonus) ? $bonus[0]['ammontare'] : 0;
                
                if ($bonus_crediti > 0) {
                    aggiornaCrediti($connessione, $_SESSION['username'], $bonus_crediti, 'aggiungi');
                }
                
                // andiamo a registrare l'acquisto
                registraAcquisto(
                    $_SESSION['username'],
                    $codice_gioco,
                    $gioco['prezzo_originale'],
                    $prezzo_finale,
                    $sconto['percentuale'],
                    $bonus_crediti
                );
                
                $messaggio = "Acquisto completato con successo!";
                if ($bonus_crediti > 0) {
                    $messaggio .= " Hai ricevuto $bonus_crediti crediti bonus!";
                }
            } else {
                $errore = "Errore durante l'acquisto";
            }
        } else {
            $errore = "Crediti insufficienti per l'acquisto";
        }
    }
}

// recuperiamo dal db il gioco da acquistare
$codice_gioco = isset($_GET['codice']) ? $_GET['codice'] : null;
if ($codice_gioco) {
    $query = "SELECT * FROM videogiochi WHERE codice = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("i", $codice_gioco);
    $stmt->execute();
    $gioco = $stmt->get_result()->fetch_assoc();
    
    if ($gioco) {
        $sconto = calcolaSconto($_SESSION['username'], $codice_gioco, $gioco['prezzo_originale']);
        $bonus = getBonusDisponibili($codice_gioco);
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Conferma Acquisto</title>
    <style>
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .riepilogo { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .prezzo-originale { text-decoration: line-through; color: #6c757d; }
        .prezzo-finale { color: #28a745; font-size: 1.2em; font-weight: bold; }
        .bonus { color: #007bff; margin-top: 10px; }
        .btn { padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; }
        .messaggio { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .successo { background: #d4edda; color: #155724; }
        .errore { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($messaggio)): ?>
            <div class="messaggio successo"><?php echo $messaggio; ?></div>
        <?php endif; ?>
        
        <?php if (isset($errore)): ?>
            <div class="messaggio errore"><?php echo $errore; ?></div>
        <?php endif; ?>

        <?php if ($gioco): ?>
            <h1>Conferma Acquisto</h1>
            
            <div class="riepilogo">
                <h2><?php echo htmlspecialchars($gioco['nome']); ?></h2>
                
                <?php if ($sconto['percentuale'] > 0): ?>
                    <div class="prezzo-originale">
                        Prezzo originale: €<?php echo number_format($gioco['prezzo_originale'], 2); ?>
                    </div>
                    <div class="prezzo-finale">
                        Prezzo finale: €<?php echo number_format($sconto['prezzo_finale'], 2); ?>
                        (Sconto del <?php echo $sconto['percentuale']; ?>%)
                    </div>
                <?php else: ?>
                    <div class="prezzo-finale">
                        Prezzo: €<?php echo number_format($gioco['prezzo_originale'], 2); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($bonus)): ?>
                    <div class="bonus">
                        Riceverai <?php echo $bonus[0]['ammontare']; ?> crediti bonus con questo acquisto!
                    </div>
                <?php endif; ?>

                <form method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="codice_gioco" value="<?php echo $codice_gioco; ?>">
                    <button type="submit" name="conferma_acquisto" class="btn">
                        Conferma Acquisto
                    </button>
                </form>
            </div>
        <?php else: ?>
            <p>Gioco non trovato.</p>
        <?php endif; ?>
    </div>
</body>
</html>