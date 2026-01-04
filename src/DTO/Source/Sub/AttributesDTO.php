<?php

namespace CardGenerator\DTO\Source\Sub;

use Symfony\Component\Validator\Constraints as Assert;

class AttributesDTO
{
    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Strength = 0;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Dexterity = 0;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Stamina = 0;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Charisma = 0;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Manipulation = 0;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Appearance = 0;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Perception = 0;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Intelligence = 0;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Wits = 0;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = (int)$v;
            }
        }
    }
}
