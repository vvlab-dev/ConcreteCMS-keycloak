<?php

namespace vvLab\KeycloakAuth;

use vvLab\KeycloakAuth\Entity\Server;

interface RealmProvider
{
    /**
     * Do we need the user's email in order to determine the realm to be used?
     *
     * @return bool
     */
    public function isEmailRequired(): bool;

    /**
     * Get the keycloak server to be used with the specified email address.
     * If isEmailRequired() returns false, $email will be an empty.
     */
    public function getServerByEmail(string $email): ?Server;
}
