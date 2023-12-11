<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Form\Service\Form $form
 * @var Concrete\Core\Html\Service\Html $html
 * @var Concrete\Core\Application\Service\UserInterface $interface
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var Concrete\Core\Page\View\PageView $view
 * @var Concrete\Package\KeycloakAuth\Controller\SinglePage\Dashboard\System\Registration\Authentication\KeycloakMappings $controller
 * @var string $authenticationTypesPageUrl
 * @var Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface $urlResolver
 * @var vvLab\KeycloakAuth\Entity\Server[]|null $servers
 */

if ($servers === null) {
    ?>
    <div class="alert alert-info">
        <?= t('Servers are handled in another way') ?>
    </div>
    <?php
    return;
}
if ($servers === []) {
    ?>
    <div class="alert alert-info">
        <?= t('No server has been defined yet.') ?><br />
        <?= t('You can define the servers <a href="%s">in the authentication types page</a>.', h($authenticationTypesPageUrl)) ?>
    </div>
    <?php
    return;
}
?>

<table class="table table-hover">
    <thead>
        <tr>
            <th><?= t('Root URL') ?></th>
            <th><?= t('Client ID') ?></th>
            <th><?= t('Email Criteria') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($servers as $server) {
            $url = (string) $urlResolver->resolve(["/dashboard/system/registration/authentication/keycloak_mappings/edit/{$server->getID()}"]);
            ?>
            <tr style="cursor: pointer" onclick="<?= h('window.location.href = ' . json_encode($url)) ?>">
                <td><?= h($server->getRealmRootUrl()) ?></td>
                <td><?= h($server->getClientID()) ?></td>
                <td><?= nl2br(h(implode("\n", $server->getEmailRegexes()))) ?></td>
            </tr>
            <?php
        }
        ?>
    </tbody>
</table>
