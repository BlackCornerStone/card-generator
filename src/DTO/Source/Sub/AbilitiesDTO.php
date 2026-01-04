<?php

declare(strict_types=1);

namespace CardGenerator\DTO\Source\Sub;

use Symfony\Component\Validator\Constraints as Assert;

class AbilitiesDTO
{
    // Core ability names from npcs.csv header
    public const LIST = [
        'Archery','Martial Arts','Melee','Thrown','War','Integrity','Performance','Presence','Resistance','Survival',
        'Craft','Investigation','Lore','Medicine','Occult','Athletics','Awareness','Dodge','Larceny','Stealth',
        'Bureaucracy','Linguistics','Ride','Sail','Socialize'
    ];

    /** @var array<string,int> */
    #[Assert\All([
        new Assert\Type('integer'),
        new Assert\GreaterThanOrEqual(0)
    ])]
    public array $values = [];

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach (self::LIST as $name) {
            $this->values[$name] = isset($data[$name]) && $data[$name] !== '' ? (int)$data[$name] : 0;
        }
    }

    public function __get(string $name): int
    {
        return $this->values[$name] ?? 0;
    }

    /**
     * @return array<string,int>
     */
    public function toArray(): array
    {
        return $this->values;
    }
}
