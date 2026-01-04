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
        $this->Name = (string)($data['Character'] ?? '') . ' ' . (string)($data['Weapon'] ?? '');
        $this->Character = (string)($data['Character'] ?? '');
        $this->Weapon = (string)($data['Weapon'] ?? '');
        // Prefer explicit Overrides key produced by normalization; fallback to legacy array-under-Weapon behavior
        if (isset($data['WeaponOverrides']) && is_array($data['WeaponOverrides'])) {
            $this->WeaponOverrides = new WeaponStatsDTO($data['WeaponOverrides']);
        } elseif (isset($data['Weapon']) && is_array($data['Weapon'])) {
            $this->WeaponOverrides = new WeaponStatsDTO($data['Weapon']);
        } else {
            $this->WeaponOverrides = null;
        }
    }
}
