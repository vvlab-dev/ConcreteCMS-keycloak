<?php

namespace KeycloakAuth\Claim\Conversion\Converters;

use Concrete\Core\Entity\Attribute\Key\UserKey;
use Concrete\Core\Entity\Attribute\Value\Value\NumberValue;
use KeycloakAuth\Claim\Conversion\Converter;

class Number implements Converter
{
    /**
     * {@inheritdoc}
     *
     * @see \KeycloakAuth\Claim\Conversion\Converter::getSupportedAttributeTypes()
     */
    public function getSupportedAttributeTypes()
    {
        return [
            'number',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @see \KeycloakAuth\Claim\Conversion\Converter::convertClaimValue()
     */
    public function convertClaimValue(UserKey $attributeKey, $claimValue)
    {
        $number = $this->toNumber($claimValue);
        if ($number === null) {
            return null;
        }
        $result = new NumberValue();
        $result->setValue($number);

        return $result;
    }

    /**
     * @param mixed $claimValue
     *
     * @return int|float|null
     */
    protected function toNumber($claimValue)
    {
        switch (gettype($claimValue)) {
            case 'integer':
            case 'double':
                return $claimValue;
            case 'boolean':
                return $claimValue ? 1 : 0;
            case 'string':
                if (preg_match('/^[+\-]?\d+$/ms', $claimValue)) {
                    return (int) $claimValue;
                }
                if (is_numeric($claimValue)) {
                    return (float) $claimValue;
                }

                return null;

                return null;
        }

        return null;
    }
}
