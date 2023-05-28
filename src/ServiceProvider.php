<?php

namespace KeycloakAuth;

use Concrete\Core\Foundation\Service\Provider;
use KeycloakAuth\Claim\Conversion\ConverterFactory;
use OAuth\ServiceFactory;
use OAuth\UserData\ExtractorFactory;

class ServiceProvider extends Provider
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Foundation\Service\Provider::register()
     */
    public function register()
    {
        $this->app->extend('oauth/factory/service', static function (ServiceFactory $factory) {
            return $factory->registerService('keycloak', Service::class);
        });
        $this->app->extend('oauth/factory/extractor', static function (ExtractorFactory $factory) {
            $factory->addExtractorMapping(Service::class, Extractor::class);

            return $factory;
        });
        $this->app->singleton(ConverterFactory::class);
        $this->app->extend(ConverterFactory::class, static function (ConverterFactory $converterFactory) {
            return $converterFactory->registerDefaultConverters();
        });
    }
}
