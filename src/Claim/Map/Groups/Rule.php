<?php

namespace vvLab\KeycloakAuth\Claim\Map\Groups;

use Concrete\Core\Error\ErrorList\ErrorList;
use JsonSerializable;

class Rule implements JsonSerializable
{
    /**
     * @var string
     */
    private $remoteGroupName = '';

    /**
     * @var int|null
     */
    private $localGroupID;

    /**
     * @var bool
     */
    private $joinIfPresent = false;

    /**
     * @var bool
     */
    private $leaveIfAbsent = false;

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setRemoteGroupName($value)
    {
        $this->remoteGroupName = is_string($value) ? $value : '';

        return $this;
    }

    /**
     * @return string
     */
    public function getRemoteGroupName()
    {
        return $this->remoteGroupName;
    }

    /**
     * @param int|null $value
     *
     * @return $this
     */
    public function setLocalGroupID($value)
    {
        $value = is_numeric($value) ? (int) $value : 0;
        $this->localGroupID = $value > 0 ? $value : null;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLocalGroupID()
    {
        return $this->localGroupID;
    }

    /**
     * @param bool $value
     */
    public function setJoinIfPresent($value)
    {
        $this->joinIfPresent = (bool) $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function isJoinIfPresent()
    {
        return $this->joinIfPresent;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setLeaveIfAbsent($value)
    {
        $this->leaveIfAbsent = (bool) $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function isLeaveIfAbsent()
    {
        return $this->leaveIfAbsent;
    }

    /**
     * @return bool
     */
    public function validate(ErrorList $errors = null)
    {
        $result = true;
        if ($this->getRemoteGroupName() === '') {
            if ($errors === null) {
                return false;
            }
            $errors->add(t('The remote group name is not specified in the group rule'));
            $result = false;
        }
        if ($this->getLocalGroupID() === null) {
            if ($errors === null) {
                return false;
            }
            $errors->add(t('The local group is not specified in the group rule'));
            $result = false;
        } else {
            switch ($this->getLocalGroupID()) {
                case GUEST_GROUP_ID:
                    if ($errors === null) {
                        return false;
                    }
                    $errors->add(t("The local group can't be the Guest Users group"));
                    $result = false;
                    break;
                case REGISTERED_GROUP_ID:
                    if ($errors === null) {
                        return false;
                    }
                    $errors->add(t("The local group can't be the Registered Users group"));
                    $result = false;
                    break;
            }
        }
        if ($this->isJoinIfPresent() === false && $this->isLeaveIfAbsent() === false) {
            if ($errors === null) {
                return false;
            }
            $errors->add(t('No join/leave operation specified in the group rule'));
            $result = false;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \JsonSerializable::jsonSerialize()
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'remoteGroupName' => $this->getRemoteGroupName(),
            'localGroupID' => $this->getLocalGroupID(),
            'joinIfPresent' => $this->isJoinIfPresent(),
            'leaveIfAbsent' => $this->isLeaveIfAbsent(),
        ];
    }

    /**
     * @param array|mixed $data
     *
     * @return static|null
     */
    public static function jsonUnserialize($data, ErrorList $errors = null)
    {
        if (!is_array($data)) {
            if ($errors !== null) {
                $errors->add(t('Unexpected data type in serialized group rule'));
            }

            return null;
        }
        $result = new static();
        $result
            ->setRemoteGroupName(array_key_exists('remoteGroupName', $data) ? $data['remoteGroupName'] : null)
            ->setLocalGroupID(array_key_exists('localGroupID', $data) ? $data['localGroupID'] : null)
            ->setJoinIfPresent(array_key_exists('joinIfPresent', $data) ? $data['joinIfPresent'] : null)
            ->setLeaveIfAbsent(array_key_exists('leaveIfAbsent', $data) ? $data['leaveIfAbsent'] : null)
        ;
        if ($result->getRemoteGroupName() === '' && $result->getLocalGroupID() === null) {
            return null;
        }

        return $result->validate($errors) ? $result : null;
    }
}
