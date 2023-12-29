<?php

namespace vvLab\KeycloakAuth;

interface ServerConfiguration
{
    /**
     * Get a handle that uniquely identifies this server/realm configuration.
     *
     * @return bool
     */
    public function getHandle();

    /**
     * Get the realm root URL.
     *
     * @return string
     */
    public function getRealmRootUrl();

    /**
     * Get the client ID.
     *
     * @return string
     */
    public function getClientID();

    /**
     * Get the client secret.
     *
     * @return string
     */
    public function getClientSecret();

    /**
     * Get URL of the the Authorization API endpoint.
     *
     * @return string
     */
    public function getAuthorizationEndpointUrl();

    /**
     * Get URL of the Get Access Token API endpoint.
     *
     * @return string
     */
    public function getAccessTokenEndpointUrl();

    /**
     * Get URL of the End Session API endpoint.
     *
     * @return string empty string if not supported or available
     */
    public function getEndSessionEndpointUrl();

    /**
     * Is new users registration enabled?
     *
     * @return bool
     */
    public function isRegistrationEnabled();

    /**
     * Get the ID of the group to be assigned to new users.
     *
     * @return int|null
     */
    public function getRegistrationGroupID();

    /**
     * Get the claim map to be used.
     *
     * @return \vvLab\KeycloakAuth\Claim\Map
     */
    public function getClaimMap();

    /**
     * Logout from server too when logging out from Concrete?
     *
     * @return bool
     */
    public function isLogoutOnLogout();
}
