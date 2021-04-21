<?php
namespace MHN\Referenten;
?>
<div class="jumbotron">
        <h1>MHN-Referententool</h1>
        <p>Willkommen im Referententool des MinD-Hochschul-Netzwerks.</p>
    </div>
    
        <?php if (Auth::hatRecht('ma-pt')): ?>
        <div class="row">
            <div class="col-sm-6">
                <h3><span class="glyphicon glyphicon-search"></span> <a href="vortragssuche.php">Vortagssuche</a></h3>
                <p>Suchen Sie nach Vorträgen.</p>
            </div>
            <div class="col-sm-6">
                <h3><span class="glyphicon glyphicon-search"></span> <a href="benutzersuche.php">Benutzersuche</a></h3>
                <p>Suchen Sie nach Benutzern.</p>
            </div>
        </div>
        <?php endif; ?>
    <div class="row">
        <div class="col-sm-6">
            <h3><span class="glyphicon glyphicon-user"></span> <a href="benutzer.php">Mein Profil</a></h3>
            <p>Bearbeiten Sie Ihre Daten, geben Sie Ihre Kurzvita an und laden Sie ein Bild von sich hoch.</p>
        </div>
        <div class="col-sm-6">
            <h3><span class="glyphicon glyphicon-calendar"></span> <a href="vortragsliste.php">Meine Beiträge</a></h3>
            <p>Legen Sie einen neuen Programmpunkt an oder bearbeiten Sie einen Ihrer Beiträge.</p>
        </div>
    </div>
