<?php

namespace Concrete\Package\KeycloakAuth\Controller\SinglePage\Dashboard\System\Registration\Authentication;

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use vvLab\KeycloakAuth\Entity\Server;
use vvLab\KeycloakAuth\ServerConfigurationProvider;

defined('C5_EXECUTE') or die('Access Denied.');

class KeycloakMappings extends DashboardPageController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function view()
    {
        $urlResolver = $this->app->make(ResolverManagerInterface::class);
        $this->set('urlResolver', $urlResolver);
        $this->set('authenticationTypesPageUrl', (string) $this->app->make(ResolverManagerInterface::class)->resolve(['/dashboard/system/registration/authentication']));
        if ($this->app->make(ServerConfigurationProvider::class) instanceof ServerConfigurationProvider\ServerProvider) {
            $em = $this->app->make(EntityManagerInterface::class);
            $servers = $em->getRepository(Server::class)->findBy([], ['sort' => 'asc']);
            if (count($servers) === 1) {
                $server = $servers[0];

                return $this->buildRedirect("/dashboard/system/registration/authentication/keycloak_mappings/edit/{$server->getID()}");
            }
            $this->set('servers', $servers);
        } else {
            $this->set('servers', null);
        }

        return null;
    }
}
