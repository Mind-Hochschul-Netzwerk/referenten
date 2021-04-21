<?php
namespace MHN\Referenten;

if (isset($ergebnisse)): ?>
    <div id="suchergebnisse">

    <?php if (!count($ergebnisse)): ?>
        <p>Die Suche erbrachte kein Ergebnis.</p>
    <?php else: ?>
        <div class="table-responsive"><table class="table vertical-center" id="suchergebnisse">
            <tr><th>#</th><th>Beitragstitel</th><th>Referent</th><th>Veranstaltung</th></tr>
            <?php
            
            $n = 0;
            foreach ($ergebnisse as $e) {
                ++$n;
                $class = '';
                if ((time() - strtotime($e['beitragsjahr'])) > 6*31*24*3600) {
                    $class = "inaktiv";
                }
                echo "<tr class='$class'>
                    <td>$n</td>
                    <td><a href='vortrag.php?vid=$e[vid]'>$e[vTitel] [". Util::getBeitragsformAsText(($e['beitragsform'])) . "]</a>";

                if ($e['locked']) {
                    echo ' <span class="glyphicon glyphicon-lock"></span>';
                }
                if ($e['publish']) {
                    echo ' <span class="glyphicon glyphicon-globe"></span>';
                }

                echo "</td><td>";

                foreach ($e['referenten'] as $uid => $daten) {
                    if (Auth::hatRecht('ma-pt') || Auth::ist($uid)) {
                        echo "<a href='benutzer.php?uid=$uid'>$daten[fullName]</a><br>";
                    } else {
                        echo "$daten[fullName]<br>";
                    }
                }
                echo "</td>
                    <td>$e[veranstaltung]</td>
                </tr>\n";
            }
            ?>
            
        </table></div>
        
        <?php endif; ?>   
    </div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="uid" value="<?=$userId?>">

    <div class="form-group row">
        <div class="col-sm-2">
            <button id="neu" name="neu" type="submit" class="btn btn-success" ><span class="glyphicon glyphicon-plus"></span> neuen Beitrag anlegen</button>
        </div>
    </div>

</form>
