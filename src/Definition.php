<?php
namespace Canister;

/**
 * Class Definition
 *
 * @package Canister
 */
class Definition
{
    /**
     * The definition type shortcuts
     */
    const VALUE = 0;
    const CONTAINER = 1;

    /**
     * @var
     */
    private $type;

    /**
     * @var
     */
    private $value;

    /**
     * Definition constructor.
     *
     * @param $type
     * @param $value
     */
    public function __construct($type, $value)
    {
        $this->type = !in_array($type, [self::VALUE, self::CONTAINER]) ? self::VALUE : $type;
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }
}