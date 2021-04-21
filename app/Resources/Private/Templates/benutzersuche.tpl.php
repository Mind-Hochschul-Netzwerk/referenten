<form>

    <div class="form-group row">
        <div class="col-sm-10">
            <input name="q" type="search" placeholder="Suche" class="form-control"/>
        </div>
        <div class="col-sm-2">
            <button type="submit" class="btn btn-success" onclick="return suchen();"><span
                        class="glyphicon glyphicon-search"></span> Suchen
            </button>
        </div>
    </div>

</form>

<?php if (isset($ergebnisse)): ?>
    <div id="suchergebnisse">
        <h2>Suchergebnisse</h2>

        <?php if (!count($ergebnisse)): ?>
            <p>Die Suche erbrachte kein Ergebnis.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table vertical-center" id="suchergebnisse">
                    <tr>
                        <th>#</th>
                        <th>Profilbild</th>
                        <th>Name / Sichtung</th>
                        <th>Email-Adresse</th>
                        <th>Letzter Beitrag</th>
                    </tr>
                    <?php

                    $n = 0;
                    $graue = false;
                    foreach ($ergebnisse as $e) {
                        ++$n;
                        $class = '';
                        $thumbnail = "<div class='thumbnail-container'><a href='benutzer.php?uid=$e[uid]'><img class='img-thumbnail' src='" . ($e['profilbild'] ? 'profilbilder/' . $e['profilbild'] : 'img/thumbnail-profilbild-default.png') . "' alt='Profilbild'  ></a></div>";
                        if ($e['last_login'] === null || $e['last_login'] < new \DateTime('-6 months')) {
                            $class = "inaktiv";
                            $graue = true;
                        }
                        if ($e['beitragsjahr'] === null) {
                            $e['beitragsjahr'] = 'nie';
                        }

                        echo "<tr class='$class'>
                    <td>$n</td>
                    <td>$thumbnail</td>
                    <td><a href='benutzer.php?uid=$e[uid]'>$e[fullName]</a>";

                        if ($e['locked']) {
                            echo ' <span class="glyphicon glyphicon-lock"></span>';
                        }
                        if ($e['publish']) {
                            echo ' <span class="glyphicon glyphicon-globe"></span>';
                        }

                        echo "</td>
                    <td>$e[email]</td>
                    <td>$e[beitragsjahr]</td>
                </tr>\n";
                    }
                    ?>

                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<form action="benutzer.php" method="post">
    <div class="form-group row">
        <div class="col-sm-12">
            <button id="neu" name="neu" type="submit" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span> Neuen Referenten-Account anlegen</button>
            (Um neue Programmteam-Accounts anzulegen: Referenten-Account anlegen und dann die Einstellung "Rechte" entsprechend anpassen.)
        </div>
    </div>
</form>
