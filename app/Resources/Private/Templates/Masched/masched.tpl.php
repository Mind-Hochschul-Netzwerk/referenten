<?php namespace MHN\Referenten; ?>

<h3>JSON-Cache</h3>

<?php if ($maschedTime->getTimestamp()): ?>
    <p>Stand der App-Daten im Cache: <?=$maschedTime->format('d.m.Y H:i')?> Uhr (<a href="masched.php?clearCache=1">leeren)</a></p>
<?php else: ?>
    <p>Stand der App-Daten im Cache: Der Cache wird bei der nächsten Verwendung neu generiert.</p>
<?php endif; ?>

<div class="col-sm-4">

<h3>Blockung</h3>

<h4>Blockung aus Datei (xlsx oder ods) importieren.</h4>

<p>Die Datei muss eine Tabelle enthalten. In der ersten Zeile müssen die Spaltenüberschriften <code>titel</code>, <code>typ</code>, <code>datum</code>, <code>uhrzeit_beginn</code>, <code>uhrzeit_ende</code> vorkommen. In jeder Zeile steht dann ein Block, wobei in der Spalte <code>typ</code> der Wert <code>Pause</code> oder <code>Slot</code> (für einen freien Slot, der befüllt werden kann) stehen muss.</p>

<form enctype="multipart/form-data" method="post">
    <div class="row">
        <div class="col-sm-10">
            <div class="input-group">
                <label class="input-group-btn">
                    <span class="btn btn-primary">
                        Datei auswählen &hellip; <input name="bloecke" type="file" style="display: none;">
                    </span>
                </label>
                <input type="text" class="form-control" readonly="readonly">
            </div>
        </div>

        <div class="col-sm-2">
            <button type="submit" class="btn btn-success">Laden</button>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-condensed">
        <tr><th>Titel</th><th>Typ</th><th>Beginn</th><th>Ende</th></tr>
        <?php foreach ($bloecke as $block): extract($block); ?>
            <tr><td><?=$titel?></td><td><?=array_search($typ, ['Slot'=>'f', 'Pause'=>'p'])?></td><td><?=$beginn->format('d.m. H:i')?></td><td><?=$ende->format('d.m. H:i')?></td></tr>
        <?php endforeach; ?>
    </table>
</div>

</div>
<div class="col-sm-8">

<h3>Programmimport</h3>

<h4>Programm aus Datei (xlsx oder ods) importieren.</h4>

<p>Die Datei muss eine Tabelle enthalten. In der ersten Zeile müssen die Spaltenüberschriften <code>vid</code>, <code>beitrag_titel</code>, <code>beitrag_typ</code>, <code>beitrag_raum</code>, <code>datum</code>, <code>uhrzeit_beginn</code>, <code>uhrzeit_ende</code> vorkommen. Die Reihenfolge und zusätzliche andere Spalten sind egal.</p>
<p>Die Einträge in der Spalte <code>vid</code> sind eine eindeutige Zuordnung des Beitrags und dürfen später nicht mehr geändert werden. Falls <code>vid</code> eine Zahl ist, bezieht sich der Wert auf den entsprechenden Beitrag mit der Vortragskennung. Beiträge, bei denen <code>vid</code> <strong>keine</strong> Zahl ist (z.B. &quot;rahmen_1&quot;) sind Rahmenprogramm.</p>
<p>Erlaubte Werte in der Spalte <code>beitrag_typ</code> sind <code>Sonstiges</code>, <code>Rahmenprogramm</code>, <code>Workshop</code> und <code>Vortrag</code>.</p>

<form enctype="multipart/form-data" method="post">
    <div class="row">
        <div class="col-sm-10">
            <div class="input-group">
                <label class="input-group-btn">
                    <span class="btn btn-primary">
                        Datei auswählen &hellip; <input name="programm" type="file" style="display: none;">
                    </span>
                </label>
                <input type="text" class="form-control" readonly="readonly">
            </div>
        </div>

        <div class="col-sm-2">
            <button type="submit" class="btn btn-success">Laden</button>
        </div>
    </div>
</form>

<h4>Rahmenprogramm</h4>

<div class="table-responsive">
    <table class="table table-striped table-condensed">
        <tr><th>ID</th><th>Titel</th><th>Typ</th><th>Raum</th><th>Beginn</th><th>Ende</th></tr>
        <?php foreach ($rahmenprogramm as $punkt): extract($punkt); ?>
            <tr><td><?=$id?></td><td><?=$titel?></td><td><?=$beitragsformText?><td><?=$raum?></td><td><?=$beginn->format('d.m. H:i')?></td><td><?=$ende->format('d.m. H:i')?></td></tr>
        <?php endforeach; ?>
    </table>
</div>

</div>
