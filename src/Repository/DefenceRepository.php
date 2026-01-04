<?php

declare(strict_types=1);

namespace CardGenerator\Repository;

use CardGenerator\DTO\Model\Defence as ModelDTO;
use CardGenerator\DTO\Model\Armor as ArmorModelDTO;
use CardGenerator\DTO\Source\Armor as ArmorSourceDTO;
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
        // Compute a default Name if missing: "<Character> - <Armor>"
        $name = (string)($model['Name'] ?? '');
        if ($name === '' || $name === '0') {
            $char = (string)($data['Character'] ?? '');
            $arm = (string)($data['Armor'] ?? '');
            if ($char !== '' || $arm !== '') {
                $model['Name'] = trim(sprintf('%s - %s', $char, $arm), ' -');
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
                // Linked armor exists: ignore inline values and take everything from linked armor
                $model['Armor'] = $ar;
                foreach (['BSoak', 'LSoak', 'BHard', 'LHard', 'Mobility', 'Fatigue'] as $k) {
                    /** @var ArmorModelDTO $ar */
                    $model[$k] = $ar[$k] ?? null;
                }
            } else {
                // Linked armor missing: build a placeholder using values stored inside Defence source
                $source = $model->getSource();
                if ($source instanceof SourceDTO) {
                    $ov = $source->ArmorOverrides;
                    $placeholderData = [
                        'Name' => $source->Armor,
                        'BSoak' => $ov?->BSoak ?? 0,
                        'LSoak' => $ov?->LSoak ?? 0,
                        'BHard' => $ov?->BHard ?? 0,
                        'LHard' => $ov?->LHard ?? 0,
                        'Mobility' => $ov?->Mobility ?? 0,
                        'Fatigue' => $ov?->Fatigue ?? 0,
                    ];
                    $placeholder = new ArmorModelDTO(new ArmorSourceDTO($placeholderData));
                    $model['Armor'] = $placeholder;
                    foreach (['BSoak', 'LSoak', 'BHard', 'LHard', 'Mobility', 'Fatigue'] as $k) {
                        $model[$k] = $placeholder[$k] ?? null;
                    }
                }
            }
        } else {
            // No link declared: still try to create placeholder from source overrides
            $source = $model->getSource();
            if ($source instanceof SourceDTO && $source->ArmorOverrides !== null) {
                $ov = $source->ArmorOverrides;
                $placeholderData = [
                    'Name' => $source->Armor,
                    'BSoak' => $ov->BSoak,
                    'LSoak' => $ov->LSoak,
                    'BHard' => $ov->BHard,
                    'LHard' => $ov->LHard,
                    'Mobility' => $ov->Mobility,
                    'Fatigue' => $ov->Fatigue,
                ];
                $placeholder = new ArmorModelDTO(new ArmorSourceDTO($placeholderData));
                $model['Armor'] = $placeholder;
                foreach (['BSoak', 'LSoak', 'BHard', 'LHard', 'Mobility', 'Fatigue'] as $k) {
                    $model[$k] = $placeholder[$k] ?? null;
                }
            }
        }
    }
}
