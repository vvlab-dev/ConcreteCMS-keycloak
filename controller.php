<?php

namespace Concrete\Package\KeycloakAuth;

use Concrete\Core\Authentication\AuthenticationType;
use Concrete\Core\Database\EntityManager\Provider\ProviderAggregateInterface;
use Concrete\Core\Database\EntityManager\Provider\StandardPackageProvider;
use Concrete\Core\Package\Package;
use OAuth\ServiceFactory;

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
        $this->app->extend('oauth/factory/service', function (ServiceFactory $factory) {
            return $factory->registerService('keycloak', \KeycloakAuth\Service::class);
        });
        $extractor = $this->app->make('oauth/factory/extractor');
        $extractor->addExtractorMapping(\KeycloakAuth\Service::class, \KeycloakAuth\Extractor::class);
    }
}
