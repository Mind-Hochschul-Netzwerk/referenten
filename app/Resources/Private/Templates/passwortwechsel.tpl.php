<?php
    use \MHN\Referenten\Tpl;
?>
<form method="post">

<?php 
    Tpl::set('alert_id', 'AlertWiederholungFalsch');
    Tpl::set('alert_type', 'danger');
    Tpl::set('alert_hide', empty($wiederholung_falsch));
    Tpl::set('alert_text', 'Die Wiederholung stimmt nicht mit dem Passwort überein.');
    Tpl::render('Layout/alert');
 ?>            
            
<p>Bitte wähle dein neues Passwort.</p>

<!-- TODO: Passwortstärkeindikator -->

<div class='form-group row '>
    <label for='input-password' class='col-sm-2 col-form-label'>Neues Passwort</label>
    <div class='col-sm-10'>
        <input id='input-password' name='password' type='password' class='form-control' placeholder='neues Passwort' title='neues Passwort'>
    </div>
</div>

<div class='form-group row '>
    <label for='input-password' class='col-sm-2 col-form-label'>Passwort wiederholen</label>
    <div class='col-sm-10'>
        <input id='input-password2' name='password2' type='password' class='form-control' placeholder='neues Passwort' title='neues Passwort'>
    </div>
</div>

<div class="form-group row">
    <div class="col-sm-offset-2 col-sm-10">
        <button type="submit" class="btn btn-success">Speichern</button>
        <button type="reset" class="btn btn-default">Zurücksetzen</button>
    </div>
</div>

</form>

<!-- TODO Eingaben vor dem Senden in Javascript prüfen und ggf #alertWiederholungFalsch einblenden -->
