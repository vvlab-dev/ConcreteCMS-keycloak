<?php

namespace vvLab\KeycloakAuth\Claim\Conversion\Converters;

use Concrete\Core\Entity\Attribute\Key\UserKey;
use Concrete\Core\Entity\Attribute\Value\Value\BooleanValue;
use vvLab\KeycloakAuth\Claim\Conversion\Converter;

class Boolean implements Converter
{
    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\Claim\Conversion\Converter::getSupportedAttributeTypes()
     */
    public function getSupportedAttributeTypes()
    {
        return [
            'boolean',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\Claim\Conversion\Converter::convertClaimValue()
     */
    public function convertClaimValue(UserKey $attributeKey, $claimValue)
    {
        $bool = $this->toBoolean($claimValue);
        if ($bool === null) {
            return null;
        }
        $result = new BooleanValue();
        $result->setValue($bool);

        return $result;
    }

    /**
     * @param mixed $claimValue
     *
     * @return bool|null
     */
    protected function toBoolean($claimValue)
    {
        switch (gettype($claimValue)) {
            case 'boolean':
                return $claimValue;
            case 'integer':
                if ($claimValue === 0) {
                    return false;
                }
                if ($claimValue === 1 || $claimValue === -1) {
                    return true;
                }

                return null;
            case 'double':
                if ($claimValue === 0.) {
                    return false;
                }
                if ($claimValue === 1. || $claimValue === -1.) {
                    return true;
                }

                return null;
            case 'string':
                $lc = strtolower($claimValue);
                if (in_array($lc, ['true', 't', 'yes', 'y', 'on', '1', '-1'], true)) {
                    return true;
                }
                if (in_array($lc, ['false', 'f', 'no', 'n', 'off', '0'], true)) {
                    return false;
                }

                return null;
        }

        return null;
    }
}
