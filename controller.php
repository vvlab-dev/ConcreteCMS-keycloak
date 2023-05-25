<?php

namespace Concrete\Package\KeycloakAuth;

use Concrete\Core\Authentication\AuthenticationType;
use Concrete\Core\Database\EntityManager\Provider\ProviderAggregateInterface;
use Concrete\Core\Database\EntityManager\Provider\StandardPackageProvider;
use Concrete\Core\Events\EventDispatcher;
use Concrete\Core\Package\Package;
use Concrete\Core\User\Event\Logout;
use KeycloakAuth\BeforeLogoutListener;
use KeycloakAuth\ServiceProvider;

/**
 * The package controller.
 *
 * Manages the package installation, update and start-up.
 */
class Controller extends Package implements ProviderAggregateInterface
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$appVersionRequired
     */
    protected $appVersionRequired = '8.5.0';

    /**
     * The unique handle that identifies the package.
     *
     * @var string
     */
    protected $pkgHandle = 'keycloak_auth';

    /**
     * The package version.
     *
     * @var string
     */
    protected $pkgVersion = '0.0.1';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageName()
     */
    public function getPackageName()
    {
        return t('Keycloak Authentication');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageDescription()
     */
    public function getPackageDescription()
    {
        return t('Let users access the website using a Keycloak server.');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageAutoloaderRegistries()
     */
    public function getPackageAutoloaderRegistries()
    {
        return ['src' => 'KeycloakAuth'];
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Database\EntityManager\Provider\ProviderAggregateInterface::getEntityManagerProvider()
     */
    public function getEntityManagerProvider()
    {
        return new StandardPackageProvider(
            $this->app,
            $this,
            [
                'src/Entity' => 'KeycloakAuth\Entity',
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::install()
     */
    public function install()
    {
        $package = parent::install();
        AuthenticationType::add('keycloak', tc('AuthenticationType', 'Authentication with Keycloak'), 0, $package);
    }

    public function on_start()
    {
        $this->app->make(ServiceProvider::class)->register();
        $this->hookEvents();
    }

    private function hookEvents()
    {
        $dispatcher = $this->app->make(EventDispatcher::class);
        if (method_exists($dispatcher, 'getEventDispatcher')) {
            $dispatcher = $dispatcher->getEventDispatcher();
        }
        $dispatcher->addListener('on_before_user_logout', function ($event) {
            if ($event instanceof Logout) {
                $listener = $this->app->make(BeforeLogoutListener::class);
                $listener($event);
            }
        });
    }
}
