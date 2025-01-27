<?php
session_start();
require_once('connessione.php');
require_once('funzioni_sconti_bonus.php');

// se utente è un admin lo reindirizziamo alla home
if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin') {
    header('Location: home.php');
    exit();
}

$id_gioco = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// recupera i dettagli del gioco
$query = "SELECT * FROM videogiochi WHERE codice = ?";
$stmt = $connessione->prepare($query);
$stmt->bind_param("i", $id_gioco);
$stmt->execute();
$gioco = $stmt->get_result()->fetch_assoc();

if (!$gioco) {
    header('Location: catalogo.php');
    exit();
}

// recupera le recensioni del gioco
$query = "SELECT r.*, u.username, 
          (SELECT AVG(supporto) FROM giudizi_recensioni WHERE id_recensione = r.id_recensione) as media_supporto,
          (SELECT AVG(utilita) FROM giudizi_recensioni WHERE id_recensione = r.id_recensione) as media_utilita
          FROM recensioni r 
          JOIN utenti u ON r.username = u.username 
          WHERE r.codice_gioco = ?
          ORDER BY r.data_recensione DESC";
$stmt = $connessione->prepare($query);
$stmt->bind_param("i", $id_gioco);
$stmt->execute();
$recensioni = $stmt->get_result();

// carichiamo le discussioni del forum per questo gioco
function caricaDiscussioni($id_gioco) {
    $xmlFile = '../xml/domande.xml';
    if (file_exists($xmlFile)) {
        $domande = simplexml_load_file($xmlFile);
        $discussioni = [];
        foreach ($domande->domanda as $domanda) {
            if ((string)$domanda->codiceGioco === (string)$id_gioco) {
                $discussioni[] = $domanda;
            }
        }
        return $discussioni;
    }
    return [];
}

$discussioni = caricaDiscussioni($id_gioco);

// calcola sconto e bonus
$prezzo_base = $gioco['prezzo_attuale'];
$sconto = calcolaSconto($_SESSION['username'] ?? null, $prezzo_base);
$bonus = getBonusDisponibili($id_gioco);

// DEBUG
if (empty($bonus)) {
    error_log("Nessun bonus trovato per il gioco ID: " . $id_gioco);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($gioco['nome']); ?></title>
    <link rel="stylesheet" href="../css/dettaglio.css">
    <link rel="stylesheet" href="../css/menu.css">
    <style>
        
        .btn-primary {
            background: linear-gradient(45deg, #2196F3, #1976D2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #1976D2, #1565C0);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            transform: translateY(-2px);
        }

        /* bottone del carrello */
        .btn-carrello {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            max-width: 250px;
            margin: 20px 0;
        }

        .btn-carrello:before {
            content: '🛒';
            font-size: 1.2rem;
        }

        .btn-carrello:hover {
            background: linear-gradient(45deg, #45a049, #388E3C);
        }

        /* bottoni delle recensioni e discussioni */
        .form-recensione button,
        .form-discussione button {
            background: linear-gradient(45deg, #FF5722, #F4511E);
            padding: 10px 20px;
            margin-top: 10px;
            width: auto;
            min-width: 150px;
        }

        .form-recensione button:hover,
        .form-discussione button:hover {
            background: linear-gradient(45deg, #F4511E, #E64A19);
        }

        /* stili per le textarea */
        .form-recensione textarea,
        .form-discussione textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            resize: vertical;
            min-height: 100px;
        }

        .form-recensione textarea:focus,
        .form-discussione textarea:focus {
            border-color: #2196F3;
            outline: none;
            box-shadow: 0 0 5px rgba(33, 150, 243, 0.3);
        }

        /* bottone di login */
        .btn-login {
            background: linear-gradient(45deg, #9C27B0, #7B1FA2);
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-login:hover {
            background: linear-gradient(45deg, #7B1FA2, #6A1B9A);
        }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="dettaglio-container">
        <div class="dettaglio-gioco">
            <h1 class="titolo-gioco"><?php echo htmlspecialchars($gioco['nome']); ?></h1>
            
            <div class="gioco-content">
                <img class="gioco-immagine" src="<?php echo htmlspecialchars($gioco['immagine']); ?>" 
                     alt="<?php echo htmlspecialchars($gioco['nome']); ?>">
                
                <div class="gioco-info">
                    <p class="descrizione"><?php echo htmlspecialchars($gioco['descrizione']); ?></p>
                    <div class="dettagli">
                        <p><strong>Genere:</strong> <?php echo htmlspecialchars($gioco['genere']); ?></p>
                        <p><strong>Editore:</strong> <?php echo htmlspecialchars($gioco['nome_editore']); ?></p>
                        <p><strong>Data di rilascio:</strong> <?php echo htmlspecialchars($gioco['data_rilascio']); ?></p>
                    </div>
                    
                    <div class="prezzi-acquisto">
                        <?php if ($sconto['percentuale'] > 0): ?>
                            <div class="prezzo-originale"><?php echo $prezzo_base; ?> crediti</div>
                            <div class="prezzo-scontato">
                                <?php echo $sconto['prezzo_finale']; ?> crediti
                                <span class="sconto-info">(-<?php echo $sconto['percentuale']; ?>%)</span>
                            </div>
                            <div class="sconto-motivo"><?php echo $sconto['motivo']; ?></div>
                        <?php else: ?>
                            <div class="prezzo"><?php echo $prezzo_base; ?> crediti</div>
                        <?php endif; ?>
                        
                        <?php if (!empty($bonus)): ?>
                            <div class="bonus-info">
                                <p class="bonus-titolo">Bonus all'acquisto:</p>
                                <?php foreach ($bonus as $b): ?>
                                    <div class="bonus-badge">
                                        <span class="bonus-ammontare">+<?php echo htmlspecialchars($b['ammontare']); ?>€ in crediti</span>
                                        <span class="bonus-date">
                                            (Valido dal <?php echo date('d/m/Y', strtotime($b['data_inizio'])); ?> 
                                            al <?php echo date('d/m/Y', strtotime($b['data_fine'])); ?>)
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['username'])): ?>
                            <form method="POST" action="carrello.php">
                                <input type="hidden" name="codice_gioco" value="<?php echo $id_gioco; ?>">
                                <button type="submit" name="aggiungi" class="btn-primary btn-carrello">Aggiungi al Carrello</button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="btn-primary btn-login">Accedi per acquistare</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- sezione recensioni -->
            <div class="recensioni-section">
                <h2>Recensioni</h2>
                <?php if (isset($_SESSION['username'])): ?>
                    <form method="POST" action="aggiungi_recensione.php" class="form-recensione">
                        <input type="hidden" name="codice_gioco" value="<?php echo $id_gioco; ?>">
                        <textarea name="testo" required placeholder="Scrivi la tua recensione..."></textarea>
                        <button type="submit" class="btn-primary">Pubblica recensione</button>
                    </form>
                <?php endif; ?>

                <div class="recensioni-container">
                    <?php 
                    $count = 0;
                    while ($recensione = $recensioni->fetch_assoc()): 
                        if ($count < 3): // inizialemnente mostriamo solo le prime 3 recensioni
                    ?>
                        <div class="recensione">
                            <div class="recensione-header">
                                <span class="recensione-autore"><?php echo htmlspecialchars($recensione['username']); ?></span>
                                <span class="recensione-data"><?php echo date('d/m/Y', strtotime($recensione['data_recensione'])); ?></span>
                            </div>
                            <p class="recensione-testo"><?php echo htmlspecialchars($recensione['testo']); ?></p>
                            <div class="recensione-valutazioni">
                                <span>Supporto: <?php echo number_format($recensione['media_supporto'], 1); ?>/3</span>
                                <span>Utilità: <?php echo number_format($recensione['media_utilita'], 1); ?>/5</span>
                            </div>
                        </div>
                    <?php 
                        endif;
                        $count++;
                    endwhile; 
                    
                    if ($count > 3): // mostriamo il pulsante di ampliamento solo se ci sono più di 3 recensioni
                    ?>
                        <button class="btn-mostra-altro" onclick="mostraAltreRecensioni()">Mostra altre recensioni</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- sezione forum -->
            <div class="forum-section">
                <h2>Discussioni</h2>
                <?php if (empty($discussioni) && isset($_SESSION['username'])): ?>
                    <form method="POST" action="aggiungi_discussione.php" class="form-discussione">
                        <input type="hidden" name="codice_gioco" value="<?php echo $id_gioco; ?>">
                        <textarea name="testo" required placeholder="Fai una domanda..."></textarea>
                        <button type="submit" class="btn-primary">Apri discussione</button>
                    </form>
                <?php elseif (!empty($discussioni)): ?>
                    <div class="discussioni-container">
                        <?php foreach ($discussioni as $discussione): ?>
                            <div class="discussione">
                                <div class="discussione-header">
                                    <span class="discussione-autore"><?php echo $discussione->username; ?></span>
                                    <span class="discussione-data"><?php echo date('d/m/Y', strtotime($discussione->data)); ?></span>
                                </div>
                                <p class="discussione-testo"><?php echo $discussione->testo; ?></p>
                                <?php if (isset($_SESSION['username'])): ?>
                                    <button class="btn-rispondi" onclick="mostraFormRisposta(this)">Rispondi</button>
                                    <form method="POST" action="aggiungi_risposta.php" class="form-risposta" style="display: none;">
                                        <input type="hidden" name="id_discussione" value="<?php echo $discussione->id; ?>">
                                        <textarea name="testo" required placeholder="Scrivi la tua risposta..."></textarea>
                                        <button type="submit">Invia risposta</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function mostraAltreRecensioni() {
            const recensioniContainer = document.querySelector('.recensioni-container');
            const recensioniNascoste = document.querySelectorAll('.recensione.nascosta');
            const btnMostraAltro = document.querySelector('.btn-mostra-altro');
            
            // mostra le prossime 3 recensioni
            let count = 0;
            recensioniNascoste.forEach(recensione => {
                if (count < 3) {
                    recensione.classList.remove('nascosta');
                    count++;
                }
            });
            
            // nascondi il pulsante se non ci sono più recensioni da mostrare
            if (document.querySelectorAll('.recensione.nascosta').length === 0) {
                btnMostraAltro.style.display = 'none';
            }
        }

        function mostraFormRisposta(button) {
            const form = button.nextElementSibling;
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>