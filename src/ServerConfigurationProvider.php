<?php

namespace vvLab\KeycloakAuth;

interface ServerConfigurationProvider
{
    /**
     * Do we need the user's email in order to determine the server/realm to be used?
     *
     * @return bool
     */
    public function isEmailRequired();

    /**
     * Get the server/realm configuration to be used with the specified email address.
     * If isEmailRequired() returns false, $email will be an empty string.
     *
     * @param string $email
     *
     * @return \vvLab\KeycloakAuth\ServerConfiguration|null
     */
    public function getServerConfigurationByEmail($email);

    /**
     * Get the server/realm configuration given its handle.
     *
     * @param string $handle
     *
     * @return \vvLab\KeycloakAuth\ServerConfiguration|null
     */
    public function getServerConfigurationByHandle($handle);
}
