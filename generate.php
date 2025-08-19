<?php

require_once 'vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Dompdf\Dompdf;
use Dompdf\Options;

function loadCardsFromCSV($filename) {
    $cards = [];
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ";");
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $cards[] = array_combine($header, $data);
        }
        fclose($handle);
    }
    return $cards;
}

if ($argc < 2) {
    echo "Usage: php generate.php <card_type>\n";
    echo "Example: php generate.php playing\n";
    exit(1);
}

$cardType = $argv[1];
$templateFile = "templates/{$cardType}.html.twig";
$dataFile = "data/{$cardType}.csv";

if (!file_exists($templateFile)) {
    echo "Error: Template file '{$templateFile}' not found.\n";
    exit(1);
}

if (!file_exists($dataFile)) {
    echo "Error: Data file '{$dataFile}' not found.\n";
    exit(1);
}

try {
    $loader = new FilesystemLoader('templates');
    $twig = new Environment($loader);
    
    $cards = loadCardsFromCSV($dataFile);
    
    $html = $twig->render("{$cardType}.html.twig", ['cards' => $cards]);
    
    // Save HTML file
    file_put_contents("output/{$cardType}.html", $html);
    echo "{$cardType} cards HTML generated successfully at output/{$cardType}.html\n";
    
    // Generate PDF from HTML
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', true);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $output = $dompdf->output();
    file_put_contents("output/{$cardType}.pdf", $output);
    
    echo "{$cardType} cards PDF generated successfully at output/{$cardType}.pdf\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}