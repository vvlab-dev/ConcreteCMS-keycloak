<?php

namespace KeycloakAuth\Claim\Map;

use Concrete\Core\Entity\Attribute\Key\UserKey;
use Concrete\Core\User\UserInfo;

class Attribute
{
    /**
     * @var \Concrete\Core\Entity\Attribute\Key\UserKey
     */
    protected $attributeKey;

    /**
     * @param \KeycloakAuth\Claim\Conversion\Converter[]
     */
    protected $converters;

    /**
     * @param \KeycloakAuth\Claim\Conversion\Converter[] $converters
     */
    public function __construct(UserKey $attributeKey, array $converters)
    {
        $this->attributeKey = $attributeKey;
        $this->converters = $converters;
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->attributeKey->getAttributeKeyDisplayName('text');
    }

    /**
     * @return string
     */
    public function getAttributeKeyHandle()
    {
        return $this->attributeKey->getAttributeKeyHandle();
    }

    public function mapValue(UserInfo $user, $claimValue)
    {
        $value = null;
        foreach ($this->converters as $converter) {
            $value = $converter->convertClaimValue($this->attributeKey, $claimValue);
            if ($value !== null) {
                break;
            }
        }
        if ($value === null) {
            return false;
        }
        $user->setAttribute($this->attributeKey, $value);

        return true;
    }
}
