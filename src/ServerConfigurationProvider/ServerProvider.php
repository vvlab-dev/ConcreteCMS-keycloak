<?php

namespace vvLab\KeycloakAuth\ServerConfigurationProvider;

use Concrete\Core\Error\UserMessageException;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use vvLab\KeycloakAuth\Entity\Server;
use vvLab\KeycloakAuth\ServerConfigurationProvider;

final class ServerProvider implements ServerConfigurationProvider
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfigurationProvider::isEmailRequired()
     */
    public function isEmailRequired()
    {
        $repo = $this->em->getRepository(Server::class);
        $firstServer = $repo->findOneBy([], ['sort' => 'ASC']);

        return $firstServer !== null && $firstServer->getEmailRegexes() !== [];
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfigurationProvider::getServerConfigurationByEmail()
     */
    public function getServerConfigurationByEmail($email)
    {
        $repo = $this->em->getRepository(Server::class);
        $servers = $repo->findBy([], ['sort' => 'ASC']);
        if ($servers === []) {
            throw new UserMessageException(t('No keycloak server has been defined.'));
        }
        foreach ($servers as $server) {
            $regexes = $server->getEmailRegexes();
            if ($regexes === []) {
                return $server;
            }
            $err = '';
            set_error_handler(static function ($errno, $errstr) use (&$err) {
                $err = is_string($errstr) ? trim($errstr) : '';
                if ($err === '') {
                    $err = "Error {$errno}";
                }
            });
            try {
                foreach ($regexes as $regex) {
                    $err = '';
                    $match = preg_match('{' . $regex . '}i', $email);
                    if ($match === false) {
                        throw new RuntimeException(t('Error in the following regular expression:') . "\n{$regex}\n" . t('Error detail: %s', $err));
                    }
                    if ($match !== 0) {
                        return $server;
                    }
                }
            } finally {
                restore_error_handler();
            }
        }
        throw new UserMessageException(t('No keycloak server can handle the provided email address.'));
    }

    /**
     * {@inheritdoc}
     *
     * @see \vvLab\KeycloakAuth\ServerConfigurationProvider::getServerConfigurationByHandle()
     */
    public function getServerConfigurationByHandle($handle)
    {
        if (!is_numeric($handle)) {
            return null;
        }
        $id = (int) $handle;
        if ($id <= 0) {
            return null;
        }

        return $this->em->find(Server::class, $id);
    }
}
