<?php
namespace MHN\Referenten;
$vids = [];

//ToDo: Funktionen zentral bereitstellen, da Mehrfachverwendung!!
function form_row($label, $inputs) {
    $html = '';
    $for = null;
    $has_danger = '';

    foreach ($inputs as $vid=>$input) {
        $vid = $input[0];
        $value = $input[1];
        if (gettype($value) === 'object' && get_class($value) === 'DateTime') {
            $value = $value->format('d.m.Y H:i:s');
        }

        $select = (!empty($input[2]) and $input[2] == 'select');
        $textarea = (!empty($input[2]) and $input[2] == 'textarea');
        $type = !empty($input[2]) ? "type='$input[2]'" : '';
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

        if (!$for) $for = "input-$vid";
    }

    if (!$label) return $html;

    return "<div class='form-group row $has_danger'>
        <label for='$vid' class='col-sm-2 col-form-label'>$label</label>
        $html
    </div>\n";
}
?>
<form>

    <div class="form-group row">
        <?= form_row('', [
                ['q', $q],
            ['eid', $eid, 'select', 'options' => $events]

        ]) ?>
        <div class="col-sm-1">
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
                        <th>Beitragstitel</th>
                        <th>Referent*innen</th>
                        <th>Veranstaltung</th>
                        <th>Termin</th>
                    </tr>
                    <?php

                    $n = 0;
                    $graue = false;
                    foreach ($ergebnisse as $e) {
                        ++$n;
                        $vids[] = $e['vid'];
                        $class = '';
                        if ((time() - strtotime($e['beitragsjahr'])) > 6 * 31 * 24 * 3600) {
                            $class = "inaktiv";
                            $graue = true;
                        }
                        $e['programm_beginn'] = $e['programm_beginn'] ? $e['programm_beginn']->format('d.m. H:i') : '';

                        echo "<tr class='$class'><td>$n</td><td><a href='vortrag.php?vid=$e[vid]'>$e[vTitel] [" . Util::getBeitragsformAsText(($e['beitragsform'])) . "]</a>";

                        if ($e['locked']) {
                            echo ' <span class="glyphicon glyphicon-lock"></span>';
                        }
                        if ($e['publish']) {
                            echo ' <span class="glyphicon glyphicon-globe"></span>';
                        }

                        echo "</td><td>";

                        foreach ($e['referenten'] as $uid => $daten) {
                            echo "<a href='benutzer.php?uid=$uid'>$daten[name]</a>";
                            if ($daten['locked']) {
                                echo ' <span class="glyphicon glyphicon-lock"></span>';
                            }
                            if ($daten['publish']) {
                                echo ' <span class="glyphicon glyphicon-globe"></span>';
                            }
                            echo "<br>";
                        }
                        echo "</td>
                    <td>$e[veranstaltung]</td>
                    <td>$e[programm_beginn]</td>
                </tr>\n";
                    }
                    ?>

                </table>
            </div>

            <?php if ($graue): ?>
                <p>Mitglieder, die sich seit mehr als 6 Monaten nicht mehr eingeloggt haben, werden ausgegraut
                    dargestellt.</p>
            <?php endif; ?>

        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="form-group row">
    <div class="col-sm-2">
        <a href="/exportVortraege.php?var=<?=implode(',', $vids);?>" class="btn btn-success">
            <span class="glyphicon glyphicon-search"></span> Exportieren
        </a>
    </div>
</div>

<?php Tpl::footStart(); ?>

<?php Tpl::footEnd(); ?>
