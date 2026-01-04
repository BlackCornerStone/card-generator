<?php

namespace CardGenerator\DTO\Source;

use Symfony\Component\Validator\Constraints as Assert;

class Weapon extends AbstractSourceDTO
{
    #[Assert\NotBlank]
    public string $Name;

    #[Assert\Type('integer')]
    public int $Speed = 0;

    #[Assert\Type('integer')]
    public int $Defense = 0;

    #[Assert\Type('integer')]
    public int $Accuracy = 0;

    #[Assert\Type('integer')]
    public int $Rate = 0;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->Name = (string)($data['Name'] ?? '');
        $this->Speed = isset($data['Speed']) && $data['Speed'] !== '' ? (int)$data['Speed'] : 0;
        $this->Defense = isset($data['Defense']) && $data['Defense'] !== '' ? (int)$data['Defense'] : 0;
        $this->Accuracy = isset($data['Accuracy']) && $data['Accuracy'] !== '' ? (int)$data['Accuracy'] : 0;
        $this->Rate = isset($data['Rate']) && $data['Rate'] !== '' ? (int)$data['Rate'] : 0;
    }
}
