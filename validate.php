<?php

require_once __DIR__.'/vendor/autoload.php';

use CardGenerator\Repository\CharacterRepository;
use CardGenerator\Repository\WeaponRepository;
use CardGenerator\Repository\ArmorRepository;
use CardGenerator\Repository\AttackRepository;
use CardGenerator\Repository\DefenceRepository;
use CardGenerator\Repository\NpcRepository;
use CardGenerator\Repository\MassCombatUnitRepository;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Valid;

$dataDir = __DIR__.'/data';

// Instantiate repositories and their dependencies
$characters = new CharacterRepository($dataDir);
$weapons = new WeaponRepository($dataDir);
$armors = new ArmorRepository($dataDir);
$attacks = new AttackRepository($characters, $weapons, $dataDir);
$defences = new DefenceRepository($characters, $armors, $dataDir);
$npcs = new NpcRepository($dataDir);
$units = new MassCombatUnitRepository($npcs, $characters, $dataDir);

$validator = Validation::createValidatorBuilder()
    ->enableAttributeMapping()
    ->getValidator();

/** @var array<string, \CardGenerator\Repository\AbstractRepository> $repos */
$repos = [
    'characters' => $characters,
    'weapons' => $weapons,
    'armors' => $armors,
    'attacks' => $attacks,
    'defences' => $defences,
    'npcs' => $npcs,
    'mass-combat-unit' => $units,
];

$errorCount = 0;
foreach ($repos as $name => $repo) {
    echo "Validating {$name}: {$repo->getCsvFile()}...\n";
    $models = $repo->findAll();
    foreach ($models as $key => $model) {
        $source = $model->getSource();
        $violations = $validator->validate($source, [new Valid()]);
        if (count($violations) > 0) {
            $errorCount += count($violations);
            echo " - Row '{$key}' has violations:\n";
            foreach ($violations as $violation) {
                echo "   * " . $violation->getPropertyPath() . ": " . $violation->getMessage() . "\n";
            }
        }
    }
}

// Report dataset validation result but continue to run code checks afterwards
if ($errorCount === 0) {
    echo "All datasets are valid.\n";
} else {
    echo "Validation finished with {$errorCount} issue(s).\n";
}

// After data validation, run static analysis (PHPStan level 6) and auto-fix coding style (PHPCBF)
$projectRoot = __DIR__;
$phpstan = PHP_OS_FAMILY === 'Windows' ? 'vendor\\bin\\phpstan.bat' : 'vendor/bin/phpstan';
$phpcbf = PHP_OS_FAMILY === 'Windows' ? 'vendor\\bin\\phpcbf.bat' : 'vendor/bin/phpcbf';

$overallExit = 0;

// Run PHPStan
if (file_exists($projectRoot . '/' . $phpstan)) {
    echo "\nRunning PHPStan (level 6)...\n";
    passthru(sprintf('php %s analyse --level=6 --no-progress --memory-limit=1G', escapeshellarg($phpstan)), $stanExit);
    if ((int)$stanExit !== 0) {
        $overallExit = 1;
    }
} else {
    echo "PHPStan not installed. Run 'composer install' to install dev tools.\n";
}

// Run PHPCBF to auto-fix style issues
if (file_exists($projectRoot . '/' . $phpcbf)) {
    echo "\nRunning PHPCBF (auto-fix) using phpcs.xml...\n";
    passthru(sprintf('php %s --standard=phpcs.xml src', escapeshellarg($phpcbf)), $cbfExit);
    if ((int)$cbfExit !== 0) {
        // phpcbf returns non-zero when not all issues could be fixed; keep track but do not fail hard
        $overallExit = 1;
    }
} else {
    echo "PHPCBF not installed. Run 'composer install' to install dev tools.\n";
}

// Final exit code indicates whether any step found issues
if ($errorCount === 0 && $overallExit === 0) {
    exit(0);
}
exit(1);
