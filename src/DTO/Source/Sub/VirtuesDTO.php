<?php

namespace CardGenerator\DTO\Source\Sub;

use Symfony\Component\Validator\Constraints as Assert;

class VirtuesDTO
{
    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Compassion = 0;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Conviction = 0;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Temperance = 0;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $Valor = 0;

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
