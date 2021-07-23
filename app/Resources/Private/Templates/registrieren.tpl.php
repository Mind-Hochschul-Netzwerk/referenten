<?php
namespace MHN\Referenten;
?>
    <div id="loginModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="false">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
        <form method="post" name="registrierung">
      <div class="modal-header">
        <h4 class="modal-title" id="myModalLabel">Im MHN-Referententool registrieren</h4>
      </div>
      <div class="modal-body">
        <h4>Gib bitte folgende Daten ein, um dich zu registrieren:</h4>

<?php if (!empty($lost_password)) {
    Tpl::set('alert_type', 'success');
    Tpl::set('alert_text', "Falls ein entsprechendes Benutzerkonto gefunden wurde, wurde eine E-Mail mit einem neuen Passwort an deine E-Mail-Adresse gesendet. Das neue Passwort ist $passwordExpireTime_minutes Minuten lang gültig.");
    Tpl::render('Layout/alert');
} ?>
        <?php if (!empty($error_passwort_falsch)): ?>
            <div id="alertFalsch" class="alert alert-danger">Die Benutzerkennung oder das Passwort ist falsch.</div>
        <?php endif; ?>
        <?php if (!empty($info_neues_passwort)): ?>
            <div id="alertPasswortNeu" class="alert alert-success">Ein neues Passwort wurde an deine E-Mail-Adresse gesendet.</div>
        <?php endif; ?>
          <?php if (!empty($error_registriert)): ?>
              <div id="alertAccountAngelegt" class="alert alert-danger">Zu dieser E-Mail-Adresse existiert bereits ein Account. <a href="login.php">Zum Login</a></div>
          <?php endif; ?>


        <div id="alertBenutzerkennung" class="alert alert-danger <?=($error_username_leer)?'':'hide'?>">Bitte die E-Mail-Adresse angeben.</div>
        <div id="alertPasswort" class="alert alert-danger <?=($error_passwort_ungleich)?'':'hide'?>">Die Wiederholung stimmt nicht mit dem Passwort überein.</div>
        <div id="alertPasswortEmpty" class="alert alert-danger hide">Bitte gib ein Passwort ein.</div>

          <div id="alertInformationspflicht" class="alert alert-danger hide">Um fortfahren zu können musst du die
              Kenntnisnahme der Verarbeitung personenbezogener Daten bestätigen.
          </div>
          <div id="alertEinwilligungserklaerung" class="alert alert-danger hide">Um fortzufahren, benötigt es der
              Zustimmung zur Verarbeitung personenbezogener Zusatzdaten.
          </div>

          <div class="row form-group">
            <div class="col-sm-12">
                <input id="id" name="id" type="email" placeholder="E-Mail-Adresse" class="form-control">
            </div>
        </div>
        <div class="row form-group">
            <div class="col-sm-12">
                <input id="password" name="password" type="password" placeholder="Passwort" class="form-control" />
            </div>
        </div>
          <div class="row form-group">
              <div class="col-sm-12">
                  <input id="password2" name="password2" type="password" placeholder="Passwort wiederholen" class="form-control" />
              </div>
          </div>
          <div class="row form-group">
              <div class="col-sm-12">
                  <label><input type="checkbox" id="informationspflicht" value="informationspflicht"> Ja, ich nehme zur
                  Kenntnis, dass meine personenbezogenen Daten wie <a href="#Informationspflicht">unten stehend</a>
                  verarbeitet und gespeichert werden.</label>
              </div>
          </div>
          <div class="row form-group">
              <div class="col-sm-12">
                  <label><input type="checkbox" id="einwilligungserklaerung" value="einwilligungserklaerung"> Ja, ich willige
                  ein, dass meine personenbezogenen Zusatzdaten wie <a href="#Einwilligungserklaerung">unten stehend</a>
                  verarbeitet und gespeichert werden dürfen.</label>
              </div>
          </div>


          <button type="submit" onclick="return check();" class="hidden-default-button"></button>

      </div>
      <div class="modal-footer">
        <a href="registrieren.php" class="btn btn-default" role="button">Abbrechen</a>
        <button type="submit" onclick="return check();" class="btn btn-primary">Registrieren</button>
      </div>
</form>

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
    </div>

<div id="loginModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Informationen zum Datenschutz</h4>
            </div>
            <div class="modal-body">

                <div class="row form-group">
                    <div class="col-sm-12">
                        <?php Tpl::render('Datenschutz/kenntnisnahme-text'); ?>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-sm-12">
                        <?php Tpl::render('Datenschutz/einwilligung-text'); ?>
                    </div>
                </div>

            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

<?php Tpl::footStart(); ?>

    <script>

        function check() {
            var un = check_username();
            var pw = check_passwort();
            var dsgvo = check_checkboxen();

            var result = un && pw && dsgvo;

            return result;
        }

        function check_username() {
            $("#alertPasswortNeu").addClass("hide");
            $("#alertFalsch").addClass("hide");
            if ($("#id").val() != "") {
                $("#alertBenutzerkennung").addClass("hide");
                return true;
            } else {
                $("#alertBenutzerkennung").removeClass("hide");
                return false;
            }
        }

        function check_passwort() {
            //ToDo: JavaScript-Überprüfung der Eingaben vervollständigen
            if ($("#password").val() == '' && $("#password2").val() == '') {
                $("#alertPasswortEmpty").removeClass("hide");
                return false;
            } else {
                $("#alertPasswortEmpty").addClass("hide");
            }

            if ($("#password").val() == $("#password2").val()) {
                $("#alertPasswort").addClass("hide");
                return true;
            } else {
                $("#alertPasswort").removeClass("hide");
                return false;
            }
        }

        function check_checkboxen() {
            $("#alertInformationspflicht").addClass("hide");
            $("#alertEinwilligungserklaerung").addClass("hide");

            if(!document.getElementById("informationspflicht").checked) {
                $("#alertInformationspflicht").removeClass("hide");
                document.getElementById("informationspflicht").focus();
                return false;

            } else if(!document.getElementById("einwilligungserklaerung").checked) {
                $("#alertEinwilligungserklaerung").removeClass("hide");
                document.getElementById("einwilligungserklaerung").focus();
                return false;
            }

            return true;

        }
    </script>

<?php Tpl::footEnd(); ?>
