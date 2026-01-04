<?php

namespace CardGenerator\DTO\Source;

use Symfony\Component\Validator\Constraints as Assert;
use CardGenerator\DTO\Source\Sub\ArmorStatsDTO;

class Defence extends AbstractSourceDTO
{
    #[Assert\NotBlank]
    public string $Name;
    #[Assert\NotBlank]
    public string $Character;

    #[Assert\NotBlank]
    public string $Armor;

    #[Assert\Valid]
    public ?ArmorStatsDTO $ArmorOverrides = null;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->Name = (string)($data['Character'] ?? '') . ' ' . (string)($data['Armor'] ?? '');
        $this->Character = (string)($data['Character'] ?? '');
        $this->Armor = (string)($data['Armor'] ?? '');
        // Prefer explicit Overrides key produced by normalization; fallback to legacy array-under-Armor behavior
        if (isset($data['ArmorOverrides']) && is_array($data['ArmorOverrides'])) {
            $this->ArmorOverrides = new ArmorStatsDTO($data['ArmorOverrides']);
        } elseif (isset($data['Armor']) && is_array($data['Armor'])) {
            $this->ArmorOverrides = new ArmorStatsDTO($data['Armor']);
        } else {
            $this->ArmorOverrides = null;
        }
    }
}
