<?php

namespace CardGenerator\DTO\Source;

use Symfony\Component\Validator\Constraints as Assert;

class Armor extends AbstractSourceDTO
{
    #[Assert\NotBlank]
    public string $Name;

    #[Assert\Type('integer')]
    public int $BSoak = 0;

    #[Assert\Type('integer')]
    public int $LSoak = 0;

    #[Assert\Type('integer')]
    public int $BHard = 0;

    #[Assert\Type('integer')]
    public int $LHard = 0;

    #[Assert\Type('integer')]
    public int $Mobility = 0;

    #[Assert\Type('integer')]
    public int $Fatigue = 0;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->Name = (string)($data['Name'] ?? '');
        $this->BSoak = isset($data['BSoak']) && $data['BSoak'] !== '' ? (int)$data['BSoak'] : 0;
        $this->LSoak = isset($data['LSoak']) && $data['LSoak'] !== '' ? (int)$data['LSoak'] : 0;
        $this->BHard = isset($data['BHard']) && $data['BHard'] !== '' ? (int)$data['BHard'] : 0;
        $this->LHard = isset($data['LHard']) && $data['LHard'] !== '' ? (int)$data['LHard'] : 0;
        $this->Mobility = isset($data['Mobility']) && $data['Mobility'] !== '' ? (int)$data['Mobility'] : 0;
        $this->Fatigue = isset($data['Fatigue']) && $data['Fatigue'] !== '' ? (int)$data['Fatigue'] : 0;
    }
}
