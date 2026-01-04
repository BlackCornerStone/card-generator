<?php

namespace CardGenerator\Repository;

use CardGenerator\DTO\Model\Character as ModelDTO;
use CardGenerator\DTO\Source\Character as SourceDTO;

class CharacterRepository extends AbstractRepository
{
    protected function getCsvFile(): string
    {
        return 'characters.csv';
    }

    protected function createSource(array $data): SourceDTO
    {
        return new SourceDTO($data);
    }

    protected function createModel($source): ModelDTO
    {
        return new ModelDTO($source);
    }
}
