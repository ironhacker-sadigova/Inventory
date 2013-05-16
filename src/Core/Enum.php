<?php
/**
 * @author  matthieu.napoli
 * @package Core
 */

/**
 * Enum class
 *
 * Create an enum by implementing this class and adding class constants.
 *
 * @package Core
 */
abstract class Core_Enum
{

    /**
     * Enum value
     * @var mixed
     */
    protected $value;

    /**
     * Creates a new value of some type
     * @param mixed $value
     * @throws UnexpectedValueException if incompatible type is given.
     */
    public function __construct($value)
    {
        $possibleValues = self::toArray();
        if (! in_array($value, $possibleValues)) {
            throw new UnexpectedValueException("Value '$value' is not part of the enum " . get_called_class());
        }
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
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }

    /**
     * Returns all possible values as an array
     * @return array
     */
    public static function toArray()
    {
        $reflection = new ReflectionClass(get_called_class());
        return $reflection->getConstants();
    }

}
