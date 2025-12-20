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
        $header = fgetcsv($handle, 1000, ";");

        if ($debug) { var_dump($header); }
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
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
                continue;
            }
            $card = array_combine($header, $data);

            if ($debug) { var_dump($card); }
            $cards[] = preprocessCardData($card);
        }
        fclose($handle);
    }
    return $cards;
}

function preprocessCardData($card) {
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
    
    return $processed;
}

// Always generate all supported card types in one run (hardcoded list)
$supportedCardTypes = [
    //'landscapes' => null,
    //'weather' => null,
    //'travel-times' => null,
    'characters' => 'characters',
    'attacks' => 'characters',
    'defences' => 'characters',
    'social-combat' => 'characters',
];

$additionalDatasets = [
    'characters' => ['procedures'],
];

try {
    // Ensure output directory exists
    if (!is_dir('output')) {
        mkdir('output', 0777, true);
    }

    $loader = new FilesystemLoader('templates');
    $twig = new Environment($loader);

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

        // Save HTML file
        file_put_contents("output/{$cardType}.html", $html);
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
        file_put_contents("output/{$cardType}.pdf", $output);
        echo "{$cardType} cards PDF generated successfully at output/{$cardType}.pdf\n";
    }

    echo "All generation tasks completed.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}