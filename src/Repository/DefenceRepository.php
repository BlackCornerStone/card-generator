<?php

namespace CardGenerator\Repository;

use CardGenerator\DTO\Model\Defence as ModelDTO;
use CardGenerator\DTO\Model\Armor as ArmorModelDTO;
use CardGenerator\DTO\Source\Defence as SourceDTO;

class DefenceRepository extends AbstractRepository
{
    private CharacterRepository $characters;
    private ArmorRepository $armors;

    public function __construct(CharacterRepository $characters, ArmorRepository $armors, string $dataDir = __DIR__ . '/../../data')
    {
        parent::__construct($dataDir);
        $this->characters = $characters;
        $this->armors = $armors;
    }

    public function getCsvFile(): string
    {
        return 'defences.csv';
    }

    protected function createSource(array $data)
    {
        return new SourceDTO($data);
    }

    protected function createModel($source)
    {
        return new ModelDTO($source);
    }

    protected function applyComputed($model, array $data): void
    {
        if (isset($data['Armor']) && is_array($data['Armor'])) {
            foreach (['BSoak', 'LSoak', 'BHard', 'LHard', 'Mobility', 'Fatigue'] as $k) {
                $v = $data['Armor'][$k] ?? null;
                if ($v !== null && $v !== '' && $v !== '""') {
                    $model[$k] = $v;
                }
            }
        }

        // Compute a default Name if missing: "<Character> - <Armor>"
        $name = (string)($model['Name'] ?? '');
        if ($name === '' || $name === '0') {
            $char = (string)($data['Character'] ?? '');
            $arm = (string)($data['Armor'] ?? '');
            if ($char !== '' || $arm !== '') {
                $model['Name'] = trim($char . ' - ' . $arm, ' -');
            }
        }
    }

    protected function applyLinks($model, array $links): void
    {
        if (isset($links['Character']) && $links['Character']['dataset'] === 'characters') {
            $ch = $this->characters->findByName($links['Character']['key']);
            if ($ch) {
                $model['Character'] = $ch;
            }
        }
        if (isset($links['Armor']) && $links['Armor']['dataset'] === 'armors') {
            $ar = $this->armors->findByName($links['Armor']['key']);
            if ($ar) {
                $model['Armor'] = $ar;
                // Fill missing top-level fields from linked armor
                foreach (['BSoak', 'LSoak', 'BHard', 'LHard', 'Mobility', 'Fatigue'] as $k) {
                    if (!isset($model[$k]) || $model[$k] === '' || $model[$k] === null) {
                        /** @var ArmorModelDTO $ar */
                        $model[$k] = $ar[$k] ?? null;
                    }
                }
            }
        }
    }
}
