<?php

namespace KeycloakAuth\Claim\Map;

use KeycloakAuth\Extractor;
use Punic\Comparer;
use ReflectionClass;

class Field
{
    public static function getUsedFields()
    {
        return [
            Extractor::FIELD_UNIQUE_ID,
            Extractor::FIELD_EMAIL,
            Extractor::FIELD_VERIFIED_EMAIL,
            Extractor::FIELD_FIRST_NAME,
            Extractor::FIELD_LAST_NAME,
            Extractor::FIELD_FULL_NAME,
        ];
    }

    public static function getDictionary()
    {
        $dictionary = [];
        $class = new ReflectionClass(Extractor::class);
        foreach ($class->getConstants() as $name => $value) {
            if (strlen($name) > strlen('FIELD_') && strpos($name, 'FIELD_') === 0 && is_string($value)) {
                $dictionary[$value] = static::getFieldDescription($value);
            }
        }
        $cmp = new Comparer();
        $cmp->sort($dictionary, true);

        return $dictionary;
    }

    /**
     * @param string $field
     *
     * @return $field
     */
    public static function getFieldDescription($field)
    {
        $field = (string) $field;
        switch ($field) {
            case Extractor::FIELD_UNIQUE_ID:
                return t('Unique ID');
            case Extractor::FIELD_USERNAME:
                return t('Username');
            case Extractor::FIELD_FIRST_NAME:
                return t('First Name');
            case Extractor::FIELD_LAST_NAME:
                return t('Last Name');
            case Extractor::FIELD_FULL_NAME:
                return t('Full Name');
            case Extractor::FIELD_EMAIL:
                return t('Email');
            case Extractor::FIELD_LOCATION:
                return t('Location');
            case Extractor::FIELD_DESCRIPTION:
                return t('Description');
            case Extractor::FIELD_IMAGE_URL:
                return t('Image URL');
            case Extractor::FIELD_PROFILE_URL:
                return t('Profile URL');
            case Extractor::FIELD_WEBSITES:
                return t('Websites');
            case Extractor::FIELD_VERIFIED_EMAIL:
                return t('Email Verified');
        }

        return $field;
    }
}
