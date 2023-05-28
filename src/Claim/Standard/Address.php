<?php

namespace KeycloakAuth\Claim\Standard;

/**
 * @see https://openid.net/specs/openid-connect-core-1_0.html#AddressClaim
 */
class Address
{
    /**
     * Full mailing address, formatted for display or use on a mailing label.
     * This field MAY contain multiple lines, separated by newlines.
     * Newlines can be represented either as a carriage return/line feed pair ("\r\n") or as a single line feed character ("\n").
     *
     * @var string
     */
    const FULL = 'formatted';

    /**
     * Full street address component, which MAY include house number, street name, Post Office Box, and multi-line extended street address information.
     * This field MAY contain multiple lines, separated by newlines.
     * Newlines can be represented either as a carriage return/line feed pair ("\r\n") or as a single line feed character ("\n").
     *
     * @var string
     */
    const STREET = 'street_address';

    /**
     * City or locality component.
     *
     * @var string
     */
    const CITY = 'locality';

    /**
     * State, province, prefecture, or region component.
     *
     * @var string
     */
    const STATE_PROVINCE = 'region';

    /**
     * Zip code or postal code component.
     *
     * @var string
     */
    const ZIP_CODE = 'postal_code';

    /**
     * Country name component.
     *
     * @var string
     */
    const COUNTY_NAME = 'country';
}
