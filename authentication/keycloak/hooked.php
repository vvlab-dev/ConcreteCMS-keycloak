<?php
defined('C5_EXECUTE') or die('Access denied.');

/**
 * @var string $name
 * @var bool $requireEmail
 * @var string $detachUrl empty string if detaching is disabled
 * @var vvLab\KeycloakAuth\UI $ui
 */

?>
<div class="form-group">
    <?= h(t("You've already attached your %s account", $name)) ?>
</div>
<?php
if ($detachUrl !== '') {
    ?>
    <div class="form-group">
        <a href="<?= h($detachUrl) ?>" class="btn <?= $ui->defaultButton ?>">
            <?= h(t('Detach %s account', $name)) ?>
        </a>
    </div>
    <?php
}
