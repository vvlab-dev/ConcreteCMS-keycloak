<?php

namespace vvLab\KeycloakAuth\Claim;

use Punic\Comparer;
use ReflectionClass;

/**
 * @see https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
 */
class Standard
{
    /**
     * Issuer Identifier for the Issuer of the response.
     *
     * @var string
     */
    const ISSUER_IDENTIFIER = 'iss';

    /**
     * Audiences that the response is intended for.
     *
     * @var string
     */
    const AUDIENCE = 'aud';

    /**
     * Expiration time on or after which the response must be refused.
     *
     * @var string
     */
    const EXPIRATION_TIME = 'exp';

    /**
     * Time at which the response was issued.
     *
     * @var string
     */
    const ISSUED_AT = 'iat';

    /**
     * Time when the user authentication occurred.
     *
     * @var string
     */
    const AUTH_TIME = 'auth_time';

    /**
     * Value used to associate a client session with a response.
     *
     * @var string
     */
    const NONCE = 'nonce';

    /**
     * Hash of the access token.
     *
     * @var string
     */
    const AT_HASH = 'at_hash';

    /**
     * Authentication context class reference.
     *
     * @var string
     */
    const AUTH_CONTEXT_REFERENCE = 'acr';

    /**
     * Authentication methods references.
     *
     * @var string
     */
    const AUTH_METHODS_REFERENCE = 'amr';

    /**
     * Authorized party.
     *
     * @var string
     */
    const AUTHORIZED_PARTY = 'azp';

    /**
     * Subject - Identifier for the End-User at the Issuer.
     *
     * @var string
     */
    const USER_IDENTIFIER = 'sub';

    /**
     * End-User's full name in displayable form including all name parts, possibly including titles and suffixes, ordered according to the End-User's locale and preferences.
     *
     * @var string
     */
    const FULL_NAME = 'name';

    /**
     * Given name(s) or first name(s) of the End-User.
     * Note that in some cultures, people can have multiple given names; all can be present, with the names being separated by space characters.
     *
     * @var string
     */
    const FIRST_NAME = 'given_name';

    /**
     * Surname(s) or last name(s) of the End-User.
     * Note that in some cultures, people can have multiple family names or no family name; all can be present, with the names being separated by space characters.
     *
     * @var string
     */
    const LAST_NAME = 'family_name';

    /**
     * Middle name(s) of the End-User.
     * Note that in some cultures, people can have multiple middle names; all can be present, with the names being separated by space characters.
     * Also note that in some cultures, middle names are not used.
     *
     * @var string
     */
    const MIDDLE_NAME = 'middle_name';

    /**
     * Casual name of the End-User that may or may not be the same as the given_name.
     * For instance, a nickname value of Mike might be returned alongside a given_name value of Michael.
     *
     * @var string
     */
    const NICKNAME = 'nickname';

    /**
     * Shorthand name by which the End-User wishes to be referred to at the RP, such as janedoe or j.doe.
     * This value MAY be any valid JSON string including special characters such as @, /, or whitespace.
     * The RP MUST NOT rely upon this value being unique.
     *
     * @var string
     */
    const PREFERRED_USERNAME = 'preferred_username';

    /**
     * URL of the End-User's profile page.
     * The contents of this Web page SHOULD be about the End-User.
     *
     * @var string
     */
    const PROFILE_URL = 'profile';

    /**
     * URL of the End-User's profile picture.
     * This URL MUST refer to an image file (for example, a PNG, JPEG, or GIF image file), rather than to a Web page containing an image.
     * Note that this URL SHOULD specifically reference a profile photo of the End-User suitable for displaying when describing the End-User, rather than an arbitrary photo taken by the End-User.
     *
     * @var string
     */
    const PICTURE_URL = 'picture';

    /**
     * URL of the End-User's Web page or blog.
     * This Web page SHOULD contain information published by the End-User or an organization that the End-User is affiliated with.
     *
     * @var string
     */
    const WEBSITE_URL = 'website';

    /**
     * End-User's preferred e-mail address.
     * Its value MUST conform to the RFC 5322 addr-spec syntax.
     * The RP MUST NOT rely upon this value being unique.
     *
     * @var string
     */
    const EMAIL = 'email';

    /**
     * True if the End-User's e-mail address has been verified; otherwise false.
     * When this Claim Value is true, this means that the OP took affirmative steps to ensure that this e-mail address was controlled by the End-User at the time the verification was performed.
     * The means by which an e-mail address is verified is context-specific, and dependent upon the trust framework or contractual agreements within which the parties are operating.
     *
     * @var string
     */
    const EMAIL_VERIFIED = 'email_verified';

    /**
     * End-User's gender.
     * Values defined by this specification are female and male.
     * Other values MAY be used when neither of the defined values are applicable.
     *
     * @var string
     */
    const GENDER = 'gender';

    /**
     * End-User's birthday, represented as an ISO 8601:2004 YYYY-MM-DD format.
     * The year MAY be 0000, indicating that it is omitted.
     * To represent only the year, YYYY format is allowed.
     * Note that depending on the underlying platform's date related function, providing just year can result in varying month and day, so the implementers need to take this factor into account to correctly process the dates.
     *
     * @var string
     */
    const BIRTH_DATE = 'birthdate';

    /**
     * String from zoneinfo time zone database representing the End-User's time zone.
     * For example, Europe/Paris or America/Los_Angeles.
     *
     * @var string
     */
    const TIMEZONE_ID = 'zoneinfo';

    /**
     * End-User's locale, represented as a BCP47 language tag.
     * This is typically an ISO 639-1 Alpha-2 language code in lowercase and an ISO 3166-1 Alpha-2 country code in uppercase, separated by a dash.
     * For example, en-US or fr-CA.
     * As a compatibility note, some implementations have used an underscore as the separator rather than a dash, for example, en_US; Relying Parties MAY choose to accept this locale syntax as well.
     *
     * @var string
     */
    const LOCALE_ID = 'locale';

    /**
     * End-User's preferred telephone number.
     * E.164 is RECOMMENDED as the format of this Claim, for example, +1 (425) 555-1212 or +56 (2) 687 2400.
     * If the phone number contains an extension, it is RECOMMENDED that the extension be represented using the RFC 3966 extension syntax, for example, +1 (604) 555-1234;ext=5678.
     *
     * @var string
     */
    const PHONE_NUMBER = 'phone_number';

    /**
     * True if the End-User's phone number has been verified; otherwise false.
     * When this Claim Value is true, this means that the OP took affirmative steps to ensure that this phone number was controlled by the End-User at the time the verification was performed.
     * The means by which a phone number is verified is context-specific, and dependent upon the trust framework or contractual agreements within which the parties are operating.
     * When true, the phone_number Claim MUST be in E.164 format and any extensions MUST be represented in RFC 3966 format.
     *
     * @var string
     */
    const PHONE_NUMBER_VERIFIED = 'phone_number_verified';

    /**
     * End-User's preferred postal address.
     * The value of the address member is a JSON structure containing some or all of the members defined in the vvLab\KeycloakAuth\Claim\Standard\Address class.
     *
     * @see \vvLab\KeycloakAuth\Claim\Standard\Address
     *
     * @var string
     */
    const ADDRESS = 'address';

    /**
     * Time the End-User's information was last updated.
     * Its value is a number representing the number of seconds from 1970-01-01T0:0:0Z as measured in UTC until the date/time.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * Unique identifier for the JWT token.
     *
     * @var string
     */
    const JWT_ID = 'jti';

    /**
     * Media type of the JWT token.
     *
     * @var string
     */
    const JWT_MEDIATYPE = 'typ';

    /**
     * Unique identifier of the user session.
     *
     * @var string
     */
    const SESSION_ID = 'sid';

    /**
     * Session state.
     *
     * @var string
     */
    const SESSION_STATE = 'session_state';

    public static function getDictionary()
    {
        $dictionary = [];
        $class = new ReflectionClass(self::class);
        foreach ($class->getConstants() as $value) {
            if (is_string($value)) {
                $dictionary[$value] = static::getClaimDescription($value, $value);
            }
        }
        $cmp = new Comparer();
        $cmp->sort($dictionary, true);

        return $dictionary;
    }

    /**
     * @param string $claim
     * @param string|null $onUnrecognized
     *
     * @return string|null
     */
    public static function getClaimDescription($claim, $onUnrecognized = null)
    {
        $claim = is_string($claim) ? (string) $claim : '';
        switch ($claim) {
            case static::ISSUER_IDENTIFIER:
                return t('Issuer identifier for the issuer');
            case static::AUDIENCE:
                return t('Audiences that the response is intended for');
            case static::EXPIRATION_TIME:
                return t('Expiration time on or after which the response must be refused');
            case static::ISSUED_AT:
                return t('Time at which the response was issued');
            case static::AUTH_TIME:
                return t('Time when the user authentication occurred');
            case static::NONCE:
                return t('Value used to associate a client session with a response');
            case static::AT_HASH:
                return t('Hash of the access token');
            case static::AUTH_CONTEXT_REFERENCE:
                return t('Authentication context class reference');
            case static::AUTH_METHODS_REFERENCE:
                return t('Authentication methods references');
            case static::AUTHORIZED_PARTY:
                return t('Authorized party');
            case static::USER_IDENTIFIER:
                return t('User identifier at the issuer');
            case static::FULL_NAME:
                return t('User full name');
            case static::FIRST_NAME:
                return t('User first name');
            case static::LAST_NAME:
                return t('User last name');
            case static::MIDDLE_NAME:
                return t('User middle name');
            case static::NICKNAME:
                return t('User nickname');
            case static::PREFERRED_USERNAME:
                return t('Preferred username');
            case static::PROFILE_URL:
                return t('URL of the user profile page');
            case static::PICTURE_URL:
                return t('URL of the user profile picture');
            case static::WEBSITE_URL:
                return t('URL of the user page or blog');
            case static::EMAIL:
                return t('Preferred user email');
            case static::EMAIL_VERIFIED:
                return t('User email has been verified');
            case static::GENDER:
                return t('User gender');
            case static::BIRTH_DATE:
                return t('User birthday');
            case static::TIMEZONE_ID:
                return t('User time zone');
            case static::LOCALE_ID:
                return t('User locale');
            case static::PHONE_NUMBER:
                return t('User preferred phone number');
            case static::PHONE_NUMBER_VERIFIED:
                return t('User phone number has been verified');
            case static::ADDRESS:
                return t('User postal address');
            case static::UPDATED_AT:
                return t("Time the user's information was last updated");
            case static::JWT_ID:
                return t('Unique identifier for the JWT token');
            case static::JWT_MEDIATYPE:
                return t('Media type of the JWT token');
            case static::SESSION_ID:
                return t('Unique identifier of the user session');
            case static::SESSION_STATE:
                return t('Session state');
        }

        return $onUnrecognized;
    }
}
