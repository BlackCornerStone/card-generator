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
        $this->Name = (string)($data['Name'] ?? '');
        $this->Character = (string)($data['Character'] ?? '');
        $this->Armor = (string)($data['Armor'] ?? '');
        $this->ArmorOverrides = isset($data['Armor']) && is_array($data['Armor']) ? new ArmorStatsDTO($data['Armor']) : null;
    }
}
