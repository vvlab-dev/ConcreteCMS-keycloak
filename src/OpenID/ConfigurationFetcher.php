<?php

namespace vvLab\KeycloakAuth\OpenID;

use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\Client\Client;
use Exception;
use League\Url\Url;
use Throwable;
use vvLab\KeycloakAuth\UI;

class ConfigurationFetcher
{
    /**
     * @var \vvLab\KeycloakAuth\UI
     */
    private $ui;

    /**
     * @var \Concrete\Core\Http\Client\Client
     */
    private $client;

    public function __construct(UI $ui, Client $client)
    {
        $this->ui = $ui;
        $this->client = $client;
    }

    /**
     * @param string $realmRootUrl
     *
     * @throws \Concrete\Core\Error\UserMessageException
     *
     * @return array
     */
    public function fetchOpenIDConfiguration($realmRootUrl)
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
            if ($this->ui->majorVersion >= 9) {
                $response = $this->client->get((string) $url);
                $json = (string) $response->getBody();
            } else {
                $response = $this->client->setUri((string) $url)->send();
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
}
