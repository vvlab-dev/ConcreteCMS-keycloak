<?php

defined('C5_EXECUTE') or die('Access denied.');

/**
 * @var string|null $error may be not set
 * @var string|null $message may be not set
 * @var string $name
 * @var bool $requireEmail
 * @var string $authUrl
 * @var Concrete\Core\User\User $user
 * @var vvLab\KeycloakAuth\UI $ui
 * @var Concrete\Core\Form\Service\Form $form
 */

if (!empty($error)) {
    ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <?php
}
if (!empty($message)) {
    ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php
}
if ($user->isRegistered()) {
    return;
}

if ($ui->majorVersion < 9) {
    ?>
    <div class="form-group">
        <span><?= t('Sign in with your %s account', h($name)) ?></span>
        <hr class="ccm-authentication-type-external-concrete5" />
    </div>
    <?php
}

if ($requireEmail) {
    ?>
    <form class="concrete-login-form" method="POST" action="<?= h($authUrl) ?>">
        <?php
        if ($ui->majorVersion >= 9) {
            ?>
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="keycloak-email">
                    <?= t('Email Address') ?>
                </label>
                <div class="col-sm-9">
                    <input type="email" name="email" id="keycloak-email" class="form-control" required="required" />
                </div>
            </div>
            <div class="mb-3 row">
                <div class="col-sm-12 text-end">
                    <input type="submit" class="btn btn-success btn-login" value="<?= h(t('Log in with %s', h($name))) ?>" />
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="form-group">
                <label class="control-label" for="keycloak-email"><?= t('Email Address') ?></label>
                <input type="email" name="email" id="keycloak-email" class="form-control" required="required" />
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="<?= h(t('Log in with %s', h($name))) ?>" />
            </div>
            <?php
        }
        ?>
    </form>
    <?php
} else {
    if ($ui->majorVersion >= 9) {
        ?>
        <div class="form-group">
            <div class="d-grid">
                <a href="<?= h($authUrl) ?>" class="btn btn-success btn-login">
                    <?= t('Log in with %s', h($name)) ?>
                </a>
            </div>
        </div>
        <?php
    } else {
        ?>
        <div class="form-group">
            <a href="<?= h($authUrl) ?>" class="btn btn-success btn-login btn-block">
                <?= t('Log in with %s', h($name)) ?>
            </a>
        </div>
        <?php
    }
}
