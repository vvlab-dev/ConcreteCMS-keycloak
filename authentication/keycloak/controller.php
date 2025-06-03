<?php

namespace Concrete\Package\KeycloakAuth\Authentication\Keycloak;

use Concrete\Core\Authentication\AuthenticationType;
use Concrete\Core\Authentication\Type\OAuth\OAuth2\GenericOauth2TypeController;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Form\Service\Form;
use Concrete\Core\Form\Service\Widget\GroupSelector;
use Concrete\Core\Http\Request;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Routing\RedirectResponse;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\Group\Group;
use Concrete\Core\User\Group\GroupList;
use Concrete\Core\User\User;
use Concrete\Core\User\UserInfoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use OAuth\Common\Token\Exception\ExpiredTokenException;
use OAuth\UserData\Extractor\ExtractorInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Throwable;
use vvLab\KeycloakAuth\Entity\Server;
use vvLab\KeycloakAuth\Extractor;
use vvLab\KeycloakAuth\OpenID\ConfigurationFetcher;
use vvLab\KeycloakAuth\ServerConfigurationProvider;
use vvLab\KeycloakAuth\Service;
use vvLab\KeycloakAuth\ServiceFactory;
use vvLab\KeycloakAuth\UI;

class Controller extends GenericOauth2TypeController
{
    /**
     * @var \vvLab\KeycloakAuth\ServiceFactory
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

    /**
     * @var \Concrete\Core\Package\PackageService
     */
    protected $packageService;

    /**
     * @var \Concrete\Core\User\UserInfoRepository
     */
    protected $userInfoRepository;

    /**
     * @var \vvLab\KeycloakAuth\ServerConfigurationProvider
     */
    protected $serverConfigurationProvider;

    public function __construct(
        ?AuthenticationType $type,
        ServiceFactory $factory,
        ResolverManagerInterface $urlResolver,
        Repository $config,
        PackageService $packageService,
        UserInfoRepository $userInfoRepository,
        ServerConfigurationProvider $serverConfigurationProvider
    ) {
        parent::__construct($type);
        $this->request = Request::getInstance();
        $this->factory = $factory;
        $this->urlResolver = $urlResolver;
        $this->config = $config;
        $this->packageService = $packageService;
        $this->userInfoRepository = $userInfoRepository;
        $this->serverConfigurationProvider = $serverConfigurationProvider;
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
        $relPath = '/images/authentication/keycloak_auth.svg';
        $pkgController = $this->packageService->getClass('keycloak_auth');
        $svgData = file_get_contents($pkgController->getPackagePath() . $relPath);
        $publicSrc = REL_DIR_PACKAGES . $relPath;

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
        $this->set('enableAttach', $this->config->get('keycloak_auth::options.enableAttach') ? true : false);
        $this->set('enableDetach', $this->config->get('keycloak_auth::options.enableDetach') ? true : false);
        $this->set('updateUsername', $this->config->get('keycloak_auth::options.updateUsername') ? true : false);
        $this->set('updateEmail', $this->config->get('keycloak_auth::options.updateEmail') ? true : false);
        $this->set('callbackUrl', $this->getCallbackUrl());
        $list = $this->app->make(GroupList::class);
        $this->set('groups', $list->getResults());
        $this->set('ui', $this->app->make(UI::class));
        if ($this->serverConfigurationProvider instanceof ServerConfigurationProvider\ServerProvider) {
            $this->set('editServers', true);
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
        } else {
            $this->set('editServers', false);
        }
        $this->set('logoutOnLogoutEnabled', class_exists('Concrete\Core\User\Event\Logout'));
        $this->set('urlResolver', $this->urlResolver);
        $this->set('mappingsUrl', (string) $this->urlResolver->resolve(['/dashboard/system/registration/authentication/keycloak_mappings']));
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
        if ($this->serverConfigurationProvider instanceof ServerConfigurationProvider\ServerProvider) {
            $em = $this->app->make(EntityManagerInterface::class);
            $servers = $this->buildServersFromArgs($args, $em, true);
            if ($servers === []) {
                throw new UserMessageException(t('Please specify at least one server.'));
            }
            $em->transactional(static function () use ($em, $servers) {
                foreach ($em->getRepository(Server::class)->findAll() as $existingServer) {
                    if (!in_array($existingServer, $servers, true)) {
                        $em->remove($existingServer);
                    }
                }
                foreach ($servers as $server) {
                    $em->persist($server);
                }
                $em->flush();
            });
        }
        $this->config->save('keycloak_auth::options.enableAttach', !empty($args['enableAttach']));
        $this->config->save('keycloak_auth::options.enableDetach', !empty($args['enableDetach']));
        $this->config->save('keycloak_auth::options.updateUsername', !empty($args['updateUsername']));
        $this->config->save('keycloak_auth::options.updateEmail', !empty($args['updateEmail']));
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
        if ($this->config->get('keycloak_auth::options.enableAttach')) {
            $this->set('attachUrl', (string) $this->urlResolver->resolve(['/ccm/system/authentication/oauth2/' . $this->getHandle() . '/attempt_attach']));
        } else {
            $this->set('attachUrl', '');
        }
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
        $authorizationCode = $this->request->get('code');
        if ($service->needsStateParameterInAuthUrl()) {
            $state = $state ?: '';
        }
        $accessToken = $service->requestAccessToken($authorizationCode, $state);
        if (!$accessToken) {
            throw new UserMessageException(t('Failed to complete authentication.'));
        }
        $this->setToken($accessToken);
        $user = $this->attemptAuthentication();
        if (!$user) {
            throw new UserMessageException(t('No local user account associated with this user, please log in with a local account and connect your account from your user profile.'));
        }

        return $this->completeAuthentication($user);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\Type\OAuth\OAuth2\GenericOauth2TypeController::handle_attach_callback()
     */
    public function handle_attach_callback()
    {
        if (!$this->config->get('keycloak_auth::options.enableAttach')) {
            throw new UserMessageException(t("You can't attach your account"));
        }

        return parent::handle_attach_callback();
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
            $rf = $this->app->make(ResponseFactoryInterface::class);
            $um = $this->app->make(ResolverManagerInterface::class);

            return $rf->redirect((string) $um->resolve(['/login']), 302);
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
        $serverConfiguration = $this->getService()->getServerConfiguration();

        return $serverConfiguration !== null && $serverConfiguration->isRegistrationEnabled();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\Type\OAuth\GenericOauthTypeController::registrationGroupID()
     */
    public function registrationGroupID()
    {
        $serverConfiguration = $this->getService()->getServerConfiguration();

        return $serverConfiguration === null ? null : $serverConfiguration->getRegistrationGroupID();
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

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\Type\OAuth\GenericOauthTypeController::attemptAuthentication()
     */
    protected function attemptAuthentication()
    {
        if ($this->isValid()) {
            $extractor = $this->getExtractor();
            try {
                if ($extractor instanceof Extractor) {
                    $service = $this->getService();
                    if ($service instanceof Service) {
                        $serverConfiguration = $service->getServerConfiguration();
                        if ($serverConfiguration instanceof Server && $serverConfiguration->isLogNextReceivedClaims()) {
                            $claims = $extractor->serializeClaims();
                            if ($claims !== null) {
                                $em = $this->app->make(EntityManagerInterface::class);
                                $serverConfiguration
                                    ->setLastLoggedReceivedClaims($claims)
                                    ->setLogNextReceivedClaims(false)
                                ;
                                $em->flush();
                            }
                        }
                    }
                }
            } catch (Exception $_) {
            } catch (Throwable $_) {
            }
            $userID = $this->getBoundUserID($extractor->getUniqueId());
            if ($userID && $userID > 0) {
                $user = User::getByUserID($userID);
                if ($user && !$user->isError()) {
                    $this->updateExistingUser($user, $extractor);
                }
            }
        }

        return parent::attemptAuthentication();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\Type\OAuth\GenericOauthTypeController::createUser()
     */
    protected function createUser()
    {
        /// @todo create it on my own
        $result = parent::createUser();
        if ($result instanceof User) {
            $this->updateExistingUser($result, $this->getExtractor());
        }

        return $result;
    }

    private function setCommonData()
    {
        $this->set('name', $this->getAuthenticationType()->getAuthenticationTypeDisplayName('text'));
        $this->set('requireEmail', $this->serverConfigurationProvider->isEmailRequired());
    }

    /**
     * @param array|\Traversable $args
     * @param bool $fetchOpenIDConfiguration
     *
     * @return \vvLab\KeycloakAuth\Entity\Server[]
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
     * @return \vvLab\KeycloakAuth\Entity\Server|null
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
        $logoutOnLogout = isset($args["logoutOnLogout_{$index}"]) ? $args["logoutOnLogout_{$index}"] : null;
        if ($serverID === null && $realmRootUrl === null && $clientID === null && $clientSecret === null && $emailRegexes === null && $registrationEnabled === null && $registrationGroupID === null && $logoutOnLogout === null) {
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
            $openIDConfiguration = $this->app->make(ConfigurationFetcher::class)->fetchOpenIDConfiguration($realmRootUrl, $ui);
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
            ->setLogoutOnLogout(class_exists('Concrete\Core\User\Event\Logout') ? !empty($logoutOnLogout) : false)
            ->setClaimMap(null)
            ->setLogNextReceivedClaims(false)
            ->setLastLoggedReceivedClaims(null)
        ;

        return $server;
    }

    /**
     * @return string
     */
    private function getCallbackUrl()
    {
        return (string) $this->urlResolver->resolve(['/ccm/system/authentication/oauth2/' . $this->getHandle() . '/callback']);
    }

    private function updateExistingUser(User $userService, ExtractorInterface $extractor)
    {
        if ($extractor->supportsVerifiedEmail() && !$extractor->isEmailVerified()) {
            throw new UserMessageException(t('Please verify your email with this service before attempting to log in.'));
        }
        if (!($extractor instanceof Extractor)) {
            return;
        }
        $serverConfiguration = null;
        $service = $this->getService();
        if ($service instanceof Service) {
            $serverConfiguration = $service->getServerConfiguration();
        }
        if ($serverConfiguration === null) {
            return;
        }
        $userID = (int) $userService->getUserID();
        $userInfo = null;
        $email = $extractor->supportsEmail() ? (string) $extractor->getEmail() : '';
        if ($email !== '') {
            $userInfo = $this->userInfoRepository->getByEmail($email);
            if ($userInfo) {
                if ((int) $userInfo->getUserID() !== $userID) {
                    throw new UserMessageException(t('Another user already exists with the provided email address.'));
                }
            }
        }
        if (!$userInfo) {
            $userInfo = $this->userInfoRepository->getByID($userID);
            if (!$userInfo) {
                return;
            }
        }
        $update = [];
        if ($this->config->get('keycloak_auth::options.updateUsername') && $extractor->supportsUsername()) {
            $preferredUsername = (string) $extractor->getUsername();
            if ($preferredUsername !== '' && $preferredUsername !== $userInfo->getUserName() && strcasecmp($userInfo->getUserName(), USER_SUPER) !== 0) {
                $existingUserInfo = $this->userInfoRepository->getByUserName($preferredUsername);
                if (!$existingUserInfo || (int) $existingUserInfo->getUserID() !== $userID) {
                    $update['uName'] = $preferredUsername;
                    $userService->uName = $preferredUsername;
                }
            }
            if ($email !== '' && $email !== $userInfo->getUserEmail()) {
                $update['uEmail'] = $email;
            }
        }
        if ($update !== []) {
            $userInfo->update($update);
        }
        $map = $serverConfiguration->getClaimMap();
        foreach ($map->getAttributeList() as $claimID => $attributes) {
            $claimValue = $extractor->getClaimValue($claimID);
            foreach ($attributes as $attribute) {
                /** @var \vvLab\KeycloakAuth\Claim\Map\Attribute $attribute */
                $attribute->mapValue($userInfo, $claimValue);
            }
        }
        $this->app->make('cache/request')->delete('attribute/value');
        $groups = $map->getGroups();
        if ($groups->getClaimName() !== '') {
            $rules = $groups->getRules();
            if ($rules !== []) {
                $claimValue = $extractor->getClaimValue($groups->getClaimName());
                if (is_string($claimValue)) {
                    $claimValue = [$claimValue];
                } elseif (!is_array($claimValue)) {
                    $claimValue = [];
                }
                foreach ($rules as $rule) {
                    if (in_array($rule->getRemoteGroupName(), $claimValue, true)) {
                        if ($rule->isJoinIfPresent()) {
                            $group = Group::getByID($rule->getLocalGroupID());
                            if ($group && !$group->isError() && !$userService->inExactGroup($group)) {
                                $userService->enterGroup($group);
                            }
                        }
                    } else {
                        if ($rule->isLeaveIfAbsent()) {
                            $group = Group::getByID($rule->getLocalGroupID());
                            if ($group && !$group->isError() && $userService->inExactGroup($group)) {
                                $userService->exitGroup($group);
                            }
                        }
                    }
                }
            }
        }
        $event = new GenericEvent($userService);
        $event->setArgument('userInfo', $userInfo);
        $event->setArgument('serverConfiguration', $serverConfiguration);
        $this->app->make('director')->dispatch('keycloak_user_ready', $event);
    }
}
