<?php

namespace CardGenerator\Repository;

use CardGenerator\DTO\Model\Attack as ModelDTO;
use CardGenerator\DTO\Model\Weapon as WeaponModelDTO;
use CardGenerator\DTO\Source\Attack as SourceDTO;

class AttackRepository extends AbstractRepository
{
    private CharacterRepository $characters;
    private WeaponRepository $weapons;

    public function __construct(CharacterRepository $characters, WeaponRepository $weapons, string $dataDir = __DIR__ . '/../../data')
    {
        parent::__construct($dataDir);
        $this->characters = $characters;
        $this->weapons = $weapons;
    }

    public function getCsvFile(): string
    {
        return 'attacks.csv';
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
        // Prefer explicit [Weapon] group overrides if present in the row
        if (isset($data['Weapon']) && is_array($data['Weapon'])) {
            foreach (['Speed', 'Defense', 'Accuracy', 'Rate'] as $k) {
                $v = $data['Weapon'][$k] ?? null;
                if ($v !== null && $v !== '' && $v !== '""') {
                    $model[$k] = $v;
                }
            }
        }

        // Compute a default Name if missing: "<Character> - <Weapon>"
        $name = (string)($model['Name'] ?? '');
        if ($name === '' || $name === '0') {
            $char = (string)($data['Character'] ?? '');
            $weap = (string)($data['Weapon'] ?? '');
            if ($char !== '' || $weap !== '') {
                $model['Name'] = trim($char . ' - ' . $weap, ' -');
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
        if (isset($links['Weapon']) && $links['Weapon']['dataset'] === 'weapons') {
            $wp = $this->weapons->findByName($links['Weapon']['key']);
            if ($wp) {
                $model['Weapon'] = $wp;
                // Fill any missing top-level convenience fields from linked weapon
                foreach (['Speed', 'Defense', 'Accuracy', 'Rate'] as $k) {
                    if (!isset($model[$k]) || $model[$k] === '' || $model[$k] === null) {
                        /** @var WeaponModelDTO $wp */
                        $model[$k] = $wp[$k] ?? null;
                    }
                }
            }
        }
    }
}
