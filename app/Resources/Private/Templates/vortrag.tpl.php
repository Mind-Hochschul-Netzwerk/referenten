<?php
use MHN\Referenten\Tpl;
use MHN\Referenten\Auth;
use MHN\Referenten\Benutzer;

Tpl::set('title', "Mein Beitrag auf der MinD-Akademie", false);

function getHtmlForFormInput($input, $label, $cols, &$hasDanger)
{
    $html = '';

    $vid = $input[0];
    $value = $input[1];
    if (gettype($value) === 'object' && get_class($value) === 'DateTime') {
        $value = $value->format('d.m.Y H:i:s');
    }

    $select = (!empty($input[2]) and $input[2] == 'select');
    $textarea = (!empty($input[2]) and $input[2] == 'textarea');
    $type = !empty($input[2]) ? "type='$input[2]'" : '';


    $class = !empty($input['class']) ? $input['class'] : 'form-control';

    if (!empty($input['maxlength'])) {
        $maxlength = $input['maxlength'];
    } else {
        $maxlength = (!empty($input[2]) && $input[2] == 'textarea') ? 1000 : 255;
    }

    if (!empty($input['error'])) {
        $class .= ' form-control-danger';
        $hasDanger = 'has-danger';
    }

    if (empty($input['disabled'])) {
        $name = "name='$vid'";
        $disabled = '';
    } else {
        $name = '';
        $disabled = 'disabled="disabled"';
        $placeholder = '';
        if (!isset($input['title'])) $input['title'] = 'Bitte wenden Sie sich an das Programmteam, wenn dieses Feld geändert werden muss.';
    }

    $placeholder = !empty($input['placeholder']) ? $input['placeholder'] : strip_tags($label);
    $title = !empty($input['title']) ? $input['title'] : $placeholder;

    if ($select) {
        $tag = "<select id='input-$vid' $name class='$class' $disabled title='$title'>\n";
        foreach ($input['options'] as $key=>$text) {
            $tag .= "<option value='$key' ".(strtolower($value)==strtolower($key)?'selected="selected"':'').">$text</option>\n";
        }
        $tag .= "</select>\n";
    } elseif ($textarea)  {
        $textCols = $input['options']['cols'];
        $textRows = $input['options']['rows'];

        $tag = "<textarea cols='$textCols' rows='$textRows'  id='input-$vid' $name class='$class' $disabled placeholder='$placeholder' title='$title' maxlength='$maxlength'>$value</textarea>";
    } else {
        $tag = "<input id='input-$vid' $name value='$value' $type class='$class' $disabled placeholder='$placeholder' title='$title' maxlength='$maxlength'>\n";
    }

    if (!empty($input['sichtbarkeit'])) {
        $name = $input['sichtbarkeit'][0];
        $checked = $input['sichtbarkeit'][1] ? 'checked="checked"' : '';
        $tag = "<div class='input-group' data-tooltip='sichtbarkeit'>$tag
            <span class='input-group-addon'><input class='input-group-addon' name='$name' data-height='32' data-width='50' data-toggle='toggle' data-onstyle='success' data-offstyle='danger' data-on='&lt;span class=&quot;glyphicon glyphicon-eye-open&quot;&gt;&lt;/span&gt;' data-off='&lt;span class=&quot;glyphicon glyphicon-eye-close&quot;&gt;&lt;/span&gt;' type='checkbox' $checked></span>
                </div>";
    }

    $html .= "<div class='col-sm-$cols'>$tag</div>";
    return $html;
}

//ToDo: Funktionen zentral bereitstellen, da Mehrfachverwendung!!
function form_row($label, $inputs) {
    $html = '';
    $for = 'input-' . $inputs[0][0];
    $hasDanger = '';

    foreach ($inputs as $vid=>$input) {
        $cols = !empty($input['cols']) ? $input['cols'] : floor(10/count($inputs));
        $html .= getHtmlForFormInput($input, $label, $cols, $hasDanger);
    }

    if (!$label) return $html;

    return "<div class='form-group row $hasDanger'>
        <label for='$vid' class='col-sm-2 col-form-label'>$label</label>
        $html
    </div>\n";
}

$active_pane = 'profile';

$changes = $error = $password_error = false;

foreach ($referenten as $uid => $daten) {
    // Alerts generieren
    if (Auth::hatRecht('ma-pt') || Auth::ist($uid)) {
        $benutzer = Benutzer::lade($uid, true);
        if(!$benutzer->get('profilbild') || !$benutzer->get('kurzvita')) {
            Tpl::set('alert_type', 'warning');
            Tpl::set('alert_text', $benutzer->get('fullName') . ' - Bitte denken Sie daran, <a href="benutzer.php?uid='.$benutzer->get('uid').'">Ihre Daten zu vervollständigen</a> und eine Kurzvita und ein Foto hochzuladen.', false);
            Tpl::render('Layout/alert');
        }
    }
}

if ($deleted) {
    Tpl::set('alert_type', 'warning');
    Tpl::set('alert_text', 'Dieser Beitrag wurde gelöscht und ist daher nur noch für Mitglieder des Programmteams auffindbar.', false);
    Tpl::render('Layout/alert');
}

if ($locked) {
    Tpl::set('alert_type', 'warning');
    Tpl::set('alert_text', 'Dieser Beitrag wurde bereits gesichtet und ist daher schreibgeschützt. Wenn noch etwas geändert werden soll, wenden Sie sich bitte an das Programmteam.', false);
    Tpl::render('Layout/alert');
}

if (!empty($data_saved_info)) {
    Tpl::set('alert_type', 'success');
    Tpl::set('alert_text',
        (!$error ? 'Ihre Daten wurden geändert.' : 'Die anderen Änderungen wurden gespeichert.') .
        (Auth::hatRecht('ma-pt') ? ' Damit die Daten in der MA-App aktualisiert werden, muss der JSON-Cache geleert werden. <a href="/masched.php?clearCache=1">Cache jetzt leeren.</a>' : ''),
        false);
    Tpl::render('Layout/alert');
}

?>

<form enctype="multipart/form-data" method="post">

<input type="hidden" name="vid" value="<?=$vid?>">

<div class="tab-content">
    <div class="tab-pane active" id="profile">
        <?= form_row('Event <span class="glyphicon glyphicon-globe"></span>', [
                ['eid', $eid, 'select', 'options' => $events]
        ]) ?>
        <?=form_row('Titel, Beitragsform <span class="glyphicon glyphicon-globe"></span>', [
            ['vTitel', $vTitel, 'placeholder' => 'Titel'],
            ['beitragsform', $beitragsform, 'select', 'options' => [
                'v' => 'Vortrag',
                'w' => 'Workshop',
                's' => 'sonstiges'
            ]],
        ]) ?>
        <?=form_row('Kurztitel (max. 24 Zeichen) <span class="glyphicon glyphicon-globe"></span>', [['kurztitel', $kurztitel, 'maxlength' => 24]])?>
        <div class='form-group row'>
            <label for='$vid' class='col-sm-2 col-form-label'>Referent*innen <span class="glyphicon glyphicon-globe"></span></label>
            <?php if (Auth::hatRecht('ma-pt')): ?>
                <?=getHtmlForFormInput(['uids',  $uids, 'title' => 'Dieses Feld ist nur vom Programmteam änderbar.'], 'Benutzerkennungen', 2, $dummy)?>
            <?php endif; ?>
            <div class="col-sm-8" style="padding-top: 5px;">
            <?php
            foreach ($referenten as $uid => $daten) {
                echo ' <span class="kapsel"><span class="glyphicon glyphicon-user"></span> <a href="benutzer.php?uid=' . $uid . '">' . $daten['name'] . '</a>';
                if ($daten['locked']) {
                    echo ' <span class="glyphicon glyphicon-lock"></span>';
                }
                if ($daten['publish']) {
                    echo ' <span class="glyphicon glyphicon-globe"></span>';
                }
                echo '</span>';
            }
            ?>
            </div>
        </div>

        <?=form_row('Sprache <span class="glyphicon glyphicon-globe"></span>', [['beitragssprache', $beitragssprache, 'select', 'options' => [
            'd' => 'deutsch',
            'e' => 'englisch',
            'b' => 'beides möglich'
        ]]])?>
        <?=form_row('beschänkte Teilnehmeranzahl <span class="glyphicon glyphicon-globe"></span>', [
            ['beschrTeilnehmeranzahl', $beschrTeilnehmeranzahl, 'select', 'options' => [
                'n' => 'Nein',
                'j' => 'Ja'
            ]],
            ['maxTeilnehmeranzahl', $maxTeilnehmeranzahl, 'placeholder' => 'maximale Teilnehmeranzahl'],
        ])?>

        <div class="row form-group">
            <div class="col-sm-2">
                Benötigtes Equipment
            </div>
            <div class="col-sm-10">
                <div class="checkbox"><label><input name="equipment_beamer" type="checkbox" <?=$equipment_beamer?'checked="checked"':''?>> Beamer</label></div>
                <div class="checkbox"><label><input name="equipment_computer" type="checkbox" <?=$equipment_computer?'checked="checked"':''?>> Computer</label></div>
                <div class="checkbox"><label><input name="equipment_wlan" type="checkbox" <?=$equipment_wlan?'checked="checked"':''?>> Internetzugang / WLAN</label></div>
                <div class="checkbox"><label><input name="equipment_lautsprecher" type="checkbox"  <?=$equipment_lautsprecher?'checked="checked"':''?> > Lautsprecher</label></div>
                <div class="checkbox"><label><input name="equipment_mikrofon" type="checkbox"  <?=$equipment_mikrofon?'checked="checked"':''?> > Mikrofon (incl. Lautsprecher)</label></div>
                <div class="checkbox"><label><input name="equipment_flipchart" type="checkbox"  <?=$equipment_flipchart?'checked="checked"':''?> > Flipchart</label></div>
                <div class="checkbox"><label><input name="equipment_sonstiges" type="checkbox"  <?=$equipment_sonstiges?'checked="checked"':''?>> Sonstiges: <input type="text" name="equipment_sonstiges_beschreibung" placeholder="Bitte spezifizieren" value="<?=$equipment_sonstiges_beschreibung?>" class="form-control" ></label></div>
            </div>
        </div>

        <?=form_row('Abstract <span class="glyphicon glyphicon-globe"></span>', [['abstract', $abstract, 'textarea', 'options' => [
            'cols' => '20',
            'rows' => '10'
        ]]])?>

        <?=form_row('Sonstige Anmerkungen und Wünsche', [['anmerkungen', $anmerkungen, 'textarea', 'options' => [
            'cols' => '20',
            'rows' => '10'
        ]]])?>

        <?=form_row('mögliche Zeiten', [['praefZeit', $praefZeit]])?>

        <?php if (Auth::hatRecht('ma-pt')): ?>
            <?=form_row('Programm <span class="glyphicon glyphicon-globe"></span>', [
                ['programm_raum', $programm_raum, 'cols' => 4, 'placeholder' => 'Raum'],
                ['programm_beginn', $programm_beginn, 'placeholder' => 'Zeitpunkt des Beginns (DD.MM.YYYY HH:MM:SS)'],
                ['programm_ende', $programm_ende, 'placeholder' => 'Endzeitpunkt (DD.MM.YYYY HH:MM:SS)'],
            ])?>

            <div class="row form-group">
                <div class="col-sm-2">
                    Sichten
                </div>
                <div class="col-sm-10">
                    <div class="checkbox"><label><input name="locked" type="checkbox" <?=$locked?'checked="checked"':''?>> <span class="glyphicon glyphicon-lock"></span> Schreibschutz (Referent*in kann dann nichts mehr ändern)</label></div>
                    <div class="checkbox"><label><input name="publish" type="checkbox" <?=$publish?'checked="checked"':''?>> <span class="glyphicon glyphicon-globe"></span> Veröffentlichen auf MA-Webseite, falls auch alle Referent*innen gesichtet wurden.</label></div>
                </div>
            </div>

            <div class="row form-group">
                <div class="col-sm-2">
                    Datenschutzbereinigung
                </div>
                <div class="col-sm-10">
                    <?php if ($datenschutz_bereinigt): ?>
                        <?php if ($datenschutz_bereinigung_termin): ?>
                            Durchgeführt am <?=$datenschutz_bereinigung_termin->format('d.m.Y')?>
                        <?php else: ?>
                            Durchgeführt
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($datenschutz_bereinigung_termin): ?>
                            Vorgemerkt für <?=$datenschutz_bereinigung_termin->format('d.m.Y')?>
                        <?php else: ?>
                            Nicht vorgemerkt
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
    <?php endif; ?>

    </div>

</div>

<p>Die mit dem Symbol <span class="glyphicon glyphicon-globe"></span> markierten Einträge werden ggf. veröffentlicht.</p>

<?php if (!$locked || Auth::hatRecht('ma-pt')): ?>
<div class="form-group row">
    <div class="col-sm-offset-2 col-sm-10">
        <button type="submit" class="btn btn-success" onclick="return checkForm();"><span class="glyphicon glyphicon-ok"></span> Speichern</button>
        <button type="reset" class="btn btn-default"><span class="glyphicon glyphicon-repeat"></span> Zurücksetzen</button>
        <a class="btn btn-default" href="vortragsliste.php"><span class="glyphicon glyphicon-remove"></span> Abbrechen</a>
        <?php if ($deleted): ?>
            <button type="submit" name="undelete" class="btn btn-danger">Löschen rückgängig machen</button>
        <?php else: ?>
            <button type="submit" name="delete" class="btn btn-danger" onclick="return checkDelete();"><span class="glyphicon glyphicon-trash"></span> Löschen</button>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>


</form>

<?php Tpl::footStart(); ?>

    <script>


// vor dem Verlassen warnen, falls etwas geändert wurde
changes = <?=$changes?'true':'false'?>;
$(document).on('change', 'input', function() {
    changes = true;
  });
$(window).bind('beforeunload', function(){
    if (changes) return 'Achtung! Ungespeicherte Änderungen gehen verloren. Fortsetzen?';
});

function checkForm() {
    // TODO: E-Mail und Passwort-Wiederholung prüfen
    changes = false;
    return true;
}

function checkDelete() {
    return confirm("Soll dieser Beitrag wirklich gelöscht werden?");
}

// Schreibschutz im Frontend. Im Backend natürlich wirksam geschützt!
function lockFrontend()
{
    $("input, textarea").not("[name='locked']").not("[name='publish']").not("[name='programm_raum']").not("[name='programm_beginn']").not("[name='programm_ende']").prop("readonly", true);
    $("input, select").not("[name='locked']").not("[name='publish']").not("[name='programm_raum']").not("[name='programm_beginn']").not("[name='programm_ende']").click(function () {return false;});
}

function unlockFrontend()
{
    $("input, select, textarea").not("[name='locked']").not("[name='publish']")
        .not("[name='vid']").not("[name='referent']")
        .not("[name='programm_raum']").not("[name='programm_beginn']").not("[name='programm_end']")
        .prop("readonly", false);
    $("input, select").not("[name='locked']").not("[name='publish']").not("[name='programm_raum']").not("[name='programm_beginn']").not("[name='programm_end']").unbind("click");
}

<?php if ($locked): ?>
    $(lockFrontend);
<?php endif; ?>

$("input[name=locked]").change(function() {
    if (this.checked) {
        lockFrontend();
        $("#publish").css("visibility", "visible");
    } else {
        unlockFrontend();
    }
});

    </script>


<?php Tpl::footEnd(); ?>
