<?php

namespace KeycloakAuth\Claim\Map\Attribute;

use Concrete\Core\Attribute\Category\UserCategory;
use KeycloakAuth\Claim\Conversion\ConverterFactory;
use KeycloakAuth\Claim\Map\Attribute;

class Factory
{
    /**
     * @param \Concrete\Core\Attribute\Category\UserCategory
     */
    protected $attributeCategory;

    /**
     * @param \KeycloakAuth\Claim\Conversion\ConverterFactory
     */
    protected $converterFactory;

    /**
     * @var \KeycloakAuth\Claim\Map\Attribute[]|null
     */
    private $supportedAttributes;

    public function __construct(UserCategory $attributeCategory, ConverterFactory $converterFactory)
    {
        $this->attributeCategory = $attributeCategory;
        $this->converterFactory = $converterFactory;
    }

    /**
     * @param bool $forceRefresh
     *
     * @return \KeycloakAuth\Claim\Map\Attribute[]
     */
    public function getSupportedAttributes($forceRefresh = false)
    {
        if ($this->supportedAttributes === null || $forceRefresh) {
            $supportedAttributes = [];
            foreach ($this->attributeCategory->getList() as $attributeKey) {
                /** @var \Concrete\Core\Entity\Attribute\Key\UserKey $attributeKey */
                $converters = $this->converterFactory->getConvertersForAttributeType($attributeKey->getAttributeTypeHandle());
                if ($converters !== []) {
                    $supportedAttributes[] = new Attribute($attributeKey, $converters);
                }
            }
            $this->supportedAttributes = $supportedAttributes;
        }

        return $this->supportedAttributes;
    }

    /**
     * @param string $attributeKeyHandle
     *
     * @return \KeycloakAuth\Claim\Map\Attribute|null
     */
    public function getSupportedAttributeByAttributeKeyHandle($attributeKeyHandle)
    {
        foreach ($this->getSupportedAttributes() as $attribute) {
            if ($attribute->getAttributeKeyHandle() === $attributeKeyHandle) {
                return $attribute;
            }
        }

        return null;
    }
}
