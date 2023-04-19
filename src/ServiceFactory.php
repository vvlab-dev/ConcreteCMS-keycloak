<?php

namespace KeycloakAuth;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\Request;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\User;
use Doctrine\ORM\EntityManagerInterface;
use KeycloakAuth\Entity\Server;
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

    public function __construct(Repository $config, Session $session, ResolverManagerInterface $url, Request $request, EntityManagerInterface $em, Application $currentUserMaker)
    {
        $this->config = $config;
        $this->session = $session;
        $this->request = $request;
        $this->urlResolver = $url;
        $this->em = $em;
        $this->currentUserMaker = $currentUserMaker;
    }

    /**
     * Create a service object given a ServiceFactory object.
     *
     * @return \OAuth\Common\Service\ServiceInterface
     */
    public function createService(OAuthServiceFactory $factory)
    {
        $server = $this->getApplicableServer();

        $callbackUrl = $this->urlResolver->resolve(['/ccm/system/authentication/oauth2/keycloak/callback/']);
        if ($callbackUrl->getHost() == '') {
            $callbackUrl = $callbackUrl->setHost($this->request->getHost());
            $callbackUrl = $callbackUrl->setScheme($this->request->getScheme());
        }

        $credentials = new Credentials($server->getClientID(), $server->getClientSecret(), (string) $callbackUrl);

        $storage = new SymfonySession($this->session, false);

        $baseApiUrl = new Uri($server->getRealmRootUrl());

        $service = $factory->createService('keycloak', $credentials, $storage, [Service::SCOPE_OPENID], $baseApiUrl);
        $service->setServer($server);

        return $service;
    }

    /**
     * @return \KeycloakAuth\Entity\Server
     */
    private function getApplicableServer()
    {
        $repo = $this->em->getRepository(Server::class);
        $servers = $repo->findBy([], ['sort' => 'ASC']);
        if ($servers === []) {
            throw new UserMessageException(t('No keycloak server has been defined.'));
        }
        $firstServer = $servers[0];
        if ($firstServer->getEmailRegexes() === []) {
            return $firstServer;
        }
        $email = $this->getPostedEmail();
        if ($email !== '') {
            return $this->getApplicableServerForEmail($servers, $email);
        }
        $id = $this->getSessionServerID();
        if ($id !== null) {
            return $this->getApplicableServerByID($servers, $id);
        }
        $user = $this->currentUserMaker->make(User::class);
        if ($user->isRegistered()) {
            $userInfo = $user->getUserInfoObject();
            if ($userInfo && !$userInfo->isError()) {
                return $this->getApplicableServerForEmail($servers, $userInfo->getUserEmail());
            }
        }
        throw new RuntimeException('Unable to detect the user state');
    }

    /**
     * @param \KeycloakAuth\Entity\Server[] $servers
     * @param string $email
     *
     * @return \KeycloakAuth\Entity\Server
     */
    private function getApplicableServerForEmail(array $servers, $email)
    {
        foreach ($servers as $server) {
            $regexes = $server->getEmailRegexes();
            if ($regexes === []) {
                return $server;
            }
            $err = '';
            set_error_handler(static function ($errno, $errstr) use (&$err) {
                $err = is_string($errstr) ? trim($errstr) : '';
                if ($err === '') {
                    $err = "Error {$errno}";
                }
            });
            try {
                foreach ($regexes as $regex) {
                    $err = '';
                    $match = preg_match('/' . preg_quote($regex, '/') . '/i', $email);
                    if ($match === false) {
                        throw new RuntimeException("Error in the following regular expression:\n{$regex}\nError detail: {$err}");
                    }
                    if ($match !== 0) {
                        return $server;
                    }
                }
            } finally {
                restore_error_handler();
            }
        }
        throw new UserMessageException(t('No keycloak server can handle the provided email address.'));
    }

    /**
     * @param \KeycloakAuth\Entity\Server[] $servers
     * @param int $id
     *
     * @return \KeycloakAuth\Entity\Server
     */
    private function getApplicableServerByID(array $servers, $id)
    {
        foreach ($servers as $server) {
            if ($server->getID() == $id) {
                return $server;
            }
        }
        throw new UserMessageException(t('No keycloak server found with the provided ID.'));
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
     * @return int|null
     */
    private function getSessionServerID()
    {
        $storage = new SymfonySession($this->session, false);
        if (!$storage->hasAuthorizationState(Service::SERVICE_ID)) {
            return null;
        }
        $token = $storage->retrieveAuthorizationState(Service::SERVICE_ID);
        $chunks = explode('-', $token, 2);
        if (count($chunks) !== 2) {
            return null;
        }
        $matches = [];
        if (preg_match('/\w+:(\d+)/', $chunks[0], $matches)) {
            $chunks[0] = $matches[1];
        }
        $serverID = is_numeric($chunks[0]) ? (int) $chunks[0] : 0;
        if ($serverID <= 0) {
            return null;
        }
        $key = 'keycloak-serverid-' . $serverID;
        if (!app('token')->validate($key, $chunks[1])) {
            return null;
        }

        return $serverID;
    }
}
