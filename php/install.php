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
    'gioco_tavolo',
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

// creazione tabella giochi (se non esiste già)
$sql = "CREATE TABLE IF NOT EXISTS gioco_tavolo (
    codice INT(5) NOT NULL PRIMARY KEY,
    titolo VARCHAR(50) NOT NULL,  
    prezzo_originale DOUBLE(6,2) NOT NULL,
    prezzo_attuale DOUBLE(6,2) NOT NULL,
    disponibile BIT NOT NULL, -- (0 = non disponibile, 1 = disponibile, NULL = non specificato)
    categoria VARCHAR(30) NOT NULL,
    min_num_giocatori INT(2) NOT NULL, -- Si poteva implementare anche come una stringa
    max_num_giocatori INT(2) NOT NULL,
    min_eta VARCHAR(3) NOT NULL, -- varchar posso inserire età con carattere + per specificare età minima in sù
    avg_partita INT(3) NOT NULL, -- VARCHAR specifica durate diverse parita
    data_rilascio DATE NOT NULL,
    nome_editore VARCHAR(30) NOT NULL,
    autore VARCHAR(100) NOT NULL, -- N/A specifica non disponibile
    descrizione TEXT,
    meccaniche SET('Movimento','Combattimento','Raccolta risorse','Scelte strategiche','Interazione tra giocatori','Lancio di dadi','Movimento di pedine') NOT NULL, -- SET assegna più di un valore
    ambientazione ENUM('Fantasy', 'Storico', 'Fantascienza', 'Distopica', 'Realistica') NOT NULL, -- Valori di default da poter scegliere
    immagine VARCHAR(255) -- imamgine delle componenti o logo  
)";

if ($connessione->query($sql) === TRUE) {
    echo "Tabella giochi creata con successo o già esistente<br>";
} else {
    echo "Errore nella creazione della tabella giochi: " . $connessione->error . "<br>";
}

// creazione tabella recensioni
$sql = "CREATE TABLE IF NOT EXISTS recensioni (
    id_recensione INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30),
    codice_gioco INT(5),
    testo TEXT,
    data_recensione DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (username) REFERENCES utenti(username) ON DELETE CASCADE,
    FOREIGN KEY (codice_gioco) REFERENCES gioco_tavolo(codice) ON DELETE CASCADE
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
    FOREIGN KEY (codice_gioco) REFERENCES gioco_tavolo(codice) ON DELETE CASCADE
)";

if ($connessione->query($sql) === TRUE) {
    echo "Tabella bonus creata con successo o già esistente<br>";
} else {
    echo "Errore nella creazione della tabella bonus: " . $connessione->error . "<br>";
}


// inserimento altri utenti (dopo gli inserimenti esistenti)
$sql = "INSERT INTO utenti (nome, cognome, username, email, password, tipo_utente, crediti) VALUES 
    ('Marco', 'Neri', 'cliente2', 'marco@email.it', 'Cliente123!', 'cliente', 75.00),
    ('Mario', 'Rossi', 'admin1', 'admin@gaming.it', 'Admin123!', 'admin', 0.00),
    ('Anna', 'Gialli', 'cliente3', 'anna@email.it', 'Cliente123!', 'cliente', 150.00),
    ('Paolo', 'Viola', 'gestore2', 'paolo@gaming.it', 'Gestore123!', 'gestore', 0.00),
    ('Laura', 'Rosa', 'admin2', 'laura@gaming.it', 'Admin123!', 'admin', 0.00),
    ('Sofia', 'Azzurri', 'visitatore1', 'sofia@email.it', 'Visitatore123!', 'visitatore', 0.00),
    ('Luca', 'Marroni', 'cliente4', 'luca@email.it', 'Cliente123!', 'cliente', 200.00),
    ('Giuseppe', 'Bianchi', 'cliente1', 'giuseppe@email.it', 'Cliente123!', 'cliente', 100.50),
    ('Luigi', 'Verdi', 'gestore1', 'gestore@gaming.it', 'Gestore123!', 'gestore', 0.00)";

if ($connessione->query($sql) === TRUE) {
    echo "Utenti inseriti con successo<br>";
} else {
    echo "Errore nell'inserimento degli utenti: " . $connessione->error . "<br>";
}

// popolamento tabella giochi_tavolo
$sql = "INSERT IGNORE INTO gioco_tavolo (codice, titolo, prezzo_originale, prezzo_attuale, disponibile, categoria, min_num_giocatori, max_num_giocatori, min_eta, avg_partita, data_rilascio, nome_editore, autore, descrizione, meccaniche, ambientazione, immagine) VALUES
    (1, 'Brass Birmingham', 105.00, 40.00, 1, 'Strategia', 2, 4, '14+', 90, 2018, 'Roxley', 'Bravan Grown', 'Racconta la storia di imprenditori in competizione tra loro a Birmingham durante la rivoluzione industriale.', 'Raccolta risorse,Scelte strategiche', 'Storico', 'https://cf.geekdo-images.com/UIlFaaTmaWms7F5xdEFgGA__imagepage/img/SitcV7akzI3P_dl8pPEneEpM-U4=/fit-in/900x600/filters:no_upscale():strip_icc()/pic3549793.jpg'),
    (2, 'Monopoly', 30.00, 20.00, 1, 'Sociale', 2, 6, '8+', 210, 1935, 'Hasbro', 'Elizabeth Magie', 'bello', 'Lancio di dadi,Movimento di pedine', 'Storico', 'https://cf.geekdo-images.com/uyuTD45L9R99vEGF0RTLUQ__imagepage/img/tc-UrchQhoi2k_5wb51l22iW40k=/fit-in/900x600/filters:no_upscale():strip_icc()/pic5872489.jpg'),
    (3, 'Indovina Chi?', 30.00, 20.00, 1, 'Deduzione', 2, 2, '6+', 20, 1980, 'Hasbro/Milton Bradley', 'Theo e Ora Coster', 'bello', 'Scelte strategiche', 'Storico', 'https://cf.geekdo-images.com/9IzIcHIJVY08OYkXpTmUQA__imagepage/img/JlSpAr2_5CGK58yA7tUwgmRDQaU=/fit-in/900x600/filters:no_upscale():strip_icc()/pic335812.jpg'),
    (4, 'Jenga', 35.00, 25.00, 1, 'Costruzioni', 1, 8, '6+', 15, 2018, 'Hasbro', 'Leslie Scott', 'Non farla cadere!', 'Scelte strategiche', 'Storico', 'https://cf.geekdo-images.com/KOb8nKGW63o2cCvno0H5Ug__imagepage/img/x5k6swUJbXXBW3rzqFnlOkAyNHk=/fit-in/900x600/filters:no_upscale():strip_icc()/pic4943939.jpg'),
    (5, 'Catan', 45.00, 35.00, 1, 'Gestionale', 3, 4, '10+', 90, 1995, 'Kosmos', 'Klaus Teuber', 'Colleziona risorse e costruisci il tuo impero!', 'Raccolta risorse', 'Storico', 'https://cf.geekdo-images.com/yGOeKCQpS82s_F-Kr2FxoQ__imagepage/img/Xtt53sTINWlkLMmyiK7Ghm8gkIw=/fit-in/900x600/filters:no_upscale():strip_icc()/pic8632669.png'),  
    (6, 'Carcassonne', 40.00, 30.00, 1, 'Piazzamento tessere', 2, 5, '7+', 35, 2000, 'Hans im Glück', 'Klaus-Jürgen Wrede', 'Costruisci città e conquista territori.', 'Raccolta risorse,Scelte strategiche', 'Storico', 'https://cf.geekdo-images.com/okM0dq_bEXnbyQTOvHfwRA__imagepage/img/axGbn7f0x6UUb07DmzX9hg5TwIo=/fit-in/900x600/filters:no_upscale():strip_icc()/pic6544250.png'),  
    (7, 'Dixit', 35.00, 28.00, 1, 'Narrazione', 3, 6, '8+', 30, 2008, 'Libellud', 'Jean-Louis Roubira', 'Un gioco di immaginazione e creatività.', 'Movimento', 'Storico', 'https://cf.geekdo-images.com/J0PlHArkZDJ57H-brXW2Fw__imagepage/img/j3bmkyVXJ2tOOXQzARtHqT__vzY=/fit-in/900x600/filters:no_upscale():strip_icc()/pic6738336.jpg'),  
    (8, 'Risiko!', 50.00, 40.00, 1, 'Strategia', 2, 6, '10+', 120, 1968, 'Hasbro', 'Albert Lamorisse', 'Conquista il mondo con la tua strategia!', 'Scelte strategiche', 'Storico', 'https://cf.geekdo-images.com/bWkEcY1KeFwyTMmT3ka9GA__imagepage/img/j1IxmVM-lcY06qozTZdngIE6pqw=/fit-in/900x600/filters:no_upscale():strip_icc()/pic5726714.png'),  
    (9, 'Cluedo', 30.00, 22.00, 1, 'Investigazione', 2, 6, '8+', 45, 1949, 'Hasbro', 'Anthony Pratt', 'Scopri chi è assassino e con quale arma.', 'Interazione tra giocatori', 'Storico', 'https://cf.geekdo-images.com/YJHF5PpGcaA5xydP7Ct-ww__imagepage/img/fMA4QzYY0L5NZxFnCNDIB3htzAQ=/fit-in/900x600/filters:no_upscale():strip_icc()/pic7623116.jpg'),  
    (10, 'Scotland Yard', 35.00, 27.00, 1, 'Investigazione', 2, 6, '10+', 45, 1983, 'Ravensburger', 'Werner Schlegel', 'Cattura il misterioso Mister X per vincere.', 'Scelte strategiche', 'Storico', 'https://cf.geekdo-images.com/tVTwaRU6Y6Cv1qei2dbdbQ__imagepage/img/Uo-9Kb_CWShYmJX7_G4nepc3nNU=/fit-in/900x600/filters:no_upscale():strip_icc()/pic8377407.jpg'),  
    (11, 'Azul', 40.00, 32.00, 1, 'Astratto', 2, 4, '8+', 40, 2018, 'Plan B Games', 'Michael Kiesling', 'Crea il miglior mosaico con le tessere.', 'Movimento', 'Storico', 'https://cf.geekdo-images.com/aPSHJO0d0XOpQR5X-wJonw__imagepage/img/q4uWd2nXGeEkKDR8Cc3NhXG9PEU=/fit-in/900x600/filters:no_upscale():strip_icc()/pic6973671.png')";

if ($connessione->query($sql) === TRUE) {
    echo "Dati inseriti nella tabella gioco_tavolo<br>";
} else {
    echo "Errore nell'inserimento degli utenti: " . $connessione->error . "<br>";
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

