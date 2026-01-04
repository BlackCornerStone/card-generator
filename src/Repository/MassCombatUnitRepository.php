<?php

namespace CardGenerator\Repository;

use CardGenerator\DTO\Model\MassCombatUnit as ModelDTO;
use CardGenerator\DTO\Source\MassCombatUnit as SourceDTO;

class MassCombatUnitRepository extends AbstractRepository
{
    private NpcRepository $npcs;
    private CharacterRepository $characters;

    public function __construct(NpcRepository $npcs, CharacterRepository $characters, string $dataDir = __DIR__ . '/../../data')
    {
        parent::__construct($dataDir);
        $this->npcs = $npcs;
        $this->characters = $characters;
    }

    public function getCsvFile(): string
    {
        return 'mass-combat-unit.csv';
    }

    protected function createSource(array $data)
    {
        return new SourceDTO($data);
    }

    protected function createModel($source)
    {
        return new ModelDTO($source);
    }

    protected function applyLinks($model, array $links): void
    {
        if (isset($links['UnitSource']) && $links['UnitSource']['dataset'] === 'npcs') {
            $npc = $this->npcs->findByName($links['UnitSource']['key']);
            if ($npc) {
                $model['UnitSource'] = $npc;
            }
        }
        if (isset($links['Leader']) && $links['Leader']['dataset'] === 'characters') {
            $leader = $this->characters->findByName($links['Leader']['key']);
            if ($leader) {
                $model['Leader'] = $leader;
            }
        }
    }
}
