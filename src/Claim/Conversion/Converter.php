<?php

namespace KeycloakAuth\Claim\Conversion;

use Concrete\Core\Entity\Attribute\Key\UserKey;

interface Converter
{
    /**
     * Get the handles of the of supported attribute types.
     *
     * @return string[]
     */
    public function getSupportedAttributeTypes();

    /**
     * Convert the value of a claim to an attribute value.
     *
     * @param mixed $claimValue
     *
     * @return \Concrete\Core\Entity\Attribute\Value\Value\AbstractValue|null return NULL if it's not possible to convert $claimValue
     */
    public function convertClaimValue(UserKey $attributeKey, $claimValue);
}
