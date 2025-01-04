<?php
session_start();
require_once('connessione.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// aggiungiamo la funzione formattaData
function formattaData($data) {
    return date('d/m/Y H:i', strtotime($data));
}

// funzione per ottenere i dettagli di un gioco
function getDettagliGioco($connessione, $codice_gioco) {
    $query = "SELECT nome, genere, nome_editore FROM videogiochi WHERE codice = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("i", $codice_gioco);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// all'inizio del file, dopo session_start()
error_log("Inizio caricamento storico acquisti per utente: " . $_SESSION['username']);

$xml_file = '../xml/acquisti.xml';
error_log("Percorso file XML: " . realpath($xml_file));

$acquisti = [];

if (file_exists($xml_file)) {
    error_log("File XML esiste");
    $xml = simplexml_load_file($xml_file);
    if ($xml === false) {
        error_log("Errore nel caricamento del file XML");
    } else {
        error_log("File XML caricato. Numero totale acquisti: " . count($xml->acquisto));
        foreach ($xml->acquisto as $acquisto) {
            error_log("Controllo acquisto - Username nel XML: " . (string)$acquisto->username . " vs Session: " . $_SESSION['username']);
            if ((string)$acquisto->username === $_SESSION['username']) {
                $dettagli_gioco = getDettagliGioco($connessione, (int)$acquisto->codice_gioco);
                if ($dettagli_gioco) {
                    error_log("Aggiunto acquisto per gioco: " . $dettagli_gioco['nome']);
                    $acquisti[] = [
                        'id' => (string)$acquisto['id'],
                        'gioco' => $dettagli_gioco['nome'],
                        'genere' => $dettagli_gioco['genere'],
                        'editore' => $dettagli_gioco['nome_editore'],
                        'prezzo_originale' => (float)$acquisto->prezzo_originale,
                        'prezzo_pagato' => (float)$acquisto->prezzo_pagato,
                        'sconto' => isset($acquisto->sconto_applicato) ? (float)$acquisto->sconto_applicato : 0,
                        'data' => (string)$acquisto->data
                    ];
                } else {
                    error_log("Dettagli gioco non trovati per codice: " . (int)$acquisto->codice_gioco);
                }
            }
        }
    }
} else {
    error_log("File XML non trovato: " . $xml_file);
}

error_log("Numero totale acquisti trovati per l'utente: " . count($acquisti));

// calcolo delle statistiche
$totale_speso = array_sum(array_column($acquisti, 'prezzo_pagato'));
$totale_risparmiato = array_sum(array_map(function($a) {
    return $a['prezzo_originale'] - $a['prezzo_pagato'];
}, $acquisti));
$totale_bonus = array_sum(array_column($acquisti, 'bonus'));
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storico Acquisti - GameShop</title>
    <link rel="stylesheet" href="../css/home.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            margin-top: 100px;
        }
        .statistiche {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-valore {
            font-size: 1.5em;
            color: #28a745;
            font-weight: bold;
            margin: 10px 0;
        }
        .acquisti-tabella {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .acquisti-tabella th,
        .acquisti-tabella td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .acquisti-tabella th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .acquisti-tabella tr:hover {
            background: #f8f9fa;
        }
        .sconto-badge {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.9em;
        }
        .bonus-badge {
            background: #007bff;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.9em;
        }
        .filtri {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .filtri select {
            padding: 5px;
            margin-right: 10px;
        }
        .no-acquisti {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        .container {
            margin-top: 100px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="#">GameShop</a>
        </div>
        <ul class="nav-links">
            <li><a href="catalogo.php">Catalogo</a></li>
            <li><a href="offerte.php">Offerte</a></li>
            <li><a href="faq.php">FAQ</a></li>
            <?php if(isset($_SESSION['statoLogin'])) : ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
            <?php if(isset($_SESSION['ruolo'])){ 
                if($_SESSION['ruolo'] === 'cliente'):   ?>
                <li><a href="carrello.php">Carrello</a></li>
                <li><a href="profilo.php">Profilo</a></li>
            <?php elseif ($_SESSION['ruolo'] === 'admin'):  ?>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
            <?php endif; } ?>
        </ul>
        <div class="hamburger-menu">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>

    <div class="container">
        <h1>Storico Acquisti</h1>

        <div class="statistiche">
            <div class="stat-card">
                <h3>Totale Speso</h3>
                <div class="stat-valore">€<?php echo number_format($totale_speso, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Totale Risparmiato</h3>
                <div class="stat-valore">€<?php echo number_format($totale_risparmiato, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Bonus Ottenuti</h3>
                <div class="stat-valore"><?php echo $totale_bonus; ?> crediti</div>
            </div>
            <div class="stat-card">
                <h3>Numero Acquisti</h3>
                <div class="stat-valore"><?php echo count($acquisti); ?></div>
            </div>
        </div>

        <?php if (!empty($acquisti)): ?>
            <div class="filtri">
                <label>Filtra per genere:</label>
                <select id="filtroGenere">
                    <option value="">Tutti</option>
                    <?php 
                    $generi = array_unique(array_column($acquisti, 'genere'));
                    foreach ($generi as $genere): 
                    ?>
                        <option value="<?php echo htmlspecialchars($genere); ?>">
                            <?php echo htmlspecialchars($genere); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Ordina per:</label>
                <select id="ordinamento">
                    <option value="data">Data</option>
                    <option value="prezzo">Prezzo</option>
                    <option value="nome">Nome gioco</option>
                </select>
            </div>

            <table class="acquisti-tabella">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Gioco</th>
                        <th>Genere</th>
                        <th>Editore</th>
                        <th>Prezzo Originale</th>
                        <th>Prezzo Pagato</th>
                        <th>Sconto/Bonus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($acquisti as $acquisto): ?>
                        <tr>
                            <td><?php echo formattaData($acquisto['data']); ?></td>
                            <td><?php echo htmlspecialchars($acquisto['gioco']); ?></td>
                            <td><?php echo htmlspecialchars($acquisto['genere']); ?></td>
                            <td><?php echo htmlspecialchars($acquisto['editore']); ?></td>
                            <td>€<?php echo number_format($acquisto['prezzo_originale'], 2); ?></td>
                            <td>€<?php echo number_format($acquisto['prezzo_pagato'], 2); ?></td>
                            <td>
                                <?php if ($acquisto['sconto'] > 0): ?>
                                    <span class="sconto-badge">
                                        -<?php echo $acquisto['sconto']; ?>%
                                    </span>
                                <?php endif; ?>
                                <?php if ($acquisto['bonus'] > 0): ?>
                                    <span class="bonus-badge">
                                        +<?php echo $acquisto['bonus']; ?> crediti
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-acquisti">
                <h2>Nessun acquisto effettuato</h2>
                <p>Non hai ancora effettuato acquisti. Visita il nostro catalogo per iniziare!</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const hamburgerMenu = document.querySelector('.hamburger-menu');
        const navLinks = document.querySelector('.nav-links');

        hamburgerMenu.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        // funzioni per il filtraggio e l'ordinamento
        document.getElementById('filtroGenere').addEventListener('change', function() {
            //IMPLEMENTARE IL FILTRAGGIO PER GENERE
        });

        document.getElementById('ordinamento').addEventListener('change', function() {
            //IMPLEMENTARE L'ORDINAMENTO
        });
    </script>
</body>
</html>