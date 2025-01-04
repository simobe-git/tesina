<?php
$current_page = basename($_SERVER['PHP_SELF']);

// array contenente tutte le voci del menu base
$menu_items = [
    'home.php' => 'Home',
    'catalogo.php' => 'Catalogo',
    'offerte.php' => 'Offerte',
    'faq.php' => 'FAQ',
    'contatti.php' => 'Contatti'
];

// aggiungimo le voci condizionali in base al login
if (isset($_SESSION['statoLogin'])) {
    $menu_items['profilo.php'] = 'Profilo';
} else {
    $menu_items['login.php'] = 'Login';
}

// pagine che dovrebbero escludere determinate voci
$profile_pages = ['profilo.php', 'modifica_profilo.php', 'modifica_password.php'];
$auth_pages = ['login.php', 'registration.php', 'reset-password.php'];
?>

<nav class="navbar">
    <div class="logo">
        <a href="home.php">GameShop</a>
    </div>
    <ul class="nav-links">
        <?php
        // mostra le voci del menu base escludendo la pagina corrente
        foreach ($menu_items as $page => $label) {
            if ($page !== $current_page && 
                !($page === 'profilo.php' && in_array($current_page, $profile_pages)) &&
                !($page === 'login.php' && in_array($current_page, $auth_pages))) {
                
                // non mostriamo la voce 'login' se l'utente è già loggato
                if (!($page === 'login.php' && isset($_SESSION['statoLogin']))) {
                    echo "<li><a href=\"$page\">$label</a></li>";
                }
            }
        }

        // gestione voci basate sul login
        if (isset($_SESSION['statoLogin'])) {
            if (isset($_SESSION['ruolo'])) {
                if ($_SESSION['ruolo'] === 'cliente') {
                    echo "<li><a href=\"carrello.php\">Carrello</a></li>";
                } elseif ($_SESSION['ruolo'] === 'admin') {
                    echo "<li><a href=\"admin_dashboard.php\">Dashboard</a></li>";
                }
            }
            echo "<li><a href=\"logout.php\">Logout</a></li>";
        }
        ?>
    </ul>
    <div class="hamburger-menu">
        <span></span>
        <span></span>
        <span></span>
    </div>
</nav>