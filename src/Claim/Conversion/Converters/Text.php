<?php

namespace vvLab\KeycloakAuth\Claim\Conversion\Converters;

use Concrete\Core\Entity\Attribute\Key\UserKey;
use Concrete\Core\Entity\Attribute\Value\Value\TextValue;

class Text extends MultilineText
{
    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\Claim\Conversion\Converter::getSupportedAttributeTypes()
     */
    public function getSupportedAttributeTypes()
    {
        return [
            'text',
            'telephone',
            'url',
            'email',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\Claim\Conversion\Converter::convertClaimValue()
     */
    public function convertClaimValue(UserKey $attributeKey, $claimValue)
    {
        $string = $this->toString($claimValue);
        if ($string === null || strpbrk($string, "\r\n") !== false) {
            return null;
        }
        $result = new TextValue();
        $result->setValue($string);

        return $result;
    }
}
