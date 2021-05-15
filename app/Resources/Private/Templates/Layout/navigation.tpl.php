<?php
namespace MHN\Referenten;

if (Auth::istEingeloggt()) {
    $navItems = [
        'home' => ['/', 'Startseite', 'home'],
        'benutzersuche' => Auth::hatRecht('ma-pt') ? ['benutzersuche.php', 'Benutzersuche', 'search'] : null,
        'vortragssuche' => Auth::hatRecht('ma-pt') ? ['vortragssuche.php', 'Vortragssuche', 'search'] : null,
        'admin' => Auth::hatRecht('ma-pt') ? ['admin.php', 'Programmteam', 'wrench'] : null,
        'benutzer' => ['benutzer.php', 'Mein Profil', 'user'],
        'vortragsliste' => (Auth::hatRecht('referent') && !Auth::hatRecht('ma-pt')) ? ['vortragsliste.php', 'Meine Beiträge', 'calendar'] : null,
        'logout' => ['logout.php', 'Logout', 'log-out'],
        'akademie' => ['https://www.mind-akademie.de/', 'MA-Webseite', 'home'],
        'moodle' => ['https://www.' . getenv('DOMAINNAME'), 'MHN-Webseite', 'home'],
        'datenschutz' => ['https://www.' . getenv('DOMAINNAME') . '/mod/page/view.php?id=12', 'Datenschutz', 'paragraph'],
        'datenverarbeitung' => ['datenverarbeitung.php', 'Datenverarbeitung', 'paragraph'],
        'impressum' => ['https://www.' . getenv('DOMAINNAME') . '/mod/page/view.php?id=5', 'Impressum', 'globe'],
    ];

} else {
    $navItems = [
        'login' => ['/', 'Login', 'log-in'],
        'registrieren' => ['registrieren.php', 'Registrieren', 'plus'],
        'akademie' => ['https://www.mind-akademie.de/', 'MA-Webseite', 'home'],
        'moodle' => ['https://www.' . getenv('DOMAINNAME'), 'MHN-Webseite', 'home'],
        'datenschutz' => ['https://www.' . getenv('DOMAINNAME') . '/mod/page/view.php?id=12', 'Datenschutz', 'paragraph'],
        'impressum' => ['https://www.' . getenv('DOMAINNAME') . '/mod/page/view.php?id=5', 'Impressum', 'globe'],
    ];
}
?>
<nav class="navbar navbar-mhn sidebar" role="navigation">
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-sidebar-navbar-collapse-1">
                <span class="sr-only">Navigation aufklappen</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="/"><img src="/img/mhn-logo-small.png" id="mhn-logo"><span class="logo-text"> Referenten
            </span>
                <span class='pull-right showopacity glyphicon'><img src="/img/mhn-logo-small.png" id="mhn-icon"></span>
            </a>
        </div>
        <div class="collapse navbar-collapse" id="bs-sidebar-navbar-collapse-1">
            <ul class="nav navbar-nav">
<?php

foreach ($navItems as $itemname => $item) {
    if (!$item) {
        continue;
    }
    $class = (!empty($navId) and $navId === $itemname) ? 'active' : '';
    echo "<li class='$class'><a href='$item[0]'>$item[1]<span class='pull-right showopacity glyphicon glyphicon-$item[2]'></span></a></li>\n";
}
?>
            </ul>
        </div>
    </div>
</nav>
