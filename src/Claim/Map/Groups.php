<?php

namespace KeycloakAuth\Claim\Map;

use Concrete\Core\Error\ErrorList\ErrorList;
use JsonSerializable;

class Groups implements JsonSerializable
{
    /**
     * @var string
     */
    private $claimName = '';

    /**
     * @var \KeycloakAuth\Claim\Map\Groups\Rule
     */
    private $rules = [];

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->getClaimName() === '' && $this->getRules() === [];
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setClaimName($value)
    {
        $this->claimName = is_string($value) ? $value : '';

        return $this;
    }

    /**
     * @return string
     */
    public function getClaimName()
    {
        return $this->claimName;
    }

    /**
     * @param \KeycloakAuth\Claim\Map\Groups\Rule[] $value
     *
     * @return $this
     */
    public function setRules(array $value)
    {
        $this->rules = [];
        foreach ($value as $rule) {
            $this->addRule($rule);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function addRule(Groups\Rule $value)
    {
        $this->rules[] = $value;

        return $this;
    }

    /**
     * @return \KeycloakAuth\Claim\Map\Groups\Rule[]
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * {@inheritdoc}
     *
     * @see \JsonSerializable::jsonSerialize()
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $result = [
            'claimName' => $this->getClaimName(),
            'rules' => [],
        ];

        foreach ($this->getRules() as $rule) {
            $result['rules'][] = $rule->jsonSerialize();
        }

        return $result;
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
                $errors->add(t('Unexpected data type in serialized groups'));
            }

            return null;
        }
        $result = new static();
        $result
            ->setClaimName(array_key_exists('claimName', $data) ? $data['claimName'] : null)
        ;
        if (isset($data['rules']) && is_array($data['rules'])) {
            foreach ($data['rules'] as $ruleData) {
                $rule = Groups\Rule::jsonUnserialize($ruleData, $errors);
                if ($rule !== null) {
                    $result->addRule($rule);
                }
            }
        }

        return $result;
    }
}
