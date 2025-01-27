<?php
session_start();
require_once('connessione.php');
require_once('funzioni_sconti_bonus.php');

// se utente è un admin lo reindirizziamo alla home
if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin') {
    header('Location: home.php');
    exit();
}

function calcolaBonus($codiceGioco) {
    global $connessione;
    $bonus = [];
    
    // verifica se esiste un bonus nel database
    $query = "SELECT b.*, v.nome as nome_gioco 
              FROM bonus b 
              JOIN videogiochi v ON b.codice_gioco = v.codice 
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
$ordinamento = isset($_GET['ordinamento']) ? $_GET['ordinamento'] : 'nome'; // default ordinamento per nome
$direzione = isset($_GET['direzione']) ? $_GET['direzione'] : 'ASC'; // default crescente

// parametri di ordinamento
$ordinamenti_permessi = ['nome', 'prezzo', 'data_rilascio'];
$direzioni_permesse = ['ASC', 'DESC'];

if (!in_array($ordinamento, $ordinamenti_permessi)) {
    $ordinamento = 'nome';
}
if (!in_array($direzione, $direzioni_permesse)) {
    $direzione = 'ASC';
}

// query per ottenere generi ed editori (univoci)
$query_generi = "SELECT DISTINCT genere FROM videogiochi WHERE genere IS NOT NULL ORDER BY genere";
$risultato_generi = $connessione->query($query_generi);

$query_editori = "SELECT DISTINCT nome_editore FROM videogiochi WHERE nome_editore IS NOT NULL ORDER BY nome_editore";
$risultato_editori = $connessione->query($query_editori);

// gestione dei filtri
$genere = isset($_GET['genere']) ? $_GET['genere'] : '';
$editore = isset($_GET['editore']) ? $_GET['editore'] : '';

// query con i filtri
$query = "SELECT *, 
          CASE 
            WHEN prezzo_attuale IS NOT NULL AND prezzo_attuale < prezzo_originale 
            THEN prezzo_attuale 
            ELSE prezzo_originale 
          END AS prezzo_effettivo 
          FROM videogiochi 
          WHERE 1=1";  // condizione sempre vera per concatenare la AND

if ($genere) {
    $query .= " AND genere = '" . $connessione->real_escape_string($genere) . "'";
}
if ($editore) {
    $query .= " AND nome_editore = '" . $connessione->real_escape_string($editore) . "'";
}

$query .= " ORDER BY ";

// gestione ordinamento con prezzo effettivo
if ($ordinamento === 'prezzo') {
    $query .= "prezzo_effettivo";
} else {
    $query .= $ordinamento;
}
$query .= " " . $direzione;

$risultato = $connessione->query($query);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogo Videogiochi</title>
    <link rel="stylesheet" href="../css/giochi.css">
    <link rel="stylesheet" href="../css/menu.css">
    <script src="../js/filtri.js"></script>
</head>
<body>
    <?php include('menu.php'); ?>

    <header class="intestazione-negozio">
        <h1>Catalogo Videogiochi</h1>
    </header>

    <div class="filtri-sezione">
        <div class="filtri-wrapper">
            <div class="filtro-box">
                <span class="filtro-label">Ordina per:</span>
                <select class="filtro-select" id="ordinamento" onchange="applicaFiltri()">
                    <option value="nome" <?php echo $ordinamento === 'nome' ? 'selected' : ''; ?>>Nome</option>
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
                    <?php while($genere = $risultato_generi->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($genere['genere']); ?>"
                                <?php echo isset($_GET['genere']) && $_GET['genere'] === $genere['genere'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genere['genere']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filtro-box">
                <span class="filtro-label">Editore:</span>
                <select class="filtro-select" id="editore" onchange="applicaFiltri()">
                    <option value="">Tutti gli editori</option>
                    <?php while($editore = $risultato_editori->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($editore['nome_editore']); ?>"
                                <?php echo isset($_GET['editore']) && $_GET['editore'] === $editore['nome_editore'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($editore['nome_editore']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </div>

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
                        <?php 
                        $prezzo_base = $gioco['prezzo_attuale'] ?? $gioco['prezzo_originale'];
                        $sconto = calcolaSconto($_SESSION['username'] ?? null, $prezzo_base);
                        $prezzo_finale = $sconto['prezzo_finale'];
                        ?>
                        <div class="prezzo-container">
                            <?php if ($sconto['percentuale'] > 0): ?>
                                <div class="prezzo-originale"><?php echo $prezzo_base; ?> crediti</div>
                                <div class="prezzo-scontato">
                                    <?php echo $sconto['prezzo_finale']; ?> crediti
                                    <span class="sconto-info">(-<?php echo $sconto['percentuale']; ?>%)</span>
                                </div>
                                <div class="sconto-motivo"><?php echo $sconto['motivo']; ?></div>
                            <?php else: ?>
                                <div class="prezzo"><?php echo $prezzo_base; ?> crediti</div>
                                <?php if (isset($sconto['motivo'])): ?>
                                    <div class="no-sconto-info"><?php echo $sconto['motivo']; ?></div>
                                <?php endif; ?>
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
            <?php }
        } else {
            echo "<p>Nessun gioco trovato nel catalogo.</p>";
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
