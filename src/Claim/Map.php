<?php

namespace KeycloakAuth\Claim;

use Concrete\Core\Attribute\Category\UserCategory;
use Concrete\Core\Error\ErrorList\ErrorList;
use JsonSerializable;
use KeycloakAuth\Claim\Map\Attribute;
use KeycloakAuth\Extractor;

class Map implements JsonSerializable
{
    /**
     * Array keys are keys of the KeycloakAuth\Extractor::FIELD_... constants, array values are the claim names.
     *
     * @var string[]
     */
    private $fields = [];

    /**
     * Array keys are the claim names, array values are the list mapped attributes.
     *
     * @var \KeycloakAuth\Claim\Map\Attribute[][]
     */
    private $attributes = [];

    /**
     * @param string $field
     * @param string|null $claimName set to NULL or an empty string to unmap the field
     *
     * @return $this
     */
    public function mapField($field, $claimName)
    {
        $field = (string) $field;
        if (is_string($claimName) && $claimName !== '') {
            $this->fields[$field] = $claimName;
        } else {
            unset($this->fields[$field]);
        }

        return $this;
    }

    /**
     * @param string $claimName
     * @param \KeycloakAuth\Claim\Map\Attribute[] $attributes
     *
     * @return $this
     */
    public function setAttributesForClaim($claimName, array $attributes)
    {
        $claimName = (string) $claimName;
        unset($this->attributes[$claimName]);
        foreach ($attributes as $attribute) {
            $this->addAttributeForClaim($claimName, $attribute);
        }

        return $this;
    }

    /**
     * @param string $claimName
     *
     * @return $this
     */
    public function addAttributeForClaim($claimName, Attribute $attribute)
    {
        $claimName = (string) $claimName;
        if (isset($this->attributes[$claimName])) {
            $this->attributes[$claimName][$attribute->getAttributeKeyHandle()] = $attribute;
        } else {
            $this->attributes[$claimName] = [$attribute->getAttributeKeyHandle() => $attribute];
        }

        return $this;
    }

    /**
     * @param string $field
     *
     * @return string empty string if none
     */
    public function getClaimNameForField($field)
    {
        return isset($this->fields[$field]) ? $this->fields[$field] : '';
    }

    /**
     * @return \Generator|array keys are the claim names, values are arrays of \KeycloakAuth\Claim\Map\Attribute
     */
    public function getAttributeList()
    {
        foreach ($this->attributes as $claimName => $attributes) {
            yield $claimName => array_values($attributes);
        }
    }

    /**
     * @return \KeycloakAuth\Claim\Map
     */
    public static function createDefaultMap()
    {
        $result = new static();
        $result
            ->mapField(Extractor::FIELD_UNIQUE_ID, Standard::USER_IDENTIFIER)
            ->mapField(Extractor::FIELD_EMAIL, Standard::EMAIL)
            ->mapField(Extractor::FIELD_VERIFIED_EMAIL, Standard::EMAIL_VERIFIED)
            ->mapField(Extractor::FIELD_FIRST_NAME, Standard::FIRST_NAME)
            ->mapField(Extractor::FIELD_LAST_NAME, Standard::LAST_NAME)
            ->mapField(Extractor::FIELD_FULL_NAME, Standard::FULL_NAME)
        ;

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        $result = [];
        if ($this->fields !== []) {
            $result['fields'] = $this->fields;
        }
        if ($this->attributes !== []) {
            $result['attributes'] = [];
            foreach ($this->attributes as $claimName => $attributes) {
                foreach ($attributes as $attribute) {
                    $result['attributes'][] = [
                        'claim' => $claimName,
                        'attribute' => $attribute->getAttributeKeyHandle(),
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * @param bool $webSafe
     *
     * @return string
     */
    public function serialize($webSafe = true)
    {
        $arr = $this->jsonSerialize();
        if ($arr === []) {
            return '{}';
        }
        $flags = 0;
        if ($webSafe) {
            $flags |= JSON_UNESCAPED_LINE_TERMINATORS | JSON_UNESCAPED_SLASHES;
        }

        return json_encode($arr, $flags);
    }

    /**
     * @param string|array|mixed $data
     * @param \Concrete\Core\Error\ErrorList\ErrorList|null $warnings
     *
     * @return \KeycloakAuth\Claim\Map|null NULL in case of errors
     */
    public static function unserialize($data, ErrorList $warnings = null)
    {
        switch (gettype($data)) {
            case 'string':
                return static::unserializeString($data, $warnings);
            case 'array':
                return static::unserializeArray($data, $warnings);
            case 'NULL':
                if ($warnings !== null) {
                    $warnings->add(t('No data to be unserialized'));
                }

                return static::unserializeArray($data, $warnings);
            default:
                if ($warnings !== null) {
                    $warnings->add(t('Invalid serialized data'));
                }

                return null;
        }
    }

    /**
     * @param string $string
     * @param \Concrete\Core\Error\ErrorList\ErrorList|null $warnings
     *
     * @return \KeycloakAuth\Claim\Map|null NULL in case
     */
    protected static function unserializeString($string, ErrorList $warnings = null)
    {
        if ($string === '' || $string === 'null') {
            if ($warnings !== null) {
                $warnings->add(t('No data to be unserialized'));
            }

            return null;
        }
        set_error_handler(static function () {}, -1);
        try {
            $data = json_decode($string, true);
        } finally {
            restore_error_handler();
        }
        if ($data === null) {
            if ($warnings !== null) {
                $warnings->add(t('Error in JSON string.'));
            }

            return null;
        }
        if (!is_array($data)) {
            if ($warnings !== null) {
                $warnings->add(t('Invalid serialized data'));
            }

            return null;
        }

        return static::unserializeArray($data, $warnings);
    }

    protected static function unserializeArray(array $data, ErrorList $warnings = null)
    {
        $app = app();
        if ($warnings === null) {
            $warnings = $app->make(ErrorList::class);
        }
        $result = $app->make(static::class);
        if (isset($data['fields']) && is_array($data['fields'])) {
            $fieldsDictionary = Map\Field::getDictionary();
            foreach ($data['fields'] as $field => $claimName) {
                if (!is_string($field) || $field === '') {
                    continue;
                }
                if (!isset($fieldsDictionary[$field])) {
                    $warnings->add(t('Unrecognized field: %s', "{$field}"));
                    continue;
                }
                $result->mapField($field, $claimName);
            }
        }
        if (isset($data['attributes']) && is_array($data['attributes']) && $data['attributes'] !== []) {
            $alreadySeenAttributes = [];
            $userAttributeCategory = $app->make(UserCategory::class);
            $factory = $app->make(Map\Attribute\Factory::class);
            foreach ($data['attributes'] as $info) {
                if (!is_array($info)) {
                    continue;
                }
                if (!isset($info['claim']) || !is_string($info['claim']) || $info['claim'] === '') {
                    continue;
                }
                if (!isset($info['attribute']) || !is_string($info['attribute']) || $info['attribute'] === '') {
                    continue;
                }
                $userAttributeKey = $userAttributeCategory->getAttributeKeyByHandle($info['attribute']);
                if ($userAttributeKey === null) {
                    $warnings->add(t('Unable to find the user attribute with handle %s', "'{$info['attribute']}'"));
                    continue;
                }
                $attribute = $factory->getSupportedAttributeByAttributeKeyHandle($info['attribute']);
                if ($attribute === null) {
                    $warnings->add(t('The user attribute with handle %s is not supported', "'{$info['attribute']}'"));
                    continue;
                }
                if (in_array($attribute->getAttributeKeyHandle(), $alreadySeenAttributes, true)) {
                    $warnings->add(t('The user attribute with handle %s has already been mapped', "'{$info['attribute']}'"));
                    continue;
                }
                $alreadySeenAttributes[] = $attribute->getAttributeKeyHandle();
                $result->addAttributeForClaim($info['claim'], $attribute);
            }
        }
        $acceptable = true;
        if (!isset($result->fields[Extractor::FIELD_UNIQUE_ID])) {
            $warnings->add(t('Missing required field: %s', Map\Field::getFieldDescription(Extractor::FIELD_UNIQUE_ID)));
            $acceptable = false;
        }
        if (!isset($result->fields[Extractor::FIELD_EMAIL])) {
            $warnings->add(t('Missing required field: %s', Map\Field::getFieldDescription(Extractor::FIELD_EMAIL)));
            $acceptable = false;
        }

        return $acceptable ? $result : null;
    }
}
