<?php

namespace KeycloakAuth;

use Concrete\Core\Config\Repository\Repository;

defined('C5_EXECUTE') or die('Access Denied.');

final class UI
{
    /**
     * Major concrete5 / ConcreteCMS version.
     *
     * @var int
     * @readonly
     */
    public $majorVersion;

    /**
     * @var string
     * @readonly
     */
    public $faEye;

    /**
     * @var string
     * @readonly
     */
    public $faEyeSlash;

    /**
     * @var string
     * @readonly
     */
    public $defaultButton;

    /**
     * @var string
     * @readonly
     */
    public $textEnd;

    /**
     * @var string
     * @readonly
     */
    public $formGroup;

    public function __construct(Repository $config)
    {
        $version = $config->get('concrete.version');
        list($majorVersion) = explode('.', $version, 2);
        $this->majorVersion = (int) $majorVersion;
        if ($this->majorVersion >= 9) {
            $this->initializeV9();
        } else {
            $this->initializeV8();
        }
    }

    /**
     * @see https://fontawesome.com/v5/search?m=free
     * @see https://getbootstrap.com/docs/5.2
     */
    private function initializeV9()
    {
        $this->defaultButton = 'btn-secondary';
        $this->textEnd = 'text-end';
        $this->formGroup = 'mb-3';
        $this->faEye = 'fas fa-eye';
        $this->faEyeSlash = 'fas fa-eye-slash';
    }

    /**
     * @see https://fontawesome.com/v4/icons/
     * @see https://getbootstrap.com/docs/3.4/
     */
    private function initializeV8()
    {
        $this->defaultButton = 'btn-default';
        $this->textEnd = 'text-right';
        $this->formGroup = 'form-group';
        $this->faEye = 'fa fa-eye';
        $this->faEyeSlash = 'fa fa-eye-slash';
    }
}
