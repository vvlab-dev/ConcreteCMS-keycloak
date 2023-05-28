<?php

namespace KeycloakAuth;

use KeycloakAuth\Claim\Map;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token\Parser as TokenParser;
use OAuth\Common\Exception\Exception as OAuthException;
use OAuth\Common\Storage\Exception\TokenNotFoundException;
use OAuth\UserData\Extractor\ExtractorInterface;

class Extractor implements ExtractorInterface
{
    /**
     * @var \KeycloakAuth\Service|\OAuth\Common\Service\ServiceInterface|null NULL only in constructor
     */
    protected $service;

    /**
     * @var array|null
     */
    private $claims;

    /**
     * @var \KeycloakAuth\Claim\Map|null
     */
    private $map;

    /**
     * Called right after constructor.
     *
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::setService()
     */
    public function setService($service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::supportsUniqueId()
     */
    public function supportsUniqueId()
    {
        return $this->getMap()->getClaimNameForField(static::FIELD_UNIQUE_ID) !== '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::getUniqueId()
     */
    public function getUniqueId()
    {
        return $this->getStringClaim($this->getMap()->getClaimNameForField(static::FIELD_UNIQUE_ID));
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::supportsUsername()
     */
    public function supportsUsername()
    {
        return $this->getMap()->getClaimNameForField(static::FIELD_USERNAME) !== '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::getUsername()
     */
    public function getUsername()
    {
        return $this->getStringClaim($this->getMap()->getClaimNameForField(static::FIELD_USERNAME));
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::supportsFirstName()
     */
    public function supportsFirstName()
    {
        return $this->getMap()->getClaimNameForField(static::FIELD_FIRST_NAME) !== '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::getFirstName()
     */
    public function getFirstName()
    {
        return $this->getStringClaim($this->getMap()->getClaimNameForField(static::FIELD_FIRST_NAME));
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::supportsLastName()
     */
    public function supportsLastName()
    {
        return $this->getMap()->getClaimNameForField(static::FIELD_LAST_NAME) !== '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::getLastName()
     */
    public function getLastName()
    {
        return $this->getStringClaim($this->getMap()->getClaimNameForField(static::FIELD_LAST_NAME));
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::supportsFullName()
     */
    public function supportsFullName()
    {
        return $this->getMap()->getClaimNameForField(static::FIELD_FULL_NAME) !== '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::getFullName()
     */
    public function getFullName()
    {
        return $this->getStringClaim($this->getMap()->getClaimNameForField(static::FIELD_FULL_NAME));
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::supportsEmail()
     */
    public function supportsEmail()
    {
        return $this->getMap()->getClaimNameForField(static::FIELD_EMAIL) !== '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::getEmail()
     */
    public function getEmail()
    {
        return $this->getStringClaim($this->getMap()->getClaimNameForField(static::FIELD_EMAIL));
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::supportsLocation()
     */
    public function supportsLocation()
    {
        return $this->getMap()->getClaimNameForField(static::FIELD_LOCATION) !== '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::getLocation()
     */
    public function getLocation()
    {
        return $this->getStringClaim($this->getMap()->getClaimNameForField(static::FIELD_LOCATION));
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::supportsDescription()
     */
    public function supportsDescription()
    {
        return $this->getMap()->getClaimNameForField(static::FIELD_DESCRIPTION) !== '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::getDescription()
     */
    public function getDescription()
    {
        return $this->getStringClaim($this->getMap()->getClaimNameForField(static::FIELD_DESCRIPTION));
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::supportsImageUrl()
     */
    public function supportsImageUrl()
    {
        return $this->getMap()->getClaimNameForField(static::FIELD_IMAGE_URL) !== '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::getImageUrl()
     */
    public function getImageUrl()
    {
        return $this->getStringClaim($this->getMap()->getClaimNameForField(static::FIELD_IMAGE_URL));
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::supportsProfileUrl()
     */
    public function supportsProfileUrl()
    {
        return $this->getMap()->getClaimNameForField(static::FIELD_PROFILE_URL) !== '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::getProfileUrl()
     */
    public function getProfileUrl()
    {
        return $this->getStringClaim($this->getMap()->getClaimNameForField(static::FIELD_PROFILE_URL));
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::supportsWebsites()
     */
    public function supportsWebsites()
    {
        return $this->getMap()->getClaimNameForField(static::FIELD_WEBSITES) !== '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::getWebsites()
     */
    public function getWebsites()
    {
        return $this->getStringClaim($this->getMap()->getClaimNameForField(static::FIELD_WEBSITES));
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::supportsVerifiedEmail()
     */
    public function supportsVerifiedEmail()
    {
        return $this->getStringClaim($this->getMap()->getClaimNameForField(static::FIELD_VERIFIED_EMAIL));
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::isEmailVerified()
     */
    public function isEmailVerified()
    {
        return $this->getBooleanClaim($this->getMap()->getClaimNameForField(static::FIELD_VERIFIED_EMAIL));
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::supportsExtra()
     */
    public function supportsExtra()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::getExtras()
     */
    public function getExtras()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @see \OAuth\UserData\Extractor\ExtractorInterface::getExtra()
     */
    public function getExtra($key)
    {
        return null;
    }

    /**
     * @return array|null
     */
    public function serializeClaims()
    {
        $result = [];
        foreach ($this->getClaims() as $key => $value) {
            if ($value instanceof \JsonSerializable) {
                $result[$key] = $value->jsonSerialize();
            } else {
                return null;
            }
        }

        return $result;
    }
    
    
    /**
     * @param string $claimName
     * @param mixed $onNotFound
     *
     * @return mixed
     */
    public function getClaimValue($claimName, $onNotFound = null)
    {
        if ($claimName === '') {
            return $onNotFound;
        }
        $claims = $this->getClaims();
        if (!isset($claims[$claimName])) {
            return $onNotFound;
        }

        return $claims[$claimName]->getValue();
    }

    /**
     * @return array
     */
    protected function getClaims()
    {
        if ($this->claims === null) {
            $claims = $this->decodeClaims();
            $this->checkRequiredClaims($claims);
            $this->claims = $claims;
        }

        return $this->claims;
    }

    /**
     * @return array
     */
    protected function decodeClaims()
    {
        $token = $this->service->getStorage()->retrieveAccessToken($this->service->service());
        if (!($token instanceof Token)) {
            throw new TokenNotFoundException(t('Failed to retrieve the access token.'));
        }
        if (class_exists(TokenParser::class)) {
            $decoder = new TokenParser(new JoseEncoder());
            $token = $decoder->parse($token->getIDToken());
            $claims = $token->claims()->all();
        } else {
            $decoder = new Parser();
            $token = $decoder->parse($token->getIDToken());
            $claims = $token->getClaims();
        }

        return $claims;
    }

    /**
     * @see https://openid.net/specs/openid-connect-core-1_0.html#IDToken
     */
    protected function checkRequiredClaims(array $claims)
    {
        $map = $this->getMap();
        $requiredClaims = [];
        foreach ([
            static::FIELD_UNIQUE_ID,
        ] as $field) {
            $claim = $map->getClaimNameForField($field);
            if ($claim !== '') {
                $requiredClaims[] = $claim;
            }
        }
        $missingClaims = array_diff($requiredClaims, array_keys($claims));
        if ($missingClaims !== []) {
            throw new OAuthException(t('The data sent from the server is missing the following keys:') . "\n- " . implode("\n- ", $missingClaims));
        }
    }

    /**
     * @return \KeycloakAuth\Claim\Map
     */
    protected function getMap()
    {
        if ($this->map === null) {
            $server = $this->service->getServer();
            $this->map = $server === null ? Map::getDefaultMap() : $server->getClaimMap();
        }

        return $this->map;
    }

    /**
     * @param string $claimName
     *
     * @return bool
     */
    protected function hasClaim($claimName)
    {
        return array_key_exists($claimName, $this->getClaims());
    }

    /**
     * @param string $claimName
     * @param mixed $onNotFound
     *
     * @return \Lcobucci\JWT\Claim|null
     */
    protected function getClaim($claimName)
    {
        if ($claimName === '') {
            return null;
        }
        $claims = $this->getClaims();

        return isset($claims[$claimName]) ? $claims[$claimName] : null;
    }

    /**
     * @param string $claimName
     *
     * @return string
     */
    protected function getStringClaim($claimName)
    {
        $claim = $this->getClaim($claimName);
        if ($claim === null) {
            return '';
        }
        $value = $claim->getValue();

        return is_string($value) ? $value : '';
    }

    /**
     * @param string $claimName
     * @param bool|mixed $onNotFound
     * @param bool|mixed $onInvalid
     *
     * @return bool|mixed
     */
    protected function getBooleanClaim($claimName, $onNotFound = false, $onInvalid = false)
    {
        $claim = $this->getClaim($claimName, $onNotFound);
        if ($claim === $onNotFound) {
            return '';
        }
        $value = $claim->getValue();

        return is_bool($value) ? $value : $onInvalid;
    }
}
