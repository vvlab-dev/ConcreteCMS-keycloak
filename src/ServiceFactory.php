<?php

namespace vvLab\KeycloakAuth;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\Request;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\User;
use Doctrine\ORM\EntityManagerInterface;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Storage\SymfonySession;
use OAuth\ServiceFactory as OAuthServiceFactory;
use RuntimeException;
use Symfony\Component\HttpFoundation\Session\Session;

class ServiceFactory
{
    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    protected $session;

    /**
     * @var \Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface
     */
    protected $urlResolver;

    /**
     * @var \Concrete\Core\Http\Request
     */
    protected $request;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \Concrete\Core\Application\Application
     */
    protected $currentUserMaker;

    /**
     * @var \vvLab\KeycloakAuth\ServerConfigurationProvider
     */
    protected $serverConfigurationProvider;

    public function __construct(Repository $config, Session $session, ResolverManagerInterface $url, Request $request, EntityManagerInterface $em, Application $currentUserMaker, ServerConfigurationProvider $serverConfigurationProvider)
    {
        $this->config = $config;
        $this->session = $session;
        $this->request = $request;
        $this->urlResolver = $url;
        $this->em = $em;
        $this->currentUserMaker = $currentUserMaker;
        $this->serverConfigurationProvider = $serverConfigurationProvider;
    }

    /**
     * Create a service object given a ServiceFactory object.
     *
     * @return \OAuth\Common\Service\ServiceInterface
     */
    public function createService(OAuthServiceFactory $factory)
    {
        $serverConfiguration = $this->getApplicableServerConfiguration();

        $callbackUrl = $this->urlResolver->resolve(['/ccm/system/authentication/oauth2/keycloak/callback/']);
        if ($callbackUrl->getHost() == '') {
            $callbackUrl = $callbackUrl->setHost($this->request->getHost());
            $callbackUrl = $callbackUrl->setScheme($this->request->getScheme());
        }

        $credentials = new Credentials($serverConfiguration->getClientID(), $serverConfiguration->getClientSecret(), (string) $callbackUrl);

        $storage = new SymfonySession($this->session, false);

        $baseApiUrl = new Uri($serverConfiguration->getRealmRootUrl());

        $service = $factory->createService('keycloak', $credentials, $storage, [Service::SCOPE_OPENID, Service::SCOPE_PROFILE], $baseApiUrl);
        $service->setServerConfiguration($serverConfiguration);

        return $service;
    }

    /**
     * @return \vvLab\KeycloakAuth\ServerConfiguration
     */
    private function getApplicableServerConfiguration()
    {
        $user = $this->currentUserMaker->make(User::class);
        $userInfo = $user->isRegistered() ? $user->getUserInfoObject() : null;
        if ($userInfo && !$userInfo->isError()) {
            $serverConfiguration = $this->serverConfigurationProvider->getServerConfigurationByEmail($userInfo->getUserEmail());
            if ($serverConfiguration === null) {
                throw new UserMessageException(t('No keycloak server/realm can handle the your email address.'));
            }
            return $serverConfiguration;
        }
        
        $email = $this->getPostedEmail();
        if ($email !== '') {
            $serverConfiguration = $this->serverConfigurationProvider->getServerConfigurationByEmail($email);
            if ($serverConfiguration === null) {
                throw new UserMessageException(t('No keycloak server/realm can handle the provided email address.'));
            }
            return $serverConfiguration;
        }

        $handle = $this->getServerConfigurationHandleFromSession();
        if ($handle !== '') {
            $serverConfiguration = $this->serverConfigurationProvider->getServerConfigurationByHandle($handle);
            if ($serverConfiguration === null) {
                throw new UserMessageException(t('No keycloak server/realm found with the provided handle.'));
            }
            return $serverConfiguration;
        }

        throw new RuntimeException(t('Unable to detect the user state'));
    }

    /**
     * @return string
     */
    private function getPostedEmail()
    {
        if ($this->request->getMethod() !== 'POST') {
            return '';
        }

        $email = $this->request->request->get('email');

        return is_string($email) ? trim($email) : '';
    }

    /**
     * @return string
     */
    private function getServerConfigurationHandleFromSession()
    {
        $storage = new SymfonySession($this->session, false);
        if (!$storage->hasAuthorizationState(Service::SERVICE_ID)) {
            return '';
        }
        $raw = $storage->retrieveAuthorizationState(Service::SERVICE_ID);
        $p = strrpos($raw, '-');
        if ($p === false || $p < 1) {
            return '';
        }
        $firstChunk = substr($raw, 0, $p);
        $token = substr($raw, $p + 1);
        $p = strrpos($firstChunk, ':');
        if ($p === false) {
            $base64Handle = $firstChunk;
        } else {
            $base64Handle = substr($firstChunk, $p + 1);
        }
        if (!app('token')->validate('keycloak-serverconfiguration-' . $base64Handle, $token)) {
            return '';
        }
        $serverHandle = base64_decode($base64Handle, false);
        if ($serverHandle === false || $serverHandle === '') {
            return '';
        }
        
        return $serverHandle;
    }
}
