<?php

require_once 'vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Dompdf\Dompdf;
use Dompdf\Options;

function loadCardsFromCSV($filename, bool $debug = false) {
    $cards = [];
    $line = 0;
    if (($handle = fopen($filename, "r")) !== FALSE) {
        // Expect semicolon-delimited CSV files
        $header = fgetcsv($handle, 2000, ";");

        if ($debug) { var_dump($header); }
        while (($data = fgetcsv($handle, 2000, ";")) !== FALSE) {
            $line++;
            if ($debug) { var_dump($data); }
            // Skip empty lines or malformed rows
            if ($data === null || $data === [null]) {
                echo "Line $line is empty or malformed. Skipping.\n";
                continue;
            }
            if (count($data) !== count($header)) {
                echo sprintf("Line %s has unexpected number of columns. Header has %s, line %s. Skipping.\n",
                $line, count($header), count($data));
                //var_dump($data);
                continue;
            }
            $card = array_combine($header, $data);

            if ($debug) { var_dump($card); }

            $processed = groupCardData($line, $card);
            replaceLinks($line, $processed);
            replaceImagesInCardData($processed);
            $cards[$processed['Name'] ?? $line] = $processed;
        }
        fclose($handle);
    }
    return $cards;
}
function groupCardData(int $line, array $card): array {
    $processed = [];

    foreach ($card as $key => $value) {
        if (preg_match('/^\[(.+?)\]\s*(.+)$/', $key, $matches)) {
            // Column has bracket format [Group] Subkey
            $groupName = $matches[1];
            $subKey = $matches[2];
            $processed[$groupName][$subKey] = $value;
        } else {
            // Regular column without brackets
            $processed[$key] = $value;
        }
    }

    // Process groups with asterisk suffix - explode values into arrays
    foreach ($processed as $groupName => $groupData) {
        if (is_array($groupData) && substr($groupName, -1) === '*') {
            $cleanGroupName = substr($groupName, 0, -1);
            $explodedGroup = [];

            foreach ($groupData as $subKey => $value) {
                if ($value !== null || trim($value) !== '') {
                    $items = explode('|', $value);
                    foreach ($items as $index => $item) {
                        $explodedGroup[$cleanGroupName][$index][$subKey] = trim($item);
                    }
                }
            }

            unset($processed[$groupName]);
            $processed[$cleanGroupName] = $explodedGroup;
        }
    }

    return $processed;
}

function replaceLinks(int $line, array &$processed): array {
    $loadedLinks = [];

    foreach ($processed as $key => $value) {
        if (preg_match('/^\{(.+?)\}\s*(.+)$/', $key, $matches)) {
            // Column has bracket format [Group] Subkey
            $groupName = $matches[1];
            $subKey = $matches[2];

            if (!isset($loadedLinks[$groupName])) {
                $linksFile = "data/{$groupName}.csv";
                $loadedLinks[$groupName] = loadCardsFromCSV($linksFile);
            }
            $processed[$subKey] = $loadedLinks[$groupName][$value] ?? "404:".$value;
        } else {
            // Regular column without brackets
            $processed[$key] = $value;
        }
    }

    return $processed;
}

function replaceImagesInCardData(&$processed) {
    // Add image filename based on landscape name (if present)
    if (!empty($processed['Landscape'])) {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($processed['Landscape']));
        $filename = preg_replace('/_+/', '_', $filename);
        $filename = trim($filename, '_');
        $imagePath = __DIR__ . '/images/' . $filename . '.png';
        
        // For PDF generation, convert to base64 to avoid path issues
        if (file_exists($imagePath)) {
            $imageData = base64_encode(file_get_contents($imagePath));
            $processed['ImageFile'] = 'data:image/png;base64,' . $imageData;
            $processed['ImagePath'] = $imagePath; // Keep original path for HTML
        }
    }
}

// Always generate all supported card types in one run (hardcoded list)
$supportedCardTypes = [
    //'landscapes' => null,
    //'weather' => null,
    //'travel-times' => null,
    'characters/characters' => 'characters',
    'characters/attrition' => 'characters',
    'characters/attacks' => 'attacks',
    'characters/defences' => 'defences',
    'characters/social-combat' => 'characters',
    // Mass combat units
    'mass-combat/unit' => 'mass-combat-unit',
];

// dataFile => anotherDataFile[]
$additionalDatasets = [
    'characters/attacks' => ['characters', 'weapons'],
    'characters/defences' => ['characters', 'armors'],
    'characters/social-combat' => ['characters'],
    // Mass combat unit needs access to NPCs and Characters
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

    foreach ($supportedCardTypes as $cardType => $dataSourceFilename) {
        if ($dataSourceFilename === null) {
            $dataSourceFilename = $cardType;
        }

        $anotherDataSets = $additionalDatasets[$cardType] ?? [];
        $templateFile = "templates/{$cardType}.html.twig";
        $dataFile = "data/{$dataSourceFilename}.csv";

        if (!file_exists($templateFile)) {
            echo "Skip: Template file '{$templateFile}' not found.\n";
            continue;
        }
        if (!file_exists($dataFile)) {
            echo "Skip: Data file '{$dataFile}' not found.\n";
            continue;
        }

        $templateData = ['cards' => loadCardsFromCSV($dataFile)];

        foreach ($anotherDataSets as $datasetName) {
            $templateData[$datasetName] = loadCardsFromCSV("data/{$datasetName}.csv");
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