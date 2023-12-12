<?php

namespace vvLab\KeycloakAuth;

use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\OAuth2\Service\AbstractService;
use RuntimeException;

class Service extends AbstractService
{
    const SERVICE_ID = 'Keycloak';

    /**
     * @var string Scope for forcing OIDC
     */
    const SCOPE_OPENID = 'openid';

    const SCOPE_PROFILE = 'profile';

    /**
     * @var string Scope for system info
     */
    const SCOPE_SYSTEM = 'system';

    /**
     * @var string Scope for site tree info
     */
    const SCOPE_SITE = 'site';

    /**
     * @var string Scope for authenticated user
     */
    const SCOPE_ACCOUNT = 'account';

    /**
     * @var \vvLab\KeycloakAuth\ServerConfiguration|null
     */
    private $serverConfiguration;

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\OAuth2\Service\AbstractService::needsStateParameterInAuthUrl()
     */
    public function needsStateParameterInAuthUrl()
    {
        return true;
    }

    /**
     * @return \vvLab\KeycloakAuth\ServerConfiguration|null
     */
    public function getServerConfiguration()
    {
        return $this->serverConfiguration;
    }

    /**
     * @return $this
     */
    public function setServerConfiguration(ServerConfiguration $value)
    {
        $this->serverConfiguration = $value;

        return $this;
    }

    /**
     * @return \OAuth\Common\Http\Uri\UriInterface
     */
    public function getBaseApiUri()
    {
        return clone $this->baseApiUri;
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\Common\Service\ServiceInterface::getAuthorizationEndpoint()
     */
    public function getAuthorizationEndpoint()
    {
        $serverConfiguration = $this->getServerConfiguration();
        if ($serverConfiguration === null) {
            throw new RuntimeException('The Keycloak server configuration is not defined');
        }

        return new Uri($serverConfiguration->getAuthorizationEndpointUrl());
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\Common\Service\ServiceInterface::getAccessTokenEndpoint()
     */
    public function getAccessTokenEndpoint()
    {
        $serverConfiguration = $this->getServerConfiguration();
        if ($serverConfiguration === null) {
            throw new RuntimeException('The Keycloak server configuration is not defined');
        }

        return new Uri($serverConfiguration->getAccessTokenEndpointUrl());
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\OAuth2\Service\AbstractService::generateAuthorizationState()
     */
    public function generateAuthorizationState()
    {
        return $this->generatePrefixedAuthorizationState('');
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    public function generatePrefixedAuthorizationState($prefix)
    {
        $serverConfiguration = $this->getServerConfiguration();
        if ($serverConfiguration === null) {
            throw new RuntimeException(t('The Keycloak server configuration is not defined'));
        }
        $base64Handle = rtrim(base64_encode($serverConfiguration->getHandle()), '=');
        $token = app('token')->generate('keycloak-serverconfiguration-' . $base64Handle);
        $prefix = (string) $prefix;
        
        return $prefix === '' ? "{$base64Handle}-{$token}" : "{$prefix}:{$base64Handle}-{$token}";
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\Common\Service\AbstractService::service()
     */
    public function service()
    {
        return static::SERVICE_ID;
    }

    /**
     * @return \OAuth\Common\Token\TokenInterface|null
     */
    public function getLastStoredAccessToken()
    {
        $storage = $this->getStorage();
        $service = $this->service();

        return $storage->hasAccessToken($service) ? $storage->retrieveAccessToken($service) : null;
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\OAuth2\Service\AbstractService::parseAccessTokenResponse()
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        set_error_handler(static function () {}, -1);
        try {
            $body = json_decode($responseBody, true);
        } finally {
            restore_error_handler();
        }
        if (!is_array($body)) {
            throw new TokenResponseException(t('Invalid JSON data'));
        }
        if (isset($body['error'])) {
            throw new TokenResponseException($body['hint']);
        }
        if (!isset($body['token_type']) || !is_string($body['token_type'])) {
            throw new TokenResponseException(r('Missing field: %s', 'token_type'));
        }
        if (strcasecmp($body['token_type'], 'Bearer') !== 0) {
            throw new TokenResponseException(t('Unsupported value of the %1$s field. Expected %2$s, received %3$s.', 'token_type', "'Bearer'", "'{$body['token_type']}'"));
        }
        unset($body['token_type']);
        $token = new Token();

        $token->setAccessToken($body['access_token']);
        unset($body['access_token']);
        if (isset($body['expires_in'])) {
            $token->setLifetime($body['expires_in']);
        }
        unset($body['expires_in']);
        if (isset($body['refresh_token'])) {
            $token->setRefreshToken($body['refresh_token']);
        }
        unset($body['refresh_token']);
        $token->setExtraParams($body);
        if ($token->getIDToken() === '') {
            throw new TokenResponseException(r('Missing field: %s', 'id_token'));
        }

        return $token;
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\OAuth2\Service\AbstractService::getAuthorizationMethod()
     */
    protected function getAuthorizationMethod()
    {
        return self::AUTHORIZATION_METHOD_HEADER_BEARER;
    }
}
