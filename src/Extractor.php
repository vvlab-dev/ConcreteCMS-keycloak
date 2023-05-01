<?php

namespace KeycloakAuth;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token\Parser as TokenParser;
use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\UserData\Extractor\LazyExtractor;

class Extractor extends LazyExtractor
{
    const USER_PATH = '/ccm/api/1.0/account';

    protected $service;

    public function __construct()
    {
        parent::__construct(
            $this->getDefaultLoadersMap(),
            $this->getNormalizersMap(),
            $this->getSupports()
        );
    }

    public function getSupports()
    {
        return [
            self::FIELD_EMAIL,
            self::FIELD_UNIQUE_ID,
            self::FIELD_USERNAME,
            self::FIELD_FIRST_NAME,
            self::FIELD_LAST_NAME,
        ];
    }

    public function idNormalizer($data)
    {
        if (isset($data['claims'])) {
            return $this->claim(array_get($data, 'claims.sub'));
        }

        return isset($data['id']) ? (int) $data['id'] : null;
    }

    public function emailNormalizer($data)
    {
        return $this->getNormalizedValue($data, 'email', 'email');
    }

    public function firstNameNormalizer($data)
    {
        return $this->getNormalizedValue($data, 'given_name', 'first_name');
    }

    public function lastNameNormalizer($data)
    {
        return $this->getNormalizedValue($data, 'family_name', 'last_name');
    }

    public function usernameNormalizer($data)
    {
        return $this->getNormalizedValue($data, 'preferred_username', 'username');
    }

    /**
     * Load the external Concrete profile, either from id_token or through the API.
     *
     * @throws \OAuth\Common\Exception\Exception
     * @throws \OAuth\Common\Storage\Exception\TokenNotFoundException
     * @throws \OAuth\Common\Token\Exception\ExpiredTokenException
     *
     * @return array
     */
    public function profileLoader()
    {
        $idTokenString = null;
        $token = $this->service->getStorage()->retrieveAccessToken($this->service->service());
        if ($token instanceof StdOAuth2Token) {
            $idTokenString = array_get($token->getExtraParams(), 'id_token');
        }

        // If we don't have a proper ID token, let's just fetch the data from the API
        if (!$idTokenString) {
            return json_decode($this->service->request(self::USER_PATH), true)['data'];
        }

        if (class_exists(TokenParser::class)) {
            $decoder = new TokenParser(new JoseEncoder());
            $token = $decoder->parse($idTokenString);
            $claims = $token->claims()->all();
        } else {
            $decoder = new Parser();
            $token = $decoder->parse($idTokenString);
            $claims = $token->getClaims();
        }

        return [
            'claims' => $claims,
        ];
    }

    protected function getNormalizersMap()
    {
        return [
            self::FIELD_EMAIL => 'email',
            self::FIELD_FIRST_NAME => 'firstName',
            self::FIELD_LAST_NAME => 'lastName',
            self::FIELD_UNIQUE_ID => 'id',
            self::FIELD_USERNAME => 'username',
        ];
    }

    /**
     * Convert a claim into its raw value.
     *
     * @param \Lcobucci\JWT\Claim|string $claim
     *
     * @return string
     */
    protected function claim($claim = null)
    {
        if (!$claim) {
            return null;
        }

        if (is_string($claim)) {
            return $claim;
        }

        return $claim->getValue();
    }

    /**
     * @var \ArrayAccess|array $data
     * @var string $claimMember
     * @var string $dataMember
     *
     * @return mixed|null
     */
    protected function getNormalizedValue($data, $claimMember, $dataMember)
    {
        if (isset($data['claims'])) {
            return $this->claim(array_get($data, 'claims.' . $claimMember));
        }

        return array_get($data, $dataMember, null);
    }
}
