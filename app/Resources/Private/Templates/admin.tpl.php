<?php
namespace MHN\Referenten;
?>
<h2>Mitgliederdaten bearbeiten</h2>
<p>Suche das zu bearbeitende Mitglied mit der <a href="benutzersuche.php">Mitgliedersuche</a> und klicke im Profil auf "Daten bearbeiten".</p>

<?php if (Auth::hatRecht('ma-pt')): ?>
    <h2>Programm</h2>
    <p><a href="masched.php">Programmplan importieren.</a></p>
<?php endif; ?>

<?php if (Auth::hatRecht('rechte')): ?>
<h2>Rollen verwalten</h2>
<p>Suche das zu bearbeitende Mitglied mit der <a href="benutzersuche.php">Mitgliedersuche</a> und klicke im Profil auf "Daten bearbeiten".</p>
<p>Aktuell implementierte Rollen:</p>
<ul>
    <li><code>rechte</code>: Kann Rechte ändern. Impliziert alle Rechte! </li>
    <li><code>referent</code>: Referent. Kann seine Personendaten und die Daten zu seinem Vortrag anpassen.</li>
    <li><code>ma-pt</code>: Mind-Akademie Programmteam. Kann alle Referentendaten und deren Vorträge lesen und ändern.</li>
</ul>
<h3>Übersicht über gesetzte Rollen</h3>
<div id="panels" class="row">
<?php
    $recht_prev = null;
    $n = 0;
    foreach ($rechte as $item) {
        if ($recht_prev != $item['recht']) {
            ++$n;
            if ($recht_prev != null) {
                echo "</div></div></div>";
            }
            echo "<div class='col-sm-2'><div class='panel panel-default'><div class='panel-heading'><a class='collapsed' data-toggle='collapse' data-parent='#panels' href='#panel-$n'>$item[recht]</div><div id='panel-$n' class='panel-body collapse'>\n";
            $recht_prev = $item['recht'];
        }
        echo "<div><a href='benutzer.php?uid=$item[uid]'>$item[vorname] $item[nachname]</a></div>";
    }
    echo "</div></div></div>";
?>
</div>
<?php endif; ?>
