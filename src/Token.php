<?php

namespace KeycloakAuth;

use OAuth\OAuth2\Token\StdOAuth2Token;

/**
 * @see https://datatracker.ietf.org/doc/html/rfc6749#section-5.1
 * @see https://openid.net/specs/openid-connect-core-1_0.html#rfc.section.3.1.3.3
 */
class Token extends StdOAuth2Token
{
    const EXTRAPARAMKEY_ID_TOKEN = 'id_token';

    const EXTRAPARAMKEY_SCOPE = 'scope';

    const EXTRAPARAMKEY_REFRESHLIFETIME = 'refresh_expires_in';

    const EXTRAPARAMKEY_NOTBEFOREPOLICY = 'not-before-policy';

    const EXTRAPARAMKEY_SESSIONSTATE = 'session_state';

    /**
     * Required.
     *
     * @return string
     */
    public function getIDToken()
    {
        $value = $this->getExtraParamByKey(static::EXTRAPARAMKEY_ID_TOKEN);

        return is_string($value) ? $value : '';
    }

    /**
     * Optional.
     *
     * @return string[] Empty array if not available
     */
    public function getScopes()
    {
        $value = $this->getExtraParamByKey(static::EXTRAPARAMKEY_SCOPE);

        return is_string($value) ? preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY) : [];
    }

    /**
     * Optional, seen in Keycloak responses.
     *
     * @return int|null
     */
    public function getRefreshLifetime()
    {
        $value = $this->getExtraParamByKey(static::EXTRAPARAMKEY_REFRESHLIFETIME);

        return is_int($value) ? $value : null;
    }

    /**
     * Optional, seen in Keycloak responses.
     *
     * @return int|null NULL if not available
     */
    public function getNotBeforePolicy()
    {
        $value = $this->getExtraParamByKey(static::EXTRAPARAMKEY_NOTBEFOREPOLICY);

        return is_int($value) ? $value : null;
    }

    /**
     * Optional, seen in Keycloak responses.
     *
     * @return string Empty string if not available
     */
    public function getSessionState()
    {
        $value = $this->getExtraParamByKey(static::EXTRAPARAMKEY_SESSIONSTATE);

        return is_string($value) ? $value : '';
    }

    /**
     * @param string $key
     * @param mixed $onNotFound
     *
     * @return mixed|null
     */
    protected function getExtraParamByKey($key, $onNotFound = null)
    {
        $extraParams = $this->getExtraParams();

        return array_key_exists($key, $extraParams) ? $extraParams[$key] : $onNotFound;
    }
}
