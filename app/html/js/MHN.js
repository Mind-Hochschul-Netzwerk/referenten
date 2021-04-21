$(function() {
    $('[data-tooltip="sichtbarkeit"] .toggle-on').prop("title", "sichtbar für andere Mitglieder");
    $('[data-tooltip="sichtbarkeit"] .toggle-off').prop("title", "nicht sichtbar");

    updateZeichenlimit = function () {
        length = $(this).val().length;
        $('#' + this.id + ' + span').text($(this).attr('maxlength')-length);
    };
    $('textarea[maxlength]')
        .after($('<span></span>'))
        .each(updateZeichenlimit)
        .keyup(updateZeichenlimit);

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

            if(input.length) {
                input.val(label);
            }
        });
    });
});
