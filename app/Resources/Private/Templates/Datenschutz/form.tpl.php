<?php declare(strict_types=1);
namespace MHN\Referenten; ?>


<p>Die nachfolgende Kenntnisnahme und die Einwilligung zur Datenverarbeitung und und -speicherung sind erforderlich, um
    zukünftig mit dem Referententool arbeiten zu können.</p>

<?php if ($kenntnisnahme_informationspflicht_persbez_daten === null || $einwilligung_persbez_zusatzdaten === null): ?>
<form method="post">
    <?php endif; ?>

    <div style="margin: 0em; padding: 1em 1em 1em 1em; background-color: #eef; border-radius: 1em;">

        <?php Tpl::render('Datenschutz/kenntnisnahme-text'); ?>

    </div>

    <?php if ($kenntnisnahme_informationspflicht_persbez_daten === null): ?>
        <div class='form-group row '>
            <label for="kenntnisnahme_informationspflicht_persbez_daten" class='col-sm-12 col-form-label'>
                <input id="kenntnisnahme_informationspflicht_persbez_daten"
                       name="kenntnisnahme_informationspflicht_persbez_daten" value="1" type="checkbox"> Ja, ich nehme
                zur Kenntnis, dass meine personenbezogenen Daten wie obenstehend verarbeitet und gespeichert werden.
            </label>
        </div>
    <?php else: ?>
        <p>Du hast am <?= $kenntnisnahme_informationspflicht_persbez_daten->format('d.m.Y') ?> zur Kenntnis genommen,
            dass deine personenbezogenen Daten wie obenstehend verarbeitet und gespeichert werden.</p>
    <?php endif; ?>

    <div style="margin: 0em; padding: 1em 1em 1em 1em; background-color: #eef; border-radius: 1em;">

        <?php Tpl::render('Datenschutz/einwilligung-text'); ?>

    </div>

    <?php if ($einwilligung_persbez_zusatzdaten === null): ?>
        <div class='form-group row '>
            <label for="einwilligung_persbez_zusatzdaten" class='col-sm-12 col-form-label'>
                <input id="einwilligung_persbez_zusatzdaten" name="einwilligung_persbez_zusatzdaten" value="1"
                       type="checkbox"> Ja, ich willige ein, dass meine personenbezogenen Daten wie obenstehend
                verarbeitet und gespeichert werden dürfen.
            </label>
        </div>
    <?php else: ?>
        <p>Du hast am <?= $einwilligung_persbez_zusatzdaten->format('d.m.Y') ?> eingewilligt, dass deine
            personenbezogenen Zusatzdaten wie obenstehend verarbeitet und gespeichert werden.</p>
    <?php endif; ?>


    <?php if ($kenntnisnahme_informationspflicht_persbez_daten === null || $einwilligung_persbez_zusatzdaten === null): ?>
    <div class="form-group row">
        <div class="col-sm-12">
            <button type="submit" name="submit" class="btn btn-success">Speichern</button>
        </div>
    </div>
</form>
<?php endif; ?>

