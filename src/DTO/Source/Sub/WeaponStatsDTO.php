<?php

namespace CardGenerator\DTO\Source\Sub;

use Symfony\Component\Validator\Constraints as Assert;

class WeaponStatsDTO
{
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
    public function __construct(array $data = [])
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k) && $v !== '' && $v !== null) {
                $this->$k = (int)$v;
            }
        }
    }
}
