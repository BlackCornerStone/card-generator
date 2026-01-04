<?php

namespace CardGenerator\Repository;

use CardGenerator\DTO\Model\Armor as ModelDTO;
use CardGenerator\DTO\Source\Armor as SourceDTO;
class ArmorRepository extends AbstractRepository
{
    public function getCsvFile(): string
    {
        return 'armors.csv';
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
