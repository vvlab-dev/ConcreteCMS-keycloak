<?php

namespace KeycloakAuth\Claim\Conversion\Converters;

use Closure;
use Concrete\Core\Entity\Attribute\Key\UserKey;
use Concrete\Core\Entity\Attribute\Value\Value\AddressValue;
use Concrete\Core\Localization\Localization;
use Concrete\Core\Localization\Service\CountryList;
use Concrete\Core\Localization\Service\StatesProvincesList;
use KeycloakAuth\Claim\Conversion\Converter;
use KeycloakAuth\Claim\Standard\Address as StandardAddressFields;

class Address implements Converter
{
    /**
     * @var string
     */
    const STREET_FIELD = StandardAddressFields::STREET;

    /**
     * @var string
     */
    const CITY_FIELD = StandardAddressFields::CITY;

    /**
     * @var string
     */
    const STATE_PROVINCE_FIELD = StandardAddressFields::STATE_PROVINCE;

    /**
     * @var string
     */
    const ZIPCODE_FIELD = StandardAddressFields::ZIP_CODE;

    /**
     * @var string
     */
    const COUNTY_NAME = StandardAddressFields::COUNTY_NAME;

    /**
     * @var \Concrete\Core\Localization\Localization
     */
    protected $localization;

    /**
     * @var \Concrete\Core\Localization\Service\CountryList
     */
    protected $countryList;

    /**
     * @var \Concrete\Core\Localization\Service\StatesProvincesList
     */
    protected $statesProvincesList;

    public function __construct(Localization $localization, CountryList $countryList, StatesProvincesList $statesProvincesList)
    {
        $this->localization = $localization;
        $this->countryList = $countryList;
        $this->statesProvincesList = $statesProvincesList;
    }

    /**
     * {@inheritdoc}
     *
     * @see \KeycloakAuth\Claim\Conversion\Converter::getSupportedAttributeTypes()
     */
    public function getSupportedAttributeTypes()
    {
        return [
            'address',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @see \KeycloakAuth\Claim\Conversion\Converter::convertClaimValue()
     */
    public function convertClaimValue(UserKey $attributeKey, $claimValue)
    {
        if (is_object($claimValue)) {
            $claimValue = (array) $claimValue;
        } elseif (!is_array($claimValue)) {
            return null;
        }
        $streetLines = preg_split('/\s*[\r\n]\s*/', $this->extractString($claimValue, static::STREET_FIELD));
        $city = $this->extractString($claimValue, static::CITY_FIELD);
        $stateProvinceName = $this->extractString($claimValue, static::STATE_PROVINCE_FIELD);
        $zipCode = $this->extractString($claimValue, static::ZIPCODE_FIELD);
        $countryName = $this->extractString($claimValue, static::COUNTY_NAME);
        if ($streetLines === [] && $city === '' && $stateProvinceName === '' && $zipCode === '' && $countryName !== '') {
            return null;
        }
        $countryCode = $this->countryNameToCode($countryName);
        $stateProvinceCode = $this->stateProvinceNameToCode($countryCode, $stateProvinceName);
        $result = new AddressValue();
        if (isset($streetLines[0])) {
            $result->setAddress1($streetLines[0]);
            if (isset($streetLines[1])) {
                $result->setAddress2($streetLines[1]);
                if (isset($streetLines[3])) {
                    $result->setAddress3($streetLines[2]);
                }
            }
        }
        if ($city !== '') {
            $result->setCity($city);
        }
        if ($stateProvinceCode !== '') {
            $result->setStateProvince($stateProvinceCode);
        } elseif ($stateProvinceName !== '') {
            $result->setStateProvince($stateProvinceName);
        }
        if ($zipCode !== '') {
            $result->setPostalCode($zipCode);
        }
        if ($countryCode !== '') {
            $result->setCountry($countryCode);
        } elseif ($countryName !== '') {
            $result->setCountry($countryName);
        }

        return $result;
    }

    /**
     * @param array $raw
     * @param string $key
     *
     * @return string
     */
    protected function extractString(array $raw, $key)
    {
        if (!isset($raw[$key])) {
            return '';
        }
        $value = $raw[$key];
        switch (gettype($value)) {
            case 'string':
                return $value;
            case 'integer':
            case 'double':
                return (string) $value;
        }

        return '';
    }

    /**
     * @param string $countryName
     *
     * @return string
     */
    protected function countryNameToCode($countryName)
    {
        if (($countryName = trim($countryName)) === '') {
            return '';
        }
        if (strlen($countryName) === 2) {
            $countryCode = strtoupper($countryName);
            $countries = $this->countryList->getCountries();

            return isset($countries[$countryCode]) ? $countryCode : '';
        }
        $countryName = mb_strtolower($countryName);
        $countryCode = $this->iterateAllUsedLocales(
            function () use ($countryName) {
                $countries = $this->countryList->getCountries();
                $code = array_search($countryName, array_map('mb_strtolower', $countries), true);

                return $code === false ? null : $code;
            }
        );

        return $countryCode === null ? '' : $countryCode;
    }

    /**
     * @param string $countryCode
     * @param string $stateProvinceName
     * @param string|mixed $onNotFound
     *
     * @return string|mixed
     */
    protected function stateProvinceNameToCode($countryCode, $stateProvinceName, $onNotFound = '')
    {
        if ($countryCode === '' || ($stateProvinceName = trim($stateProvinceName)) === '') {
            return $onNotFound;
        }
        $stateProvinceName = mb_strtoupper($stateProvinceName);
        $stateProvinceCode = $this->iterateAllUsedLocales(
            function () use ($countryCode, $stateProvinceName) {
                $statesProvinces = $this->statesProvincesList->getStateProvinceArray($countryCode);
                if ($statesProvinces === null) {
                    return null;
                }
                if (isset($statesProvinces[$stateProvinceName])) {
                    return $stateProvinceName;
                }
                $code = array_search($stateProvinceName, array_map('mb_strtoupper', $statesProvinces), true);

                return $code === false ? null : $code;
            }
        );

        return $stateProvinceCode === null ? '' : $stateProvinceCode;
    }

    protected function iterateAllUsedLocales(Closure $callback)
    {
        $originalLocale = $this->localization->getLocale();
        foreach ($this->getAllUsedLocales() as $locale) {
            if ($locale !== $originalLocale) {
                $this->localization->setLocale($locale);
            }
            try {
                $result = $callback();
            } finally {
                if ($locale !== $originalLocale) {
                    $this->localization->setLocale($originalLocale);
                }
            }
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    protected function getAllUsedLocales()
    {
        $localization = $this->localization;

        return array_values(
            array_unique(
                array_merge(
                    [$localization->getLocale()],
                    $localization->getAvailableInterfaceLanguages(),
                    [$localization::BASE_LOCALE]
                ),
                SORT_STRING
            )
        );
    }
}
