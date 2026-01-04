<?php

namespace CardGenerator\DTO\Source\Sub;

use Symfony\Component\Validator\Constraints as Assert;

class ArmorStatsDTO
{
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
    public function __construct(array $data = [])
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k) && $v !== '' && $v !== null) {
                $this->$k = (int)$v;
            }
        }
    }
}
