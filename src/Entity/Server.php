<?php

namespace vvLab\KeycloakAuth\Entity;

use JsonException;
use OAuth\Common\Http\Uri\Uri;
use RuntimeException;
use vvLab\KeycloakAuth\Claim\Map;
use vvLab\KeycloakAuth\ServerConfiguration;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @Doctrine\ORM\Mapping\Entity
 * @Doctrine\ORM\Mapping\Table(
 *     name="authKeycloakServers",
 *     options={"comment": "Keycloak servers to be used for authentication"}
 * )
 */
class Server implements ServerConfiguration
{
    /**
     * The server ID (null if not persisted).
     *
     * @Doctrine\ORM\Mapping\Id
     * @Doctrine\ORM\Mapping\Column(type="integer", options={"unsigned": true, "comment": "Server ID"})
     * @Doctrine\ORM\Mapping\GeneratedValue(strategy="AUTO")
     *
     * @var int|null
     */
    protected $id;

    /**
     * The sort order of this server.
     *
     * @Doctrine\ORM\Mapping\Column(type="integer", nullable=false, options={"unsigned": true, "comment": "Sort order of this server"})
     *
     * @var int
     */
    protected $sort;

    /**
     * The realm root URL.
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=255, nullable=false, unique=false, options={"comment": "Realm root URL"})
     *
     * @var string
     */
    protected $realmRootUrl;

    /**
     * The OpenID configuration (in JSON format).
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "OpenID configuration (in JSON format)"})
     *
     * @var string
     */
    protected $openIDConfiguration;

    /**
     * The client ID.
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=255, nullable=false, options={"comment": "Client ID"})
     *
     * @var string
     */
    protected $clientID;

    /**
     * The client secret.
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=255, nullable=false, options={"comment": "Client secret"})
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * Is new users registration enabled?
     *
     * @Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is new users registration enabled?"})
     *
     * @var bool
     */
    protected $registrationEnabled;

    /**
     * The ID of the group to be assigned to new users.
     *
     * @Doctrine\ORM\Mapping\Column(type="integer", nullable=true, options={"unsigned": true, "comment": "ID of the group to be assigned to new users"})
     *
     * @var int|null
     */
    protected $registrationGroupID;

    /**
     * The list of regular expressions this server should be used for (one per line).
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "List of regular expressions this server should be used for (one per line)"})
     *
     * @var string
     */
    protected $emailRegexes;

    /**
     * Logout from server too when logging out from Concrete?
     *
     * @Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Logout from server too when logging out from Concrete?"})
     *
     * @var bool
     */
    protected $logoutOnLogout;

    /**
     * The claim map to be used (in JSON format).
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "Claim map to be used (in JSON format)"})
     *
     * @var string
     */
    protected $claimMap;

    /**
     * Should the next received claims be logged?
     *
     * @Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Should the next received claims be logged?"})
     *
     * @var bool
     */
    protected $logNextReceivedClaims;

    /**
     * The last logged received claims (in JSON format).
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "Last logged received claims (in JSON format)"})
     *
     * @var string
     */
    protected $lastLoggedReceivedClaims;

    public function __construct()
    {
        $this->id = null;
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfiguration::getHandle()
     */
    public function getHandle()
    {
        return (string) $this->getID();
    }

    /**
     * Get the server ID (null if not persisted).
     *
     * @return int|null
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Get the sort order of this server.
     *
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Get the sort order of this server.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setSort($value)
    {
        $this->sort = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfiguration::getRealmRootUrl()
     */
    public function getRealmRootUrl()
    {
        return $this->realmRootUrl;
    }

    /**
     * Set the realm root URL.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setRealmRootUrl($value)
    {
        $this->realmRootUrl = $value;

        return $this;
    }

    /**
     * Get the OpenID configuration.
     *
     * @return array
     */
    public function getOpenIDConfiguration()
    {
        try {
            $arr = json_decode($this->openIDConfiguration, true, 512, defined('JSON_THROW_ON_ERROR') ? JSON_THROW_ON_ERROR : 0);
        } catch (JsonException $_) {
            return [];
        }

        return is_array($arr) ? $arr : [];
    }

    /**
     * Set the OpenID configuration.
     *
     * @return $this
     */
    public function setOpenIDConfiguration(array $value)
    {
        $this->openIDConfiguration = json_encode($value, JSON_UNESCAPED_LINE_TERMINATORS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfiguration::getClientID()
     */
    public function getClientID()
    {
        return $this->clientID;
    }

    /**
     * Set the client ID.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setClientID($value)
    {
        $this->clientID = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfiguration::getClientSecret()
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * Set the client secret.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setClientSecret($value)
    {
        $this->clientSecret = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfiguration::isRegistrationEnabled()
     */
    public function isRegistrationEnabled()
    {
        return $this->registrationEnabled;
    }

    /**
     * Is new users registration enabled?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setRegistrationEnabled($value)
    {
        $this->registrationEnabled = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfiguration::getRegistrationGroupID()
     */
    public function getRegistrationGroupID()
    {
        return $this->registrationGroupID;
    }

    /**
     * Set the ID of the group to be assigned to new users.
     *
     * @param int|null $value
     *
     * @return $this
     */
    public function setRegistrationGroupID($value)
    {
        $this->registrationGroupID = $value;

        return $this;
    }

    /**
     * Get the list of regular expressions this server should be used for.
     *
     * @return string[]
     */
    public function getEmailRegexes()
    {
        return preg_split('/[\r\n]+/', $this->emailRegexes, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Get the list of regular expressions this server should be used for.
     *
     * @param string[] $value
     *
     * @return $this
     */
    public function setEmailRegexes(array $value)
    {
        $value = array_map(
            static function ($regex) {
                return is_string($regex) ? trim($regex) : '';
            },
            $value
        );
        $value = array_filter(
            $value,
            static function ($regex) {
                return $regex !== '';
            }
        );
        $this->emailRegexes = implode("\n", $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfiguration::isLogoutOnLogout()
     */
    public function isLogoutOnLogout()
    {
        return $this->logoutOnLogout;
    }

    /**
     * Logout from server too when logging out from Concrete?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setLogoutOnLogout($value)
    {
        $this->logoutOnLogout = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfiguration::getClaimMap()
     */
    public function getClaimMap()
    {
        return Map::unserialize($this->claimMap) ?: Map::createDefaultMap();
    }

    /**
     * Set the claim map to be used.
     *
     * @return $this
     */
    public function setClaimMap(Map $value = null)
    {
        $this->claimMap = $value === null ? '' : $value->serialize(false);

        return $this;
    }

    /**
     * Should the next received claims be logged?
     *
     * @return bool
     */
    public function isLogNextReceivedClaims()
    {
        return $this->logNextReceivedClaims;
    }

    /**
     * Should the next received claims be logged?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setLogNextReceivedClaims($value)
    {
        $this->logNextReceivedClaims = $value;

        return $this;
    }

    /**
     * Get the OpenID configuration.
     *
     * @return array|null
     */
    public function getLastLoggedReceivedClaims()
    {
        if (empty($this->lastLoggedReceivedClaims)) {
            return null;
        }
        try {
            $arr = json_decode($this->lastLoggedReceivedClaims, true, 512, defined('JSON_THROW_ON_ERROR') ? JSON_THROW_ON_ERROR : 0);
        } catch (JsonException $_) {
            return null;
        }

        return is_array($arr) ? $arr : null;
    }

    /**
     * Set the OpenID configuration.
     *
     * @return $this
     */
    public function setLastLoggedReceivedClaims(array $value = null)
    {
        $this->lastLoggedReceivedClaims = $value === null ? '' : json_encode($value, JSON_UNESCAPED_LINE_TERMINATORS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfiguration::getAuthorizationEndpointUrl()
     */
    public function getAuthorizationEndpointUrl()
    {
        return $this->getUrlFromConfig('authorization_endpoint', true);
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfiguration::getAccessTokenEndpointUrl()
     */
    public function getAccessTokenEndpointUrl()
    {
        return $this->getUrlFromConfig('token_endpoint', true);
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfiguration::getEndSessionEndpointUrl()
     */
    public function getEndSessionEndpointUrl()
    {
        return $this->getUrlFromConfig('end_session_endpoint', false);
    }

    /**
     * @param string $endpointKey
     *
     * @return string
     */
    private function getUrlFromConfig($endpointKey, $required)
    {
        $openIDConfiguration =  $this->getOpenIDConfiguration();
        $endpoint = isset($openIDConfiguration[$endpointKey]) ? $openIDConfiguration[$endpointKey] : null;
        if (empty($endpoint) || !is_string($endpoint)) {
            if ($required) {
                throw new RuntimeException(t('The keycloak server did not provide %s', $endpointKey));
            }
            return '';
        }

        return $endpoint;
    }
}
