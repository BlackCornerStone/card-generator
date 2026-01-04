<?php

namespace CardGenerator\Repository;

use CardGenerator\DTO\Model\Weapon as ModelDTO;
use CardGenerator\DTO\Source\Weapon as SourceDTO;

class WeaponRepository extends AbstractRepository
{
    protected function getCsvFile(): string
    {
        return 'weapons.csv';
    }

    protected function createSource(array $data)
    {
        return new SourceDTO($data);
    }

    protected function createModel($source)
    {
        return new ModelDTO($source);
    }
}
