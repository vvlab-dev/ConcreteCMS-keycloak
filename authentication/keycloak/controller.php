<?php

namespace Concrete\Package\KeycloakAuth\Authentication\Keycloak;

use Concrete\Core\Authentication\AuthenticationType;
use Concrete\Core\Authentication\Type\OAuth\OAuth2\GenericOauth2TypeController;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Form\Service\Form;
use Concrete\Core\Form\Service\Widget\GroupSelector;
use Concrete\Core\Http\Client\Client;
use Concrete\Core\Http\Request;
use Concrete\Core\Routing\RedirectResponse;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\Group\GroupList;
use Concrete\Core\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use KeycloakAuth\Entity\Server;
use KeycloakAuth\ServiceFactory;
use KeycloakAuth\UI;
use League\Url\Url;
use Throwable;
use OAuth\Common\Token\Exception\ExpiredTokenException;

class Controller extends GenericOauth2TypeController
{
    /**
     * @var \KeycloakAuth\ServiceFactory
     */
    protected $factory;

    /**
     * @var \Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface
     */
    protected $urlResolver;

    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    public function __construct(AuthenticationType $type = null, ServiceFactory $factory, ResolverManagerInterface $urlResolver, Repository $config)
    {
        $this->request = Request::getInstance();
        parent::__construct($type);
        $this->factory = $factory;
        $this->urlResolver = $urlResolver;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\AuthenticationTypeController::getHandle()
     */
    public function getHandle()
    {
        return 'keycloak';
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\AuthenticationTypeController::getAuthenticationTypeIconHTML()
     */
    public function getAuthenticationTypeIconHTML()
    {
        $svgData = file_get_contents(DIR_BASE_CORE . '/images/authentication/community/concrete.svg');
        $publicSrc = '/concrete/images/authentication/community/concrete.svg';

        return "<div class='ccm-concrete-authentication-type-svg' data-src='{$publicSrc}'>{$svgData}</div>";
    }

    /**
     * This method is called just before rendering type_form.php: use it to set data for that template.
     */
    public function edit()
    {
        $this->addHeaderItem(
            <<<'EOT'
<style>
[v-cloak] {
    display: none;
}
</style>
EOT
        );
        $this->requireAsset('javascript', 'vue');
        $this->set('groupSelector', $this->app->make(GroupSelector::class));
        $this->set('form', $this->app->make('helper/form'));
        $em = $this->app->make(EntityManagerInterface::class);
        $this->set('enableDetach', $this->config->get('keycloak_auth::options.enableDetach') ? true : false);
        $this->set('callbackUrl', $this->getCallbackUrl());
        $list = $this->app->make(GroupList::class);
        $this->set('groups', $list->getResults());
        $this->set('ui', $this->app->make(UI::class));
        $servers = null;
        if ($this->request->isPost()) {
            try {
                $servers = $this->buildServersFromArgs($this->request->request->all(), $em, false);
            } catch (Exception $_) {
            } catch (Throwable $_) {
            }
        }
        if ($servers === null) {
            $repo = $em->getRepository(Server::class);
            $servers = $repo->findBy([], ['sort' => 'ASC']);
        }
        $this->set('servers', $servers);
    }

    /**
     * This method is called when the type_form.php submits: it stores client details and configuration for connecting.
     *
     * @param array|\Traversable $args
     */
    public function saveAuthenticationType($args)
    {
        $passedName = isset($args['displayName']) ? $args['displayName'] : '';
        $passedName = is_string($passedName) ? trim($passedName) : '';
        if ($passedName === '') {
            throw new UserMessageException(t('Invalid display name'));
        }
        $em = $this->app->make(EntityManagerInterface::class);
        $servers = $this->buildServersFromArgs($args, $em, true);
        if ($servers === []) {
            throw new UserMessageException(t('Please specify at least one server.'));
        }
        $em->transactional(static function () use ($em, $servers) {
            $allServers = $em->getRepository(Server::class)->findAll();
            $deletedServers = array_udiff($allServers, $servers, static function (Server $a, Server $b) {
                return $a === $b ? 0 : 1;
            });
            foreach ($servers as $server) {
                $em->persist($server);
            }
            foreach ($deletedServers as $server) {
                $em->remove($server);
            }
            $em->flush();
        });
        $this->config->save('keycloak_auth::options.enableDetach', !empty($args['enableDetach']));
        $this->authenticationType->setAuthenticationTypeName($passedName);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\Type\OAuth\OAuth2\GenericOauth2TypeController::getService()
     */
    public function getService()
    {
        if (!$this->service) {
            $serviceFactory = $this->app->make('oauth/factory/service');
            $this->service = $this->factory->createService($serviceFactory);
        }

        return $this->service;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\Type\OAuth\GenericOauthTypeController::form()
     */
    public function form()
    {
        $this->setCommonData();
        $this->set('user', $this->app->make(User::class));
        $this->set('authUrl', (string) $this->urlResolver->resolve(['/ccm/system/authentication/oauth2/' . $this->getHandle() . '/attempt_auth']));
        $this->set('ui', $this->app->make(UI::class));
        $this->set('form', $this->app->make(Form::class));
    }

    /**
     * This method is called before hook.php is rendered, use it to set data for that template.
     */
    public function hook()
    {
        $this->setCommonData();
        $this->set('attachUrl', (string) $this->urlResolver->resolve(['/ccm/system/authentication/oauth2/' . $this->getHandle() . '/attempt_attach']));
        $me = $this->app->make(User::class);
        $myInfo = $me->getUserInfoObject();
        $this->set('userEmail', $myInfo ? (string) $myInfo->getUserEmail() : '');
    }

    /**
     * This method gets called before hooked.php is rendered: use it to set data for that template.
     */
    public function hooked()
    {
        $this->setCommonData();
        $this->set('ui', $this->app->make(UI::class));
        if ($this->config->get('keycloak_auth::options.enableDetach')) {
            $this->set('detachUrl', (string) $this->urlResolver->resolve(['/ccm/system/authentication/oauth2/' . $this->getHandle() . '/attempt_detach']));
        } else {
            $this->set('detachUrl', '');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\Type\OAuth\OAuth2\GenericOauth2TypeController::handle_attach_attempt()
     */
    public function handle_attach_attempt()
    {
        $url = $this->getService()->getAuthorizationUri(
            [
                'state' => $this->getService()->generatePrefixedAuthorizationState('attach'),
            ] + $this->getAdditionalRequestParameters()
        );

        return new RedirectResponse((string) $url);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\Type\OAuth\OAuth2\GenericOauth2TypeController::handle_authentication_callback()
     * @see https://github.com/concretecms/concretecms/pull/10984
     * @see https://github.com/concretecms/concretecms/pull/11377
     */
    public function handle_authentication_callback()
    {
        $state = $this->request->get('state');
        if (is_string($state) && substr($state, 0, 7) === 'attach:') {
            return $this->handle_attach_callback();
        }
        $user = $this->app->make(User::class);
        if ($user && !$user->isError() && $user->isRegistered()) {
            throw new UserMessageException(t('You are already logged in.'));
        }
        $service = $this->getService();
        $code = $this->request->get('code');
        if ($service->needsStateParameterInAuthUrl()) {
            $state = $state ?: '';
        }
        $token = $service->requestAccessToken($code, $state);
        if (!$token) {
            throw new UserMessageException(t('Failed to complete authentication.'));
        }
        $this->setToken($token);
        $user = $this->attemptAuthentication();
        if (!$user) {
            throw new UserMessageException(t('No local user account associated with this user, please log in with a local account and connect your account from your user profile.'));
        }

        return $this->completeAuthentication($user);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\Type\OAuth\GenericOauthTypeController::handle_detach_attempt()
     * @see https://github.com/concretecms/concretecms/pull/11385
     */
    public function handle_detach_attempt()
    {
        $user = $this->app->make(User::class);
        if (!$user->isRegistered()) {
            return new RedirectResponse(\URL::to('/login'), 302);
        }
        if (!$this->config->get('keycloak_auth::options.enableDetach')) {
            throw new UserMessageException(t("You can't detach your account"));
        }
        $uID = $user->getUserID();
        $namespace = $this->getHandle();
        $binding = $this->getBindingForUser($user);
        try {
            $this->getService()->request('/' . $binding . '/permissions', 'DELETE');
        } catch (ExpiredTokenException $_) {
        }
        $this->getBindingService()->clearBinding($uID, $binding, $namespace);

        return $this->showSuccess(t('Successfully detached.'));
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\Type\OAuth\GenericOauthTypeController::supportsRegistration()
     */
    public function supportsRegistration()
    {
        $server = $this->getService()->getServer();

        return $server !== null && $server->isRegistrationEnabled();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\Type\OAuth\GenericOauthTypeController::registrationGroupID()
     */
    public function registrationGroupID()
    {
        $server = $this->getService()->getServer();

        return $server === null ? null : $server->getRegistrationGroupID();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\Type\OAuth\GenericOauthTypeController::isValid()
     * @see https://github.com/concretecms/concretecms/pull/11374
     */
    protected function isValid()
    {
        $this->getExtractor();
        return parent::isValid();
    }

    private function setCommonData()
    {
        $this->set('name', $this->getAuthenticationType()->getAuthenticationTypeDisplayName('text'));
        $repo = $this->app->make(EntityManagerInterface::class)->getRepository(Server::class);
        $firstServer = $repo->findOneBy([], ['sort' => 'ASC']);
        $this->set('requireEmail', $firstServer !== null && $firstServer->getEmailRegexes() !== []);
    }

    /**
     * @param array|\Traversable $args
     * @param bool $fetchOpenIDConfiguration
     *
     * @return \KeycloakAuth\Entity\Server[]
     */
    private function buildServersFromArgs($args, EntityManagerInterface $em, $fetchOpenIDConfiguration)
    {
        $ui = $this->app->make(UI::class);
        $servers = [];
        for ($index = 0;; $index++) {
            $server = $this->buildServerFromArgs($args, $index, $em, $ui, $fetchOpenIDConfiguration);
            if ($server === null) {
                return $servers;
            }
            $servers[] = $server;
        }
    }

    /**
     * @param array|\Traversable $args
     * @param int $index
     * @param bool $fetchOpenIDConfiguration
     *
     * @return \KeycloakAuth\Entity\Server|null
     */
    private function buildServerFromArgs($args, $index, EntityManagerInterface $em, UI $ui, $fetchOpenIDConfiguration)
    {
        $serverID = isset($args["serverID_{$index}"]) ? $args["serverID_{$index}"] : null;
        $realmRootUrl = isset($args["realmRootUrl_{$index}"]) ? $args["realmRootUrl_{$index}"] : null;
        $clientID = isset($args["clientID_{$index}"]) ? $args["clientID_{$index}"] : null;
        $clientSecret = isset($args["clientSecret_{$index}"]) ? $args["clientSecret_{$index}"] : null;
        $emailRegexes = isset($args["emailRegexes_{$index}"]) ? $args["emailRegexes_{$index}"] : null;
        $registrationEnabled = isset($args["registrationEnabled_{$index}"]) ? $args["registrationEnabled_{$index}"] : null;
        $registrationGroupID = isset($args["registrationGroupID_{$index}"]) ? $args["registrationGroupID_{$index}"] : null;
        if ($serverID === null && $realmRootUrl === null && $clientID === null && $clientSecret === null && $emailRegexes === null && $registrationEnabled === null && $registrationGroupID === null) {
            return null;
        }
        if (empty($serverID)) {
            $server = new Server();
            $openIDConfiguration = [];
        } else {
            $server = is_numeric($serverID) ? $em->find(Server::class, (int) $serverID) : null;
            if ($server === null) {
                throw new UserMessageException(t('Unable to find the server with ID %s', $serverID));
            }
            $openIDConfiguration = $server->getOpenIDConfiguration();
        }
        if (!is_string($realmRootUrl) || ($realmRootUrl = trim($realmRootUrl)) === '' || ($realmRootUrl = rtrim($realmRootUrl, '/')) === '') {
            throw new UserMessageException(t('Please specify the realm root URL of the server #%s', $index + 1));
        }
        $registrationGroupID = is_numeric($registrationGroupID) ? (int) $registrationGroupID : null;
        if ($registrationGroupID <= 0) {
            $registrationGroupID = null;
        }
        if ($fetchOpenIDConfiguration) {
            $openIDConfiguration = $this->fetchOpenIDConfiguration($realmRootUrl, $ui);
        }
        $server
            ->setSort($index)
            ->setRealmRootUrl($realmRootUrl)
            ->setOpenIDConfiguration($openIDConfiguration)
            ->setClientID(trim((string) $clientID))
            ->setClientSecret(trim((string) $clientSecret))
            ->setRegistrationEnabled(!empty($registrationEnabled))
            ->setRegistrationGroupID($registrationGroupID)
            ->setEmailRegexes(preg_split('/\s*[\r\n]+\s*/', trim($emailRegexes), -1, PREG_SPLIT_NO_EMPTY))
        ;

        return $server;
    }

    /**
     * @param string $realmRootUrl
     *
     * @return array
     */
    private function fetchOpenIDConfiguration($realmRootUrl, UI $ui)
    {
        try {
            $url = Url::createFromUrl($realmRootUrl);
        } catch (Exception $_) {
            throw new UserMessageException(t('Please specify a valid root URL of the realm'));
        } catch (Throwable $_) {
            throw new UserMessageException(t('Please specify a valid root URL of the realm'));
        }
        if (!(string) $url->getScheme() || !(string) $url->getHost()) {
            throw new UserMessageException(t('Please specify a valid root URL of the realm'));
        }
        try {
            $path = rtrim($url->getPath(), '/') . '/.well-known/openid-configuration';
            $url = $url->setPath($path);
            $client = $this->app->make(Client::class);
            if ($ui->majorVersion >= 9) {
                $response = $client->get((string) $url);
                $json = (string) $response->getBody();
            } else {
                $client->setUri((string) $url);
                $response = $client->send();
                $json = $response->getBody();
            }
            $openIDConfiguration = json_decode($json, true, 512, defined('JSON_THROW_ON_ERROR') ? JSON_THROW_ON_ERROR : 0);
            if (!is_array($openIDConfiguration)) {
                throw new UserMessageException(t('Invalid response from the Keycloak server'));
            }
        } catch (Exception $x) {
            throw new UserMessageException(t('Error while inspecting the URL %s', (string) $url) . "\n" . $x->getMessage());
        } catch (Throwable $x) {
            throw new UserMessageException(t('Error while inspecting the URL %s', (string) $url) . "\n" . $x->getMessage());
        }

        return $openIDConfiguration;
    }

    /**
     * @return string
     */
    private function getCallbackUrl()
    {
        return (string) $this->urlResolver->resolve(['/ccm/system/authentication/oauth2/' . $this->getHandle() . '/callback']);
    }
}
