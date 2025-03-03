<?php
session_start();
require_once('funzioni_sconti_bonus.php');

// se utente è un admin lo reindirizziamo alla home
if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin') {
    header('Location: home.php');
    exit();
}

// Caricamento dei giochi dal file XML
$xml = simplexml_load_file('../xml/giochi.xml'); // Carica il file XML
$giochi = json_decode(json_encode($xml), true); // Converte l'XML in un array

// Controlla se l'array contiene i giochi
if (isset($giochi['gioco'])) {
    // Se l'array è presente, accedi ai giochi
    $giochi = $giochi['gioco'];
} else {
    // Se non ci sono giochi, mostra un messaggio
    echo "<p>Nessun gioco trovato nel catalogo.</p>";
    exit; // Esci dallo script
}

function calcolaBonus($codiceGioco) {
    global $connessione;
    $bonus = [];
    
    // verifica se esiste un bonus nel database
    $query = "SELECT b.*, v.titolo as nome_gioco 
              FROM bonus b 
              // JOIN gioco_tavolo v ON b.codice_gioco = v.codice 
              WHERE b.codice_gioco = ? 
              AND b.data_inizio <= CURRENT_DATE 
              AND b.data_fine >= CURRENT_DATE";
              
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("i", $codiceGioco);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $bonus[] = [
            'id' => $row['id_bonus'],
            'tipo' => 'crediti',
            'ammontare' => $row['crediti_bonus'],
            'nome_gioco' => $row['nome_gioco'],
            'data_inizio' => $row['data_inizio'],
            'data_fine' => $row['data_fine']
        ];
    }
    
    return $bonus;
}

// gestione dell'ordinamento
$ordinamento = isset($_GET['ordinamento']) ? $_GET['ordinamento'] : 'titolo'; // default ordinamento per nome
$direzione = isset($_GET['direzione']) ? $_GET['direzione'] : 'ASC'; // default crescente

// parametri di ordinamento
$ordinamenti_permessi = ['titolo', 'prezzo', 'data_rilascio'];
$direzioni_permesse = ['ASC', 'DESC'];

if (!in_array($ordinamento, $ordinamenti_permessi)) {
    $ordinamento = 'titolo';
}
if (!in_array($direzione, $direzioni_permesse)) {
    $direzione = 'ASC';
}

// gestione dei filtri
$genere = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$editore = isset($_GET['editore']) ? $_GET['editore'] : '';

// Filtraggio dei giochi
if ($genere) {
    $giochi = array_filter($giochi, function($gioco) use ($genere) {
        return $gioco['categoria'] === $genere;
    });
}
if ($editore) {
    $giochi = array_filter($giochi, function($gioco) use ($editore) {
        return $gioco['nome_editore'] === $editore;
    });
}

// Non è più necessario eseguire una query sul database per ottenere i giochi
// $query = "SELECT *, 
//           CASE 
//             WHEN prezzo_attuale IS NOT NULL AND prezzo_attuale < prezzo_originale 
//             THEN prezzo_attuale 
//             ELSE prezzo_originale 
//           END AS prezzo_effettivo 
//           FROM gioco_tavolo
//           WHERE 1=1";  // condizione sempre vera per concatenare la AND

// $query .= " ORDER BY ";

// gestione ordinamento con prezzo effettivo
// if ($ordinamento === 'prezzo') {
//     $query .= "prezzo_effettivo";
// } else {
//     $query .= $ordinamento;
// }
// $query .= " " . $direzione;

// $risultato = $connessione->query($query);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogo Giochi da Tavolo</title>
    <link rel="stylesheet" href="../css/giochi.css">
    <link rel="stylesheet" href="../css/menu.css">
    <script src="../js/filtri.js"></script>
</head>
<body>
    <?php include('menu.php'); ?>

    <header class="intestazione-negozio">
        <h1>Catalogo Giochi da Tavolo</h1>
    </header>

    <div class="filtri-sezione">
        <div class="filtri-wrapper">
            <div class="filtro-box">
                <span class="filtro-label">Ordina per:</span>
                <select class="filtro-select" id="ordinamento" onchange="applicaFiltri()">
                    <option value="titolo" <?php echo $ordinamento === 'titolo' ? 'selected' : ''; ?>>Nome</option>
                    <option value="prezzo" <?php echo $ordinamento === 'prezzo' ? 'selected' : ''; ?>>Prezzo</option>
                    <option value="data_rilascio" <?php echo $ordinamento === 'data_rilascio' ? 'selected' : ''; ?>>Anno di uscita</option>
                </select>
            </div>

            <div class="filtro-box">
                <span class="filtro-label">Ordine:</span>
                <select class="filtro-select" id="direzione" onchange="applicaFiltri()">
                    <option value="ASC" <?php echo $direzione === 'ASC' ? 'selected' : ''; ?>>Crescente ↑</option>
                    <option value="DESC" <?php echo $direzione === 'DESC' ? 'selected' : ''; ?>>Decrescente ↓</option>
                </select>
            </div>

            <div class="filtro-box">
                <span class="filtro-label">Genere:</span>
                <select class="filtro-select" id="genere" onchange="applicaFiltri()">
                    <option value="">Tutti i generi</option>
                    <?php 
                    // Genera le opzioni per i generi dal file XML
                    $generi = array_unique(array_column($giochi, 'categoria'));
                    foreach ($generi as $genereOpzione): ?>
                        <option value="<?php echo htmlspecialchars($genereOpzione); ?>"
                                <?php echo isset($_GET['categoria']) && $_GET['categoria'] === $genereOpzione ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genereOpzione); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filtro-box">
                <span class="filtro-label">Editore:</span>
                <select class="filtro-select" id="editore" onchange="applicaFiltri()">
                    <option value="">Tutti gli editori</option>
                    <?php 
                    // Genera le opzioni per gli editori dal file XML
                    $editori = array_unique(array_column($giochi, 'nome_editore'));
                    foreach ($editori as $editoreOpzione): ?>
                        <option value="<?php echo htmlspecialchars($editoreOpzione); ?>"
                                <?php echo isset($_GET['editore']) && $_GET['editore'] === $editoreOpzione ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($editoreOpzione); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- griglia dei videogiochi -->
    <div class="product-grid">
        <?php
        // Mostra i giochi filtrati
        if (empty($giochi)) {
            echo "<p>Nessun gioco trovato nel catalogo.</p>";
        } else {
            foreach ($giochi as $gioco) {
                // Assicurati che le chiavi esistano prima di accedervi
                $titolo = $gioco['titolo'] ?? 'Titolo non disponibile';
                $descrizione = $gioco['descrizione'] ?? 'Descrizione non disponibile';
                $prezzo_originale = $gioco['prezzo_originale'] ?? 'N/A';
                $prezzo_attuale = $gioco['prezzo_attuale'] ?? 'N/A';

                // Calcola sconto
                $prezzo_base = $prezzo_attuale !== 'N/A' ? $prezzo_attuale : $prezzo_originale;
                $sconto = calcolaSconto($_SESSION['username'] ?? null, $prezzo_base);
                $prezzo_finale = $sconto['prezzo_finale'] ?? $prezzo_base; // Assicurati che $sconto esista

                ?>
                <div class="product-item">
                    <a href="dettaglio_gioco.php?id=<?php echo $gioco['codice']; ?>">
                        <img src="<?php echo htmlspecialchars($gioco['immagine']); ?>" 
                             alt="<?php echo htmlspecialchars($titolo); ?>">
                    </a>
                    <h2><?php echo htmlspecialchars($titolo); ?></h2>
                    <p class="descrizione"><?php echo htmlspecialchars($descrizione); ?></p>
                    
                    <div class="prezzi">
                        <div class="prezzo-container">
                            <!-- Mostra prezzo scontato con bonus -->
                            <?php if (isset($sconto['percentuale']) && $sconto['percentuale'] > 0): ?>
                                <div class="prezzo-originale"><?php echo $prezzo_originale; ?> crediti</div>
                                <div class="prezzo-scontato">
                                    <?php echo $prezzo_finale; ?> crediti
                                    <span class="sconto-info">(-<?php echo $sconto['percentuale']; ?>%)</span>
                                </div>
                                <div class="sconto-motivo"><?php echo $sconto['motivo'] ?? ''; ?></div>
                            <?php else: ?>
                                <div class="prezzo-scontato"><?php echo $prezzo_attuale; ?> crediti</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="dettaglio_gioco.php?id=<?php echo $gioco['codice']; ?>" 
                       style="display: block; 
                              width: 90%; 
                              margin: 10px auto; 
                              padding: 10px; 
                              background-color: #007bff; 
                              color: white; 
                              text-align: center; 
                              text-decoration: none; 
                              border-radius: 5px;">
                        Acquista
                    </a>
                </div>
                <?php 
            }
        }
        ?>
    </div>

    <script>
        const menuHamburger = document.querySelector('.hamburger-menu');
        const linkNav = document.querySelector('.nav-links');

        menuHamburger.addEventListener('click', () => {
            linkNav.classList.toggle('attivo');
        });
    </script>
</body>
</html>
