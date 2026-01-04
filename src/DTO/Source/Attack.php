<?php

namespace CardGenerator\DTO\Source;

use Symfony\Component\Validator\Constraints as Assert;
use CardGenerator\DTO\Source\Sub\WeaponStatsDTO;

class Attack extends AbstractSourceDTO
{
    #[Assert\NotBlank]
    public string $Name;
    // Link keys from {characters} and {weapons} columns after normalization
    #[Assert\NotBlank]
    public string $Character;

    #[Assert\NotBlank]
    public string $Weapon;

    #[Assert\Valid]
    public ?WeaponStatsDTO $WeaponOverrides = null;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->Name = (string)($data['Name'] ?? '');
        $this->Character = (string)($data['Character'] ?? '');
        $this->Weapon = (string)($data['Weapon'] ?? '');
        $this->WeaponOverrides = isset($data['Weapon']) && is_array($data['Weapon']) ? new WeaponStatsDTO($data['Weapon']) : null;
    }
}
