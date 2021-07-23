<?php
namespace MHN\Referenten;

Tpl::set('title', "Benutzerdaten <small><a class='btn btn-default' style='margin-top: -12px;' href='vortragsliste.php?uid=$uid'>Beiträge anzeigen</a></small>", false);

function form_row($label, $inputs) {
    $html = '';
    $for = null;
    $has_danger = '';

    foreach ($inputs as $uid=>$input) {
        $uid = $input[0];
        $value = $input[1];
        if (gettype($value) === 'object' && get_class($value) === 'DateTime') {
            $value = $value->format('d.m.Y H:i:s');
        }
        $select = (!empty($input[2]) && $input[2] === 'select');
        $textarea = (!empty($input[2]) && $input[2] === 'textarea');
        $type = !empty($input[2]) ? "type='$input[2]'" : '';
        $autocompleteOff = (!empty($input[2]) && ($input[2] === 'email' || $input[2] === 'password')) ? 'autocomplete="new-password"' : '';

        $cols = !empty($input[3]) ? $input[3] : floor(10/count($inputs));

        $class = !empty($input['class']) ? $input['class'] : 'form-control';

        if (!empty($input['maxlength'])) {
            $maxlength = $input['maxlength'];
        } else {
            $maxlength = (!empty($input[2]) && $input[2] == 'textarea') ? 1000 : 255;
        }

        if (!empty($input['error'])) {
            $class .= ' form-control-danger';
            $has_danger = 'has-danger';
        }

        if (empty($input['disabled'])) {
            $name = "name='$uid'";
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
            $tag = "<select id='input-$uid' $name class='$class' $disabled title='$title'>\n";
            foreach ($input['options'] as $key=>$text) {
                $tag .= "<option value='$key' ".($value==$key?'selected="selected"':'').">$text</option>\n";
            }
            $tag .= "</select>\n";
        } elseif ($textarea)  {
            $textCols = $input['options']['cols'];
            $textRows = $input['options']['rows'];

            $tag = "<textarea cols='$textCols' rows='$textRows'  id='input-$uid' $name $type class='$class' $disabled placeholder='$placeholder' title='$title' maxlength='$maxlength'>$value</textarea>";
        } else  {
            $tag = "<input id='input-$uid' $name value='$value' $type $autocompleteOff class='$class' $disabled placeholder='$placeholder' title='$title' maxlength='$maxlength'>\n";
        }

        if (!empty($input['sichtbarkeit'])) {
            $name = $input['sichtbarkeit'][0];
            $checked = $input['sichtbarkeit'][1] ? 'checked="checked"' : '';
            $tag = "<div class='input-group' data-tooltip='sichtbarkeit'>$tag
                <span class='input-group-addon'><input class='input-group-addon' name='$name' data-height='32' data-width='50' data-toggle='toggle' data-onstyle='success' data-offstyle='danger' data-on='&lt;span class=&quot;glyphicon glyphicon-eye-open&quot;&gt;&lt;/span&gt;' data-off='&lt;span class=&quot;glyphicon glyphicon-eye-close&quot;&gt;&lt;/span&gt;' type='checkbox' $checked></span>
                    </div>";
        }

        $html .= "<div class='col-sm-$cols'>$tag</div>";

        if (!$for) $for = "input-$uid";
    }

    if (!$label) return $html;

    return "<div class='form-group row $has_danger'>
        <label for='$uid' class='col-sm-2 col-form-label'>$label</label>
        $html
    </div>\n";
}

$disableMitgliederverwaltung = !Auth::hatRecht('ma-pt');
$active_pane = 'profile';

$changes = $error = $password_error = false;

// Alerts generieren

if (!empty($profilbild_format_unbekannt)) {
    Tpl::set('alert_type', 'danger');
    Tpl::set('alert_text', 'Das Dateiformat des Profilbilds wurde nicht erkannt oder wird nicht unterstützt. Unterstützte Formate: JPEG, PNG.');
    Tpl::render('Layout/alert');
    $active_pane = 'profilbild';
    $changes = $error = true;
}

if (!empty($email_error)) {
    Tpl::set('alert_type', 'danger');
    Tpl::set('alert_text', 'Die eigegebene E-Mail-Adresse ist ungültig. Die E-Mail-Adresse wurde nicht gespeichert.');
    Tpl::render('Layout/alert');
    $changes = $error = true;
} else {
    $email_error = false;
}

if (!empty($new_password_error)) {
    Tpl::set('alert_type', 'danger');
    Tpl::set('alert_text', 'Das eingegebene neue Passwort ist leer. Das Passwort wurde nicht geändert.');
    Tpl::render('Layout/alert');
    $active_pane = 'password';
    $password_error = $changes = $error = true;
}

if (!empty($new_password2_error)) {
    Tpl::set('alert_type', 'danger');
    Tpl::set('alert_text', 'Die Wiederholung stimmt nicht mit dem neuen Passwort überein. Das Passwort wurde nicht geändert.');
    Tpl::render('Layout/alert');
    $active_pane = 'password';
    $password_error = $changes = $error = true;
}

if (!empty($old_password_error)) {
    Tpl::set('alert_type', 'danger');
    Tpl::set('alert_text', 'Das alte Passwort ist falsch. Das Passwort wurde nicht geändert.');
    Tpl::render('Layout/alert');
    $active_pane = 'password';
    $password_error = $changes = $error = true;
}

if (!empty($data_saved_info)) {
    Tpl::set('alert_type', 'success');
    Tpl::set('alert_text', (!$error ? 'Deine Daten wurden geändert.' : 'Die anderen Änderungen wurden gespeichert.') . (!empty($email_auth_info) ? ' Es wurde eine E-Mail zur Bestätigung an deine neue E-Mail-Adresse versendet.' : ''));
    Tpl::render('Layout/alert');
}

if ($locked) {
    Tpl::set('alert_type', 'warning');
    Tpl::set('alert_text', 'Ihre Daten wurden bereits gesichtet. Einige Daten sind daher nicht mehr änderbar. Wenn noch etwas geändert werden soll, wenden Sie sich bitte an das Programmteam.');
    Tpl::render('Layout/alert');
}


// E-Mail-Verifaktionsprozess noch nicht abgeschlossen?
if ($new_email_token and empty($email_auth_info)) {
    if ($new_email) {
        $minutes_left = (strtotime($new_email_token_expire_time) - time()) / 60;
        $time_left = $minutes_left > 60 ? (round($minutes_left / 60) . ' Stunden') : (round($minutes_left) . ' Minuten');
        Tpl::set('alert_type', 'warning');
        Tpl::set('alert_text', "Deine neue E-Mail-Adresse wurde noch nicht bestätigt. Bitte klicke innerhalb der nächsten $time_left auf den Link in der Bestätigungsmail. <a href='email_auth.php?new_token'>Bestätigungsmail erneut zusenden.</a>", false);
        Tpl::render('Layout/alert');
    } else {
        Tpl::set('alert_type', 'danger');
        Tpl::set('alert_text', 'Du hast die Änderung deiner E-Mail-Adresse nicht rechtzeitig bestätigt. Die E-Mail-Adresse wurde zurückgesetzt.');
        Tpl::render('Layout/alert');
        $email_error = true;
    }
}

?>

<ul class="nav nav-tabs">
    <li <?=$active_pane=='profile'?'class="active"':''?> ><a data-toggle="tab" href="#profile">Profil</a></li>
    <li <?=$active_pane=='profilbild'?'class="active"':''?> ><a data-toggle="tab" href="#profilbild">Profilbild</a></li>
    <?php if (Auth::hatRecht('ma-pt')): ?>
        <li <?= $active_pane == 'ma-pt' ? 'class="active"' : '' ?> ><a data-toggle="tab" href="#ma-pt">Programmteam</a>
        </li>
    <?php endif; ?>
    <li <?=$active_pane=='password'?'class="active"':''?> ><a data-toggle="tab" href="#password">Passwort ändern</a></li>
</ul>

<form enctype="multipart/form-data" method="post">

<input type="hidden" name="uid" value="<?=$uid?>">

<div class="tab-content">
    <div class="tab-pane <?=$active_pane=='profile'?'active':''?>" id="profile">
        <h3>Mein Profil</h3>

        <?=form_row('Titel <span class="glyphicon glyphicon-globe"></span>, Vor- <span class="glyphicon glyphicon-globe"></span> u. Nachname <span class="glyphicon glyphicon-globe"></span>', [
            ['titel', $titel, 'text', 2, 'placeholder'=>'Titel'],
            ['vorname', $vorname, 'text', 4, 'placeholder'=>'Vorname'],
            ['nachname', $nachname, 'text', 4, 'placeholder'=>'Nachname']
        ])?>
        <?= form_row('Geschlecht + ggf. Mensa-Mitgliedsnr. (optional)', [
            ['geschlecht', $geschlecht, 'select', 'options' => [
                'm' => 'männlich',
                'w' => 'weiblich',
                'u' => 'unbekannt',
                'd' => 'divers'
            ]
            , 'placeholder' => 'Geschlecht'],
            ['mensa_nr', $mensa_nr, 'placeholder' => 'Mensa-Mitgliedsnummer']
        ])?>
        <?=
        form_row('MHN-Mitglied:', [
            ['mhn_mitglied', $mhn_mitglied, 'select', 'options' => [
                true => 'Ja',
                false => 'Nein'
            ], 'placeholder' => 'Mitglied']
        ]) ?>

        <?=form_row('E-Mail', [['email', $email, 'email','disabled' => $disableMitgliederverwaltung, 'error' => $email_error]])?>
        <?= form_row('Telefon (optional)', [['telefon', $telefon, 'tel']]) ?>
        <?= form_row('Mobil (optional)', [['mobil', $mobil, 'tel']]) ?>

        <?= form_row('Zugehörigkeit (Affiliation) (optional) <span class="glyphicon glyphicon-globe"></span>', [['affiliation', $affiliation]]) ?>

        <div class="row form-group">
            <div class="col-sm-2">
                Aufnahmen (optional)
            </div>
            <div class="col-sm-10">
                <div class="checkbox"><label><input name="aufnahmen" type="checkbox" <?=$aufnahmen?'checked':''?>> Ich bin damit einverstanden, dass von meinem Vortrag eine Bild-Ton-Aufnahme angefertig wird. Die Aufnahmen werden nur Teilnehmer:innen der Mind-Akademie zugänglich gemacht. Diese Zustimmung kann jederzeit widerrufen werden. </label></div>
            </div>
        </div>

        <?=form_row('Kurzvita <span class="glyphicon glyphicon-globe"></span>', [['kurzvita', $kurzvita, 'textarea', 'options' => [
            'cols' => '20',
            'rows' => '10'
        ]]])?>
    </div>

    <div class="tab-pane <?=$active_pane=='profilbild'?'active':''?>" id="profilbild">
        <h3>Profilbild</h3>

        <div class="form-group row">
            <label for="aktuellesBild" class="col-sm-2 col-form-label">Profilbild</label>
            <div class="col-sm-10 text-center">
                <img id="aktuellesBild" src="<?=$profilbild?('profilbilder/'.$profilbild):('img/profilbild-default.png')?>" />
            </div>
        </div>

        <div class="form-group row">
            <label for="profilbild" class="col-sm-2 col-form-label">Bild ändern (max. 20 MB)</label>
            <div class="col-sm-10">

                <div class="input-group">
                    <label class="input-group-btn">
                        <span class="btn btn-primary">
                            Datei auswählen &hellip; <input name="profilbild" type="file" style="display: none;">
                        </span>
                    </label>
                    <input type="text" class="form-control" readonly="readonly">
                </div>
            </div>
        </div>

        <?php if ($profilbild): ?>
            <div class="form-group row">
                <label for="bildLoeschen" class="col-sm-2 col-form-label">Bild löschen</label>
                <div class="col-sm-10"><label><input type="checkbox" name="bildLoeschen"> Bild löschen</label></div>
            </div>
        <?php endif; ?>

    </div>

    <?php if (Auth::hatRecht('ma-pt')): ?>
    <div class="tab-pane <?=$active_pane=='ma-pt'?'active':''?>" id="ma-pt">
        <h3>Programmteam</h3>

        <?=form_row('Benutzerkennung <span class="glyphicon glyphicon-globe"></span>', [['uid', $uid, 'disabled' => true, 'title' => 'Die Benutzerkennung ist nicht änderbar.']])?>

        <?= form_row('Registrierungsdatum (leeren=unbekannt)', [['registrierungsdatum', $registrierungsdatum ? $registrierungsdatum->format('Y-m-d') : '', 'date']]) ?>
            <?php if (Auth::hatRecht('rechte')): ?>
                <?= form_row('Gruppen / Rechte', [['rechte', $rechte, 'placeholder' => 'Trennen durch Komma. Mögliche Werte siehe Menüpunkt „Programmteam”']]) ?>
            <?php endif; ?>

        <div class="row">
            <div class="col-sm-2">Informationspflicht bei Erhebung von personenbezogenen Daten</div>
            <div class="col-sm-10"><?= ($kenntnisnahme_informationspflicht_persbez_daten === null) ? 'nein' : ('zur Kenntnis genommen am ' . $kenntnisnahme_informationspflicht_persbez_daten->format('d.m.Y, H:i:s') . ' Uhr.') ?></div>
        </div>
        <div class="row">
            <div class="col-sm-2">Informationspflicht bei Erhebung von personenbezogenen Daten: Text</div>
            <div class="col-sm-10"><textarea disabled class="small"
                                             style="width:100%;"><?= $kenntnisnahme_informationspflicht_persbez_daten_text ?></textarea>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-2">Einwilligungserklärung für personenbezogene Zusatzdaten</div>
            <div class="col-sm-10"><?= ($einwilligung_persbez_zusatzdaten === null) ? 'nein' : ('zur Kenntnis genommen am ' . $einwilligung_persbez_zusatzdaten->format('d.m.Y, H:i:s') . ' Uhr.') ?></div>
        </div>
        <div class="row">
            <div class="col-sm-2">Einwilligungserklärung für personenbezogene Zusatzdaten: Text</div>
            <div class="col-sm-10"><textarea disabled class="small"
                                             style="width:100%;"><?= $einwilligung_persbez_zusatzdaten_text ?></textarea>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Benutzer löschen</label>
            <div class="col-sm-10">
                <label>
                    <input name="deleted" type="checkbox" id="deleted"
                    <?php if ($deleted) echo 'checked="checked"'; ?>
                    onclick="return confirm(&quot;Bist du ganz sicher?&quot; + (!$(&quot;#deleted&quot;).get(0).checked ? &quot;&quot; : &quot; Daten werden unwiederbringlich gelöscht!&quot;));">
                </label>
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
                        Vorgemerkt für den <?=$datenschutz_bereinigung_termin->format('d.m.Y')?>
                    <?php else: ?>
                        Nicht vorgemerkt
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="row form-group">
            <div class="col-sm-2">
                Sichten
            </div>
            <div class="col-sm-10">
                <div class="checkbox"><label><input name="locked" type="checkbox" <?=$locked?'checked="checked"':''?>> <span class="glyphicon glyphicon-lock"></span> Schreibschutz (Referent/in kann dann nichts mehr ändern)</label></div>
                <div class="checkbox"><label><input name="publish" type="checkbox" <?=$publish?'checked="checked"':''?>> <span class="glyphicon glyphicon-globe"></span>  Veröffentlichen auf MA-Webseite</label></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="tab-pane <?= $active_pane == 'password' ? 'active' : '' ?>" id="password">
        <h3>Passwort ändern</h3>
        <?=form_row('Neues Passwort', [['new_password', '', 'password', 'error' => $password_error]])?>
        <?=form_row('Passwort wiederholen', [['new_password2', '', 'password', 'error' => $password_error]])?>
        <?php if(!Auth::hatRecht('ma-pt') or Auth::ist($uid)): ?>
            <?=form_row('Altes Passwort', [['password', '', 'password', 'error' => $password_error]])?>
        <?php endif; ?>
    </div>
</div>

<p>Die mit dem Symbol <span class="glyphicon glyphicon-globe"></span> markierten Einträge werden ggf. veröffentlicht.</p>

<div class="form-group row">
    <div class="col-sm-offset-2 col-sm-10">
        <button type="submit" class="btn btn-success" onclick="return checkForm();">Speichern</button>
        <button type="reset" class="btn btn-default">Zurücksetzen</button>
    </div>
</div>


</form>

<?php Tpl::footStart(); ?>

    <script>


$(function() {
  // We can attach the `fileselect` event to all file inputs on the page
  $(document).on('change', ':file', function() {
    var input = $(this),
        label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
    input.trigger('fileselect', label);
  });

  // We can watch for our custom `fileselect` event like this
  $(document).ready( function() {
      $(':file').on('fileselect', function(event, label) {

          var input = $(this).parents('.input-group').find(':text');

          if( input.length ) {
              input.val(label);
          }
      });
  });
});

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

// Schreibschutz im Frontend. Im Backend natürlich wirksam geschützt!
function lockFrontend()
{
    $("input, textarea").not("[name='locked']").not("[name='publish']")
        .not("[name='mensa_nr']").not("[name='telefon']").not("[name='mobil']").not("[name='email']").not("[name='aufnahmen']")
        .not("[name='new_password']").not("[name='new_password2']").not("[name='password']")
        .prop("readonly", true);
    $("input, select").not("[name='locked']").not("[name='publish']").not("[name='aufnahmen']").click(function () {return false;});
}

function unlockFrontend()
{
    $("input, select, textarea").not("[name='locked']").not("[name='publish']")
        .not("[name='mensa_nr']").not("[name='telefon']").not("[name='mobil']").not("[name='email']").not("[name='aufnahmen']")
        .not("[name='new_password']").not("[name='new_password2']").not("[name='password']")
        .prop("readonly", false);
    $("input, select").not("[name='locked']").not("[name='publish']").not("[name='aufnahmen']").unbind("click");
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
