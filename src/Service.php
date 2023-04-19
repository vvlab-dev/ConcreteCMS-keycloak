<?php

namespace KeycloakAuth;

use KeycloakAuth\Entity\Server;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\OAuth2\Service\AbstractService;
use OAuth\OAuth2\Token\StdOAuth2Token;
use RuntimeException;

class Service extends AbstractService
{
    const SERVICE_ID = 'Keycloak';

    /**
     * @var string Scope for forcing OIDC
     */
    const SCOPE_OPENID = 'openid';

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
     * @var \KeycloakAuth\Entity\Server|null
     */
    private $server;

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
     * @return \KeycloakAuth\Entity\Server|null
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @return $this
     */
    public function setServer(Server $server)
    {
        $this->server = $server;

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
        return $this->getUriFromConfig('authorization_endpoint');
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\Common\Service\ServiceInterface::getAccessTokenEndpoint()
     */
    public function getAccessTokenEndpoint()
    {
        return $this->getUriFromConfig('token_endpoint');
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
        $server = $this->getServer();
        if ($server === null) {
            throw new RuntimeException('Keycloak server is not defined');
        }
        $key = 'keycloak-serverid-' . $server->getID();
        $token = app('token')->generate($key);
        $result = $server->getID() . '-' . $token;
        $prefix = (string) $prefix;

        return $prefix === '' ? $result : "{$prefix}:{$result}";
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
     * {@inheritdoc}
     *
     * @see \OAuth\OAuth2\Service\AbstractService::parseAccessTokenResponse()
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $body = json_decode($responseBody, true);

        if (isset($body['error'])) {
            throw new TokenResponseException($body['hint']);
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($body['access_token']);
        $token->setRefreshToken($body['refresh_token']);
        $token->setLifetime($body['expires_in']);

        // Store the id_token as an "extra param"
        $token->setExtraParams(['id_token' => $body['id_token']]);

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

    /**
     * @param string $endpointKey
     *
     * @return \OAuth\Common\Http\Uri\Uri
     */
    private function getUriFromConfig($endpointKey)
    {
        $server = $this->getServer();
        if ($server === null) {
            throw new RuntimeException('Keycloak server is not defined');
        }
        $config = $server->getOpenIDConfiguration();
        $endpoint = isset($config[$endpointKey]) ? $config[$endpointKey] : null;
        if (empty($endpoint)) {
            throw new RuntimeException(sprintf('Keycloak server did not provide %s', $endpointKey));
        }

        return new Uri($endpoint);
    }
}
