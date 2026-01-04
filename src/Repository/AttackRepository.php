<?php

declare(strict_types=1);

namespace CardGenerator\Repository;

use CardGenerator\DTO\Model\Attack as ModelDTO;
use CardGenerator\DTO\Model\Weapon as WeaponModelDTO;
use CardGenerator\DTO\Source\Weapon as WeaponSourceDTO;
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
        // Compute a default Name if missing: "<Character> - <Weapon>"
        $name = (string)($model['Name'] ?? '');
        if ($name === '' || $name === '0') {
            $char = (string)($data['Character'] ?? '');
            $weap = (string)($data['Weapon'] ?? '');
            if ($char !== '' || $weap !== '') {
                $model['Name'] = trim(sprintf('%s - %s', $char, $weap), ' -');
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
                // Linked weapon exists: ignore inline values and take everything from linked weapon
                $model['Weapon'] = $wp;
                foreach (['Speed', 'Defense', 'Accuracy', 'Rate'] as $k) {
                    /** @var WeaponModelDTO $wp */
                    $model[$k] = $wp[$k] ?? null;
                }
            } else {
                // Linked weapon missing: build a placeholder using values stored inside Attack source
                $source = $model->getSource();
                if ($source instanceof SourceDTO) {
                    $ov = $source->WeaponOverrides;
                    $placeholderData = [
                        'Name' => $source->Weapon,
                        'Speed' => $ov?->Speed ?? 6,
                        'Defense' => $ov?->Defense ?? 0,
                        'Accuracy' => $ov?->Accuracy ?? 0,
                        'Rate' => $ov?->Rate ?? 1,
                    ];
                    $placeholder = new WeaponModelDTO(new WeaponSourceDTO($placeholderData));
                    $model['Weapon'] = $placeholder;
                    foreach (['Speed', 'Defense', 'Accuracy', 'Rate'] as $k) {
                        $model[$k] = $placeholder[$k] ?? null;
                    }
                }
            }
        } else {
            // No link declared: still try to create placeholder from source overrides
            $source = $model->getSource();
            if ($source instanceof SourceDTO && $source->WeaponOverrides !== null) {
                $ov = $source->WeaponOverrides;
                $placeholderData = [
                    'Name' => $source->Weapon,
                    'Speed' => $ov->Speed,
                    'Defense' => $ov->Defense,
                    'Accuracy' => $ov->Accuracy,
                    'Rate' => $ov->Rate,
                ];
                $placeholder = new WeaponModelDTO(new WeaponSourceDTO($placeholderData));
                $model['Weapon'] = $placeholder;
                foreach (['Speed', 'Defense', 'Accuracy', 'Rate'] as $k) {
                    $model[$k] = $placeholder[$k] ?? null;
                }
            }
        }
    }
}
