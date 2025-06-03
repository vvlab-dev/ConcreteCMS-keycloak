<?php

namespace Concrete\Package\KeycloakAuth;

use Concrete\Core\Authentication\AuthenticationType;
use Concrete\Core\Database\EntityManager\Provider\ProviderAggregateInterface;
use Concrete\Core\Database\EntityManager\Provider\StandardPackageProvider;
use Concrete\Core\Package\Package;
use vvLab\KeycloakAuth\BeforeLogoutListener;
use vvLab\KeycloakAuth\ServiceProvider;

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
    protected $appVersionRequired = '8.5.10';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$phpVersionRequired
     */
    protected $phpVersionRequired = '7.1';

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
    protected $pkgVersion = '1.1.0';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageName()
     */
    public function getPackageName()
    {
        return t('Authentication with Keycloak');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageDescription()
     */
    public function getPackageDescription()
    {
        return t('Let users access your website using Keycloak OpenID servers.');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageAutoloaderRegistries()
     */
    public function getPackageAutoloaderRegistries()
    {
        return class_exists(ServiceProvider::class) ? [] : ['src' => 'vvLab\\KeycloakAuth'];
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
                'src/Entity' => 'vvLab\KeycloakAuth\Entity',
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
        $this->app->make(ServiceProvider::class)->register();
        AuthenticationType::add('keycloak', tc('AuthenticationType', 'Authentication with Keycloak'), 0, $package);
        $this->installContentFile('config/install.xml');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::upgrade()
     */
    public function upgrade()
    {
        parent::upgrade();
        $this->installContentFile('config/install.xml');
    }

    public function on_start()
    {
        $this->app->make(ServiceProvider::class)->register();
        $this->hookEvents();
    }

    private function hookEvents()
    {
        $dispatcher = $this->app->make('director');
        if (method_exists($dispatcher, 'getEventDispatcher')) {
            $dispatcher = $dispatcher->getEventDispatcher();
        }
        $dispatcher->addListener('on_before_user_logout', function ($event) {
            if (is_a($event, 'Concrete\\Core\\User\\Event\\Logout', true)) {
                $listener = $this->app->make(BeforeLogoutListener::class);
                $listener($event);
            }
        });
    }
}
