<?php declare(strict_types=1); namespace MHN\Referenten; ?>
<div <?=empty($alert_id) ? '' : "id=$alert_id"?> class="alert alert-<?=$alert_type?> alert-dismissible fade in <?=!empty($alert_hide) ? 'hide' : ''?>" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button        >
    <?=$alert_type === 'danger' ? '<strong>Fehler:</strong> ' : ''?> <?=$alert_text?>
</div>
<?php Tpl::set('alert_id', ''); ?>
<?php Tpl::set('alert_hide', false); ?>
