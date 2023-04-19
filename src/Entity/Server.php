<?php

namespace KeycloakAuth\Entity;

use JsonException;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @Doctrine\ORM\Mapping\Entity
 * @Doctrine\ORM\Mapping\Table(
 *     name="authKeycloakServers",
 *     options={"comment": "Keycloak servers to be used for authentication"}
 * )
 */
class Server
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

    public function __construct()
    {
        $this->id = null;
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
     * Get the realm root URL.
     *
     * @return string
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

            return is_array($arr) ? $arr : [];
        } catch (JsonException $_) {
            return [];
        }
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
     * Get the client ID.
     *
     * @return string
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
     * Get the client secret.
     *
     * @return string
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
     * Is new users registration enabled?
     *
     * @return bool
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
     * Get the ID of the group to be assigned to new users.
     *
     * @return int|null
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
}
