<?php

namespace Concrete\Package\KeycloakAuth\Controller\SinglePage\Dashboard\System\Registration\Authentication\KeycloakMappings;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Form\Service\Widget\GroupSelector;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Punic\Comparer;
use vvLab\KeycloakAuth\Claim\Map;
use vvLab\KeycloakAuth\Claim\Map\Attribute\Factory;
use vvLab\KeycloakAuth\Claim\Map\Field;
use vvLab\KeycloakAuth\Claim\Standard;
use vvLab\KeycloakAuth\Entity\Server;
use vvLab\KeycloakAuth\ServerConfigurationProvider;
use vvLab\KeycloakAuth\UI;

defined('C5_EXECUTE') or die('Access Denied.');

class Edit extends DashboardPageController
{
    /**
     * @param int|string|mixed $serverID
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function view($serverID = '')
    {
        $server = null;
        if ($this->app->make(ServerConfigurationProvider::class) instanceof ServerConfigurationProvider\ServerProvider) {
            $serverID = is_numeric($serverID) ? (int) $serverID : 0;
            if ($serverID > 0) {
                $em = $this->app->make(EntityManagerInterface::class);
                $server = $em->find(Server::class, $serverID);
            }
        }
        if ($server === null) {
            return $this->buildRedirect('/dashboard/system/registration/authentication/keycloak_mappings');
        }
        $this->requireAsset('javascript', 'vue');
        $this->set('ui', $this->app->make(UI::class));
        $this->set('groupSelector', $this->app->make(GroupSelector::class));
        $this->set('server', $server);
        $urlResolver = $this->app->make(ResolverManagerInterface::class);
        if (count($em->getRepository(Server::class)->findBy([], [], 2)) > 1) {
            $backTo = $urlResolver->resolve(['/dashboard/system/registration/authentication/keycloak_mappings']);
        } else {
            $backTo = $urlResolver->resolve(['/dashboard/system/registration/authentication']);
        }
        $this->set('backTo', (string) $backTo);
        $this->set('standardClaimsDictionary', Standard::getDictionary());
        $this->set('fieldDictionary', Field::getDictionary());
        $this->set('attributeDictionary', $this->getAttriutesDictionary());
        $this->set('usedFields', Field::getUsedFields());

        return null;
    }

    /**
     * @param int|string|mixed $serverID
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function claimsLogOperation($serverID = '')
    {
        if (!$this->app->make(ServerConfigurationProvider::class) instanceof ServerConfigurationProvider\ServerProvider) {
            throw new UserMessageException(t('Servers are handled in another way'));
        }
        if (!$this->token->validate("kc-mappings-logop{$serverID}")) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $serverID = is_numeric($serverID) ? (int) $serverID : 0;
        if ($serverID > 0) {
            $em = $this->app->make(EntityManagerInterface::class);
            $server = $em->find(Server::class, $serverID);
        }
        if ($server === null) {
            throw new UserMessageException(t('Unable to find the requested server.'));
        }
        switch ($this->request->request->get('operation')) {
            case 'enable':
                $server->setLogNextReceivedClaims(true);
                $em->flush();
                break;
            case 'disable':
                $server->setLogNextReceivedClaims(false);
                $em->flush();
                break;
            case 'clear':
                $server->setLastLoggedReceivedClaims(null);
                $em->flush();
                break;
            case 'refresh':
                break;
            default:
                throw new UserMessageException(t('Unrecognized operation'));
        }

        return $this->app->make(ResponseFactoryInterface::class)->json([
            'logNextReceivedClaims' => $server->isLogNextReceivedClaims(),
            'lastLoggedReceivedClaims' => $server->getLastLoggedReceivedClaims(),
        ]);
    }

    /**
     * @param int|string|mixed $serverID
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function save($serverID = '')
    {
        if (!$this->app->make(ServerConfigurationProvider::class) instanceof ServerConfigurationProvider\ServerProvider) {
            throw new UserMessageException(t('Servers are handled in another way'));
        }
        if (!$this->token->validate("kc-mappings-save{$serverID}")) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $serverID = is_numeric($serverID) ? (int) $serverID : 0;
        if ($serverID > 0) {
            $em = $this->app->make(EntityManagerInterface::class);
            $server = $em->find(Server::class, $serverID);
        }
        if ($server === null) {
            throw new UserMessageException(t('Unable to find the requested server.'));
        }
        $errors = $this->app->make(ErrorList::class);
        $map = Map::unserialize($this->request->request->get('map'), $errors);
        if ($map === null && !$errors->has()) {
            $errors->add(t('Invalid serialized data'));
        }
        if ($errors->has()) {
            throw new UserMessageException($errors->toText());
        }
        $server->setClaimMap($map);
        $em->flush();

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    protected function getAttriutesDictionary()
    {
        $factory = $this->app->make(Factory::class);
        $dictionary = [];
        foreach ($factory->getSupportedAttributes() as $attribute) {
            $dictionary[$attribute->getAttributeKeyHandle()] = $attribute->getDisplayName();
        }
        $cmp = new Comparer();
        $cmp->sort($dictionary, true);

        return $dictionary;
    }
}
