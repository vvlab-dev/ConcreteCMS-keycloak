<?php

namespace KeycloakAuth\Claim\Conversion\Converters;

use Concrete\Core\Editor\LinkAbstractor;
use Concrete\Core\Entity\Attribute\Key\Settings\TextareaSettings;
use Concrete\Core\Entity\Attribute\Key\UserKey;
use Concrete\Core\Entity\Attribute\Value\Value\TextValue;
use KeycloakAuth\Claim\Conversion\Converter;

class MultilineText implements Converter
{
    /**
     * {@inheritdoc}
     *
     * @see \KeycloakAuth\Claim\Conversion\Converter::getSupportedAttributeTypes()
     */
    public function getSupportedAttributeTypes()
    {
        return [
            'textarea',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @see \KeycloakAuth\Claim\Conversion\Converter::convertClaimValue()
     */
    public function convertClaimValue(UserKey $attributeKey, $claimValue)
    {
        $string = $this->toString($claimValue);
        if ($string === null) {
            return null;
        }
        $type = $attributeKey->getAttributeKeySettings();
        if ($type instanceof TextareaSettings) {
            switch ($type->getMode()) {
                case 'rich_text':
                    $string = LinkAbstractor::translateTo($string);
                    break;
            }
        }
        $result = new TextValue();
        $result->setValue($string);

        return $result;
    }

    /**
     * @param mixed $claimValue
     *
     * @return string|null
     */
    protected function toString($claimValue)
    {
        switch (gettype($claimValue)) {
            case 'string':
                return $claimValue;
            case 'boolean':
                return $claimValue ? '1' : '0';
            case 'integer':
            case 'double':
                return (string) $claimValue;
        }

        return null;
    }
}
