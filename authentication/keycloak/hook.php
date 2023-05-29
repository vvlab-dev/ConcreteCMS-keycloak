<?php

defined('C5_EXECUTE') or die('Access denied.');

/**
 * @var string $name
 * @var bool $requireEmail
 * @var string $attachUrl
 * @var string $userEmail
 */

if ($attachUrl === '') {
    return;
}
?>
<div class="form-group">
    <span>
        <?= t('Attach your %s account', h($name)) ?>
    </span>
    <hr />
</div>
<?php
if ($requireEmail) {
    ?>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" for="keycloak-email">
            <?= t('Email Address') ?>
        </label>
        <div class="col-sm-9">
            <input type="email" id="keycloak-email" class="form-control" value="<?= h($userEmail) ?>" />
        </div>
    </div>
    <div class="mb-3 row">
        <div class="col-sm-12 text-end">
            <button type="button" class="btn btn-success" id="keycloak-attach" disabled="disabled"><?= h(t('Attach your %s account', $name)) ?></button>
        </div>
    </div>
    <script>
    window.addEventListener('DOMContentLoaded', function() {
        var UI = {
            email: document.getElementById('keycloak-email'),
            save: document.getElementById('keycloak-attach'),
        };
        UI.save.disabled = false;
        UI.save.addEventListener('click', function() {
            var email = UI.email.value.replace(/^\s+|\s+$/g, '');
            if (email === '') {
                UI.email.focus();
                return;
            }
            UI.email.readOnly = true;
            UI.save.disabled = true;
            var form = document.createElement('form');
            form.style.display = 'none';
            form.setAttribute('method', 'POST');
            form.setAttribute('action', <?= json_encode($attachUrl) ?>);
            var input;
            var input = document.createElement("input");
            input.setAttribute('type', 'text');
            input.setAttribute('name', 'email');
            input.setAttribute('value', email);
            form.appendChild(input);
            document.getElementsByTagName('body')[0].appendChild(form);
            form.submit();
        });
    });
    </script>
    <?php
} else {
    ?>
    <div class="form-group">
        <a href="<?= h($attachUrl) ?>" class="btn btn-primary btn-success">
            <?= h(t('Attach your %s account', $name)) ?>
        </a>
    </div>
    <?php
}
