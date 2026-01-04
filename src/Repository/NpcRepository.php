<?php

namespace CardGenerator\Repository;

use CardGenerator\DTO\Model\Npc as ModelDTO;
use CardGenerator\DTO\Source\Npc as SourceDTO;

class NpcRepository extends AbstractRepository
{
    public function getCsvFile(): string
    {
        return 'npcs.csv';
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
