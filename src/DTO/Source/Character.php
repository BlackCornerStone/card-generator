<?php

namespace CardGenerator\DTO\Source;

use Symfony\Component\Validator\Constraints as Assert;
use CardGenerator\DTO\Source\Sub\AttributesDTO;
use CardGenerator\DTO\Source\Sub\AbilitiesDTO;
use CardGenerator\DTO\Source\Sub\VirtuesDTO;

class Character extends AbstractSourceDTO
{
    #[Assert\NotBlank]
    public string $Name;

    public ?string $Concept = null;
    public ?string $Caste = null;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Essence = 0;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Willpower = 0;

    #[Assert\Valid]
    public AttributesDTO $Attributes;

    #[Assert\Valid]
    public AbilitiesDTO $Abilities;

    #[Assert\Valid]
    public VirtuesDTO $Virtues;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->Name = (string)($data['Name'] ?? '');
        $this->Concept = isset($data['Concept']) && $data['Concept'] !== '' ? (string)$data['Concept'] : null;
        $this->Caste = isset($data['Caste']) && $data['Caste'] !== '' ? (string)$data['Caste'] : null;
        $this->Essence = isset($data['Essence']) && $data['Essence'] !== '' ? (int)$data['Essence'] : 0;
        $this->Willpower = isset($data['Willpower']) && $data['Willpower'] !== '' ? (int)$data['Willpower'] : 0;
        $this->Attributes = new AttributesDTO(is_array($data['Attributes'] ?? null) ? $data['Attributes'] : []);
        $this->Abilities = new AbilitiesDTO(is_array($data['Abilities'] ?? null) ? $data['Abilities'] : []);
        $this->Virtues = new VirtuesDTO(is_array($data['Virtues'] ?? null) ? $data['Virtues'] : []);
    }
}
