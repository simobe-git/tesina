<?php
require_once('dati-connessione.php');
// creiamo una connessione senza specificare il database
$connessione = new mysqli($hostname, $user, $password);

// verifica della connessione
if ($connessione->connect_error) {
    die("Connessione fallita: " . $connessione->connect_error);
}

// e successiva creazione del database
$sql = "CREATE DATABASE IF NOT EXISTS $db";
if ($connessione->query($sql) === TRUE) {
    echo "Database creato con successo o già esistente<br>";
} else {
    echo "Errore nella creazione del database: " . $connessione->error . "<br>";
}

// selezione del database
$connessione->select_db($db);

// disabilitiamo temporaneamente i controlli delle chiavi esterne (debug)
$connessione->query('SET FOREIGN_KEY_CHECKS = 0');

// eliminiamo le tabelle nell'ordine corretto
$tabelle = [
    'giudizi_recensioni',
    'recensioni',
    'bonus',
    'videogiochi',
    'utenti',
    'faq',
    'carrello'
];

foreach ($tabelle as $tabella) {
    $sql = "DROP TABLE IF EXISTS $tabella";
    if ($connessione->query($sql) === TRUE) {
        echo "Tabella $tabella eliminata se esistente<br>";
    } else {
        echo "Errore nell'eliminazione della tabella $tabella: " . $connessione->error . "<br>";
    }
}

// riabilita i controlli delle chiavi esterne
$connessione->query('SET FOREIGN_KEY_CHECKS = 1');

// creazione della tabella utenti
$sql = "CREATE TABLE utenti (
    nome VARCHAR(30) NOT NULL,
    cognome VARCHAR(30) NOT NULL,
    username VARCHAR(30) NOT NULL PRIMARY KEY,
    email VARCHAR(50) NOT NULL,
    password VARCHAR(30) NOT NULL,
    tipo_utente ENUM('visitatore', 'cliente', 'gestore', 'admin') DEFAULT 'cliente',
    crediti DECIMAL(10,2) DEFAULT 0.00,
    data_registrazione DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if ($connessione->query($sql) === TRUE) {
    echo "Tabella utenti creata con successo<br>";
} else {
    echo "Errore nella creazione della tabella utenti: " . $connessione->error . "<br>";
}

// creazione della tabella videogiochi (se non esiste già)
$sql = "CREATE TABLE IF NOT EXISTS videogiochi (
    codice INT(5) NOT NULL PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    prezzo_originale DOUBLE(6,2) NOT NULL,
    prezzo_attuale DOUBLE(6,2) NOT NULL,
    genere VARCHAR(30) NOT NULL,
    data_rilascio DATE NOT NULL,
    nome_editore VARCHAR(30) NOT NULL,
    descrizione TEXT,
    immagine VARCHAR(255)
)";

if ($connessione->query($sql) === TRUE) {
    echo "Tabella videogiochi creata con successo o già esistente<br>";
} else {
    echo "Errore nella creazione della tabella videogiochi: " . $connessione->error . "<br>";
}

// creazione tabella recensioni
$sql = "CREATE TABLE IF NOT EXISTS recensioni (
    id_recensione INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30),
    codice_gioco INT(5),
    testo TEXT,
    data_recensione DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (username) REFERENCES utenti(username) ON DELETE CASCADE,
    FOREIGN KEY (codice_gioco) REFERENCES videogiochi(codice) ON DELETE CASCADE
)";

if ($connessione->query($sql) === TRUE) {
    echo "Tabella recensioni creata con successo o già esistente<br>";
} else {
    echo "Errore nella creazione della tabella recensioni: " . $connessione->error . "<br>";
}

// creazione tabella giudizi_recensioni
$sql = "CREATE TABLE IF NOT EXISTS giudizi_recensioni (
    id_giudizio INT AUTO_INCREMENT PRIMARY KEY,
    id_recensione INT,
    username_votante VARCHAR(30),
    supporto INT(1) CHECK (supporto BETWEEN 1 AND 3),
    utilita INT(1) CHECK (utilita BETWEEN 1 AND 5),
    FOREIGN KEY (id_recensione) REFERENCES recensioni(id_recensione) ON DELETE CASCADE,
    FOREIGN KEY (username_votante) REFERENCES utenti(username) ON DELETE CASCADE
)";

if ($connessione->query($sql) === TRUE) {
    echo "Tabella giudizi_recensioni creata con successo o già esistente<br>";
} else {
    echo "Errore nella creazione della tabella giudizi_recensioni: " . $connessione->error . "<br>";
}

// creazione tabella bonus
$sql = "CREATE TABLE IF NOT EXISTS bonus (
    id_bonus INT AUTO_INCREMENT PRIMARY KEY,
    crediti_bonus DECIMAL(10,2) NOT NULL,
    codice_gioco INT(5),
    data_inizio DATE,
    data_fine DATE,
    FOREIGN KEY (codice_gioco) REFERENCES videogiochi(codice) ON DELETE CASCADE
)";

if ($connessione->query($sql) === TRUE) {
    echo "Tabella bonus creata con successo o già esistente<br>";
} else {
    echo "Errore nella creazione della tabella bonus: " . $connessione->error . "<br>";
}

// inserimento admin
$sql = "INSERT INTO utenti (nome, cognome, username, email, password, tipo_utente, crediti) 
        VALUES ('Mario', 'Rossi', 'admin1', 'admin@gaming.it', 'Admin123!', 'admin', 0)";

if ($connessione->query($sql) === TRUE) {
    echo "Admin inserito con successo<br>";
} else {
    echo "Errore nell'inserimento admin: " . $connessione->error . "<br>";
}

// inserimento gestore
$sql = "INSERT INTO utenti (nome, cognome, username, email, password, tipo_utente, crediti) 
        VALUES ('Luigi', 'Verdi', 'gestore1', 'gestore@gaming.it', 'Gestore123!', 'gestore', 0)";

if ($connessione->query($sql) === TRUE) {
    echo "Gestore inserito con successo<br>";
} else {
    echo "Errore nell'inserimento gestore: " . $connessione->error . "<br>";
}

// inserimento cliente1
$sql = "INSERT INTO utenti (nome, cognome, username, email, password, tipo_utente, crediti) 
        VALUES ('Giuseppe', 'Bianchi', 'cliente1', 'giuseppe@email.it', 'Cliente123!', 'cliente', 100.50)";

if ($connessione->query($sql) === TRUE) {
    echo "Cliente1 inserito con successo<br>";
} else {
    echo "Errore nell'inserimento cliente1: " . $connessione->error . "<br>";
}

// inserimento altri utenti (dopo gli inserimenti esistenti)
$sql = "INSERT INTO utenti (nome, cognome, username, email, password, tipo_utente, crediti) VALUES 
    ('Marco', 'Neri', 'cliente2', 'marco@email.it', 'Cliente123!', 'cliente', 75.00),
    ('Anna', 'Gialli', 'cliente3', 'anna@email.it', 'Cliente123!', 'cliente', 150.00),
    ('Paolo', 'Viola', 'gestore2', 'paolo@gaming.it', 'Gestore123!', 'gestore', 0),
    ('Laura', 'Rosa', 'admin2', 'laura@gaming.it', 'Admin123!', 'admin', 0),
    ('Sofia', 'Azzurri', 'visitatore1', 'sofia@email.it', 'Visitatore123!', 'visitatore', 0),
    ('Luca', 'Marroni', 'cliente4', 'luca@email.it', 'Cliente123!', 'cliente', 200.00)";

if ($connessione->query($sql) === TRUE) {
    echo "Utenti inseriti con successo<br>";
} else {
    echo "Errore nell'inserimento degli utenti: " . $connessione->error . "<br>";
}

// popolamento tabella videogiochi
$sql = "INSERT IGNORE INTO videogiochi (codice, nome, prezzo_originale, prezzo_attuale, genere, data_rilascio, nome_editore, descrizione, immagine) VALUES
    (10001, 'The Witcher 3', 59.99, 39.99, 'RPG', '2015-05-19', 'CD Projekt RED', 'Un epico gioco di ruolo fantasy', 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/292030/header.jpg?t=1730212926'),
    (10002, 'FC 24', 69.99, 69.99, 'Sport', '2023-09-29', 'EA Sports', 'Il più recente simulatore di calcio', 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/2195250/header.jpg?t=1730826798'),
    (10003, 'Cyberpunk 2077', 59.99, 49.99, 'RPG', '2020-12-10', 'CD Projekt RED', 'Un gioco di ruolo ambientato nel futuro', 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/1091500/header.jpg?t=1730212296'),
    (10004, 'Assassins Creed Valhalla', 59.99, 44.99, 'Azione/Avventura', '2020-11-10', 'Ubisoft', 'Avventura vichinga', 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/2208920/header.jpg?t=1732122317'),
    (10005, 'Red Dead Redemption 2', 59.99, 39.99, 'Azione/Avventura', '2018-10-26', 'Rockstar Games', 'Epica avventura western', 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/1174180/header.jpg?t=1720558643'),
    (10006, 'God of War Ragnarök', 69.99, 59.99, 'Azione/Avventura', '2022-11-09', 'Sony', 'Epica avventura norrena', 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/2322010/header.jpg?t=1728067832'),
    (10007, 'Spider-Man 2', 69.99, 69.99, 'Azione/Avventura', '2023-10-20', 'Sony', 'Le avventure dell\'Uomo Ragno', 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/2651280/header.jpg?t=1732310461'),
    (10008, 'Final Fantasy XVI', 69.99, 49.99, 'RPG', '2023-06-22', 'Square Enix', 'Action RPG fantasy', 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/2515020/header.jpg?t=1732559903'),
    (10009, 'Resident Evil 4', 59.99, 39.99, 'Horror', '2023-03-24', 'Capcom', 'Remake del classico horror', 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/2050650/header.jpg?t=1731387968'),
    (10010, 'Horizon Forbidden West', 59.99, 29.99, 'Azione/RPG', '2022-02-18', 'Sony', 'Avventura post-apocalittica', 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/2420110/header.jpg?t=1725653368'),
    (10011, 'Elden Ring', 59.99, 44.99, 'RPG', '2022-02-25', 'FromSoftware', 'Action RPG open world', 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/1245620/header.jpg?t=1726158298'),
    (10012, 'Diablo IV', 69.99, 59.99, 'RPG', '2023-06-06', 'Blizzard', 'Action RPG dark fantasy', 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/2344520/header.jpg?t=1728494275'),
    (10013, 'F1 24', 59.99, 29.99, 'Sport', '2024-05-31', 'Codemasters', 'Simulatore di Formula 1', 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/2488620/header.jpg?t=1732562663')";

if ($connessione->query($sql) === TRUE) {
    echo "Dati inseriti nella tabella videogiochi<br>";
}

// popolamento tabella recensioni
$sql = "INSERT IGNORE INTO recensioni (username, codice_gioco, testo) VALUES
    ('cliente1', 10001, 'Gioco fantastico, grafica stupenda e trama coinvolgente!'),
    ('cliente2', 10001, 'Uno dei migliori giochi mai provati'),
    ('cliente3', 10002, 'Il miglior FIFA degli ultimi anni'),
    ('cliente1', 10003, 'Dopo le patch è diventato un ottimo gioco'),
    ('cliente2', 10004, 'Ambientazione norrena ben realizzata'),
    ('cliente2', 10006, 'Grafica spettacolare e combattimenti epici!'),
    ('cliente3', 10007, 'Il miglior gioco di Spider-Man di sempre'),
    ('cliente1', 10008, 'Storia coinvolgente e sistema di combattimento innovativo'),
    ('cliente4', 10009, 'Remake perfettamente riuscito'),
    ('cliente2', 10010, 'Mondo di gioco incredibile e ben realizzato'),
    ('cliente3', 10011, 'Difficile ma estremamente gratificante')";

if ($connessione->query($sql) === TRUE) {
    echo "Dati inseriti nella tabella recensioni<br>";
}

// popolamento tabella giudizi_recensioni
$sql = "INSERT IGNORE INTO giudizi_recensioni (id_recensione, username_votante, supporto, utilita) VALUES
    (1, 'cliente2', 3, 5),
    (1, 'cliente3', 2, 4),
    (2, 'cliente1', 3, 5),
    (3, 'cliente1', 2, 3),
    (4, 'cliente2', 3, 4),
    (6, 'cliente1', 3, 5),
    (6, 'cliente4', 2, 4),
    (7, 'cliente2', 3, 5),
    (8, 'cliente3', 3, 4),
    (9, 'cliente1', 2, 3),
    (10, 'cliente4', 3, 5)";

if ($connessione->query($sql) === TRUE) {
    echo "Dati inseriti nella tabella giudizi_recensioni<br>";
}

// popolamento tabella bonus
$sql = "INSERT IGNORE INTO bonus (crediti_bonus, codice_gioco, data_inizio, data_fine) VALUES
    (10.00, 10001, '2024-01-01', '2024-12-31'),
    (5.00, 10002, '2024-01-01', '2024-12-31'),
    (15.00, 10003, '2024-01-01', '2024-12-31'),
    (7.50, 10004, '2024-01-01', '2024-12-31')";

if ($connessione->query($sql) === TRUE) {
    echo "Dati inseriti nella tabella bonus<br>";
}

// chiudiamo la connessione
$connessione->close();
echo "Installazione completata!";
?>

