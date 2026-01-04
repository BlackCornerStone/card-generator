<?php

namespace CardGenerator\DTO\Source;

use Symfony\Component\Validator\Constraints as Assert;

class MassCombatUnit extends AbstractSourceDTO
{
    #[Assert\NotBlank]
    public string $Name;

    #[Assert\Valid]
    public UnitDTO $Unit;

    // Link keys (normalized from {npcs} and {characters})
    #[Assert\NotBlank]
    public string $UnitSource;

    #[Assert\NotBlank]
    public string $Leader;

    public ?string $Notes = null;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->Name = (string)($data['Name'] ?? '');
        $this->Unit = new UnitDTO(is_array($data['Unit'] ?? null) ? $data['Unit'] : []);
        $this->UnitSource = (string)($data['UnitSource'] ?? '');
        $this->Leader = (string)($data['Leader'] ?? '');
        $this->Notes = isset($data['Notes']) && $data['Notes'] !== '' ? (string)$data['Notes'] : null;
    }
}

class UnitDTO
{
    #[Assert\Type('integer')]
    public int $Size = 0;

    #[Assert\Type('integer')]
    public int $Drill = 0;

    #[Assert\Type('integer')]
    public int $Might = 0;

    #[Assert\Type('integer')]
    public int $Morale = 0;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k) && $v !== '' && $v !== null) {
                $this->$k = (int)$v;
            }
        }
    }
}
