<?php

namespace KeycloakAuth;

use Concrete\Core\Authentication\AuthenticationType;
use Concrete\Core\Authentication\Type\OAuth\BindingService;
use Concrete\Core\Http\Response;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\Event\Logout;
use Exception;
use Throwable;

class BeforeLogoutListener
{
    /**
     * @var \Concrete\Core\Authentication\Type\OAuth\BindingService
     */
    protected $bindingService;

    /**
     * @var \Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface
     */
    protected $urlResolver;

    /**
     * @var \Concrete\Core\Http\ResponseFactoryInterface
     */
    protected $responseFactory;

    public function __construct(BindingService $bindingService, ResolverManagerInterface $urlResolver, ResponseFactoryInterface $responseFactory)
    {
        $this->bindingService = $bindingService;
        $this->urlResolver = $urlResolver;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Logout $event)
    {
        $userID = $event->getSubject();
        if ($userID === null) {
            return;
        }
        if ($this->bindingService->getUserBinding($userID, 'keycloak') === null) {
            return;
        }
        $type = $this->getAuthenticationType();
        if ($type === null) {
            return;
        }
        $controller = $type->getController();
        /** @var \Concrete\Package\KeycloakAuth\Authentication\Keycloak\Controller $controller */
        $service = $controller->getService();
        /** @var \KeycloakAuth\Service $service */
        $server = $service->getServer();
        if ($server === null) {
            return;
        }
        if (!$server->isLogoutOnLogout()) {
            return;
        }
        $serverInfo = $server->getOpenIDConfiguration();
        $endSessionEndpoint = $serverInfo['end_session_endpoint'] ?? null;
        if (empty($endSessionEndpoint)) {
            return;
        }
        $token = $service->getLastStoredAccessToken();
        if ($token === null) {
            return;
        }
        $tokenExtraParams = $token->getExtraParams();
        if (empty($tokenExtraParams['id_token'])) {
            return;
        }
        $url = $endSessionEndpoint;
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'id_token_hint=' . rawurlencode($tokenExtraParams['id_token']) . '&post_logout_redirect_uri=' . rawurlencode((string) $this->urlResolver->resolve(['/']));
        $event->setResponse($this->responseFactory->redirect($url, Response::HTTP_FOUND));
    }

    /**
     * @return \Concrete\Core\Authentication\AuthenticationType|null
     */
    private function getAuthenticationType()
    {
        try {
            $type = AuthenticationType::getByHandle('keycloak');
        } catch (Exception $x) {
            return null;
        } catch (Throwable $x) {
            return null;
        }

        return $type && !$type->isError() && $type->isEnabled() ? $type : null;
    }

    private function resetState()
    {
        $this->loggingOutUserID = null;
        $this->redirectTo = '';
    }
}
