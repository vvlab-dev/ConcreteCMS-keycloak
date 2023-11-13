<?php

namespace vvLab\KeycloakAuth\RealmProvider;

use vvLab\KeycloakAuth\Entity\Server;
use vvLab\KeycloakAuth\RealmProvider;

final class DefaultRealmProvider implements RealmProvider
{
    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\RealmProvider::isEmailRequired()
     */
    public function isEmailRequired(): bool
    {
        throw new \RuntimeException('@todo');
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\RealmProvider::getServerByEmail()
     */
    public function getServerByEmail(string $email): ?Server
    {
        throw new \RuntimeException('@todo');
    }
}
