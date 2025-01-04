<?php
session_start();
require_once('connessione.php');

if (!isset($_SESSION['tipo_utente']) || $_SESSION['tipo_utente'] != 'admin') {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    
    if (isset($_POST['ban_utente'])) {
        $query = "UPDATE utenti SET stato = 'bannato' WHERE username = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("s", $username);
        if ($stmt->execute()) {
            $messaggio = "Utente bannato con successo";
        }
    }
    
    elseif (isset($_POST['attiva_utente'])) {
        $query = "UPDATE utenti SET stato = 'attivo' WHERE username = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("s", $username);
        if ($stmt->execute()) {
            $messaggio = "Utente attivato con successo";
        }
    }
    
    elseif (isset($_POST['modifica_dati'])) {
        $nome = $_POST['nome'];
        $cognome = $_POST['cognome'];
        $email = $_POST['email'];
        
        $query = "UPDATE utenti SET nome = ?, cognome = ?, email = ? WHERE username = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("ssss", $nome, $cognome, $email, $username);
        if ($stmt->execute()) {
            $messaggio = "Dati utente aggiornati con successo";
        }
    }
}

// carichiamo la lista degli utenti dal db
$query = "SELECT * FROM utenti WHERE tipo_utente = 'cliente' ORDER BY data_registrazione DESC";
$result = $connessione->query($query);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Utenti</title>
    <style>
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .utente-card {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stato-bannato { color: #dc3545; }
        .stato-attivo { color: #28a745; }
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestione Utenti</h1>
        
        <?php if (isset($messaggio)): ?>
            <div class="messaggio"><?php echo $messaggio; ?></div>
        <?php endif; ?>

        <?php while ($utente = $result->fetch_assoc()): ?>
            <div class="utente-card">
                <h3><?php echo htmlspecialchars($utente['username']); ?></h3>
                <form method="POST">
                    <input type="hidden" name="username" value="<?php echo $utente['username']; ?>">
                    
                    <div class="form-group">
                        <label>Nome:</label>
                        <input type="text" name="nome" value="<?php echo htmlspecialchars($utente['nome']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Cognome:</label>
                        <input type="text" name="cognome" value="<?php echo htmlspecialchars($utente['cognome']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($utente['email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Stato:</label>
                        <span class="stato-<?php echo $utente['stato']; ?>">
                            <?php echo ucfirst($utente['stato']); ?>
                        </span>
                    </div>
                    
                    <button type="submit" name="modifica_dati" class="btn btn-primary">Salva Modifiche</button>
                    
                    <?php if ($utente['stato'] == 'attivo'): ?>
                        <button type="submit" name="ban_utente" class="btn btn-danger"
                                onclick="return confirm('Sei sicuro di voler bannare questo utente?')">
                            Banna Utente
                        </button>
                    <?php else: ?>
                        <button type="submit" name="attiva_utente" class="btn btn-success">
                            Attiva Utente
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>