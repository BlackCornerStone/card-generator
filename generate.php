<?php

require_once 'vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Dompdf\Dompdf;
use Dompdf\Options;
use CardGenerator\Repository\CharacterRepository;
use CardGenerator\Repository\WeaponRepository;
use CardGenerator\Repository\ArmorRepository;
use CardGenerator\Repository\AttackRepository;
use CardGenerator\Repository\DefenceRepository;
use CardGenerator\Repository\NpcRepository;
use CardGenerator\Repository\MassCombatUnitRepository;

// Always generate all supported card types in one run (hardcoded list)
$supportedCardTypes = [
    'characters/characters' => 'characters',
    'characters/attrition' => 'characters',
    'characters/attacks' => 'attacks',
    'characters/defences' => 'defences',
    'characters/social-combat' => 'characters',
    // Mass combat units
    'mass-combat/unit' => 'mass-combat-unit',
];

// dataFile => anotherDataFile[] (contextual datasets to pass to templates)
$additionalDatasets = [
    'characters/attacks' => ['characters', 'weapons'],
    'characters/defences' => ['characters', 'armors'],
    'characters/social-combat' => ['characters'],
    'mass-combat/unit' => ['npcs', 'characters'],
];

try {
    // Ensure output directory exists
    if (!is_dir('output')) {
        mkdir('output', 0777, true);
    }

    $loader = new FilesystemLoader('templates');
    // Enable Twig debug to allow using {{ dump() }} in templates
    $twig = new Environment($loader, [
        'debug' => true,
    ]);
    $twig->addExtension(new \Twig\Extension\DebugExtension());

    // Instantiate repositories (data dir defaults to ./data)
    $dataDir = __DIR__ . '/data';
    $characters = new CharacterRepository($dataDir);
    $weapons = new WeaponRepository($dataDir);
    $armors = new ArmorRepository($dataDir);
    $attacks = new AttackRepository($characters, $weapons, $dataDir);
    $defences = new DefenceRepository($characters, $armors, $dataDir);
    $npcs = new NpcRepository($dataDir);
    $units = new MassCombatUnitRepository($npcs, $characters, $dataDir);

    foreach ($supportedCardTypes as $cardType => $dataSourceKey) {
        $templateFile = "templates/{$cardType}.html.twig";
        if (!file_exists($templateFile)) {
            echo "Skip: Template file '{$templateFile}' not found.\n";
            continue;
        }

        // Prepare context using repositories
        $templateData = [];
        switch ($dataSourceKey) {
            case 'characters':
                $templateData['cards'] = iterator_to_array($characters->findAll());
                break;
            case 'attacks':
                $templateData['cards'] = iterator_to_array($attacks->findAll());
                break;
            case 'defences':
                $templateData['cards'] = iterator_to_array($defences->findAll());
                break;
            case 'mass-combat-unit':
                $templateData['cards'] = iterator_to_array($units->findAll());
                break;
            default:
                // Fallback (should not happen with current list)
                $templateData['cards'] = [];
        }

        // Attach additional datasets when templates expect them
        $anotherDataSets = $additionalDatasets[$cardType] ?? [];
        foreach ($anotherDataSets as $datasetName) {
            if ($datasetName === 'characters') {
                $templateData['characters'] = iterator_to_array($characters->findAll());
            } elseif ($datasetName === 'weapons') {
                $templateData['weapons'] = iterator_to_array($weapons->findAll());
            } elseif ($datasetName === 'armors') {
                $templateData['armors'] = iterator_to_array($armors->findAll());
            } elseif ($datasetName === 'npcs') {
                $templateData['npcs'] = iterator_to_array($npcs->findAll());
            }
        }

        $html = $twig->render("{$cardType}.html.twig", $templateData);

        // Ensure nested output directory exists for this card type
        $outputHtmlPath = "output/{$cardType}.html";
        $outputPdfPath = "output/{$cardType}.pdf";
        $outputDirForType = dirname($outputHtmlPath);
        if (!is_dir($outputDirForType)) {
            mkdir($outputDirForType, 0777, true);
        }

        // Save HTML file
        file_put_contents($outputHtmlPath, $html);
        echo "{$cardType} cards HTML generated successfully at output/{$cardType}.html\n";

        // Generate PDF from HTML
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        // Keep existing behavior; last setPaper wins (landscape)
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $output = $dompdf->output();
        file_put_contents($outputPdfPath, $output);
        echo "{$cardType} cards PDF generated successfully at output/{$cardType}.pdf\n";
    }

    echo "All generation tasks completed.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}