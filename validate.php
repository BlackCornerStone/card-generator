<?php
declare(strict_types=1);

require_once sprintf('%s/vendor/autoload.php', __DIR__);

use CardGenerator\Repository\CharacterRepository;
use CardGenerator\Repository\WeaponRepository;
use CardGenerator\Repository\ArmorRepository;
use CardGenerator\Repository\AttackRepository;
use CardGenerator\Repository\DefenceRepository;
use CardGenerator\Repository\NpcRepository;
use CardGenerator\Repository\MassCombatUnitRepository;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Valid;

$dataDir = sprintf('%s/data', __DIR__);

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
                echo sprintf('   * %s: %s%s', $violation->getPropertyPath(), $violation->getMessage(), "\n");
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
if (file_exists(sprintf('%s/%s', $projectRoot, $phpstan))) {
    echo "\nRunning PHPStan (level 6)...\n";
    passthru(sprintf('php %s analyse --level=6 --no-progress --memory-limit=1G', escapeshellarg($phpstan)), $stanExit);
    if ((int)$stanExit !== 0) {
        $overallExit = 1;
    }
} else {
    echo "PHPStan not installed. Run 'composer install' to install dev tools.\n";
}

// Run PHPCBF to auto-fix style issues
if (file_exists(sprintf('%s/%s', $projectRoot, $phpcbf))) {
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

/**
 * Custom code quality checks:
 * - Enforce declare(strict_types=1) at the top of PHP files
 * - Disallow direct string concatenation (use sprintf instead)
 */
(function (): void {
    $root = __DIR__;
    $pathsToScan = [
        $root . '/src',
        $root . '/generate.php',
        $root . '/validate.php',
    ];

    $excludeDirs = [
        $root . '/vendor',
        $root . '/var',
        $root . '/templates',
        $root . '/output',
    ];

    $missingStrict = [];
    $concatViolations = [];

    $files = [];
    foreach ($pathsToScan as $path) {
        if (is_dir($path)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            /** @var SplFileInfo $file */
            foreach ($it as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $fp = $file->getPathname();
                if (pathinfo($fp, PATHINFO_EXTENSION) !== 'php') {
                    continue;
                }
                $skip = false;
                foreach ($excludeDirs as $ex) {
                    if (str_starts_with($fp, $ex . DIRECTORY_SEPARATOR)) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip) {
                    continue;
                }
                $files[] = $fp;
            }
        } elseif (is_file($path) && str_ends_with($path, '.php')) {
            $files[] = $path;
        }
    }

    foreach (array_unique($files) as $phpFile) {
        $code = (string) file_get_contents($phpFile);
        // Strict types enforcement (check only very top of file after opening tag)
        $prefix = substr($code, 0, 500);
        $hasDeclare = false;
        if (preg_match('/^\s*<\?php\s*(declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;)/mi', $prefix)) {
            $hasDeclare = true;
        }
        if (!$hasDeclare) {
            $missingStrict[] = $phpFile;
        }

        // Disallow concatenation: detect '.' or '.=' outside strings/comments using token_get_all
        $tokens = token_get_all($code);
        $line = 1;
        foreach ($tokens as $tok) {
            if (is_array($tok)) {
                [$id, $text, $ln] = $tok;
                $line = $ln;
                if ($id === T_CONCAT_EQUAL) {
                    $concatViolations[] = sprintf('%s:%d uses ".=" (concatenation assignment)', $phpFile, $line);
                }
            } else {
                if ($tok === '.') {
                    // This is the concatenation operator (floats are T_DNUMBER, strings contain dots but not as separate tokens)
                    $concatViolations[] = sprintf('%s:%d uses "." (concatenation operator)', $phpFile, $line);
                }
            }
        }
    }

    $hasIssues = false;
    if (count($missingStrict) > 0) {
        $hasIssues = true;
        echo "\n[QUALITY] Missing declare(strict_types=1) in files:\n";
        foreach ($missingStrict as $f) {
            echo sprintf(" - %s\n", $f);
        }
    }
    if (count($concatViolations) > 0) {
        $hasIssues = true;
        echo "\n[QUALITY] Direct string concatenation is disallowed (use sprintf):\n";
        foreach ($concatViolations as $v) {
            echo sprintf(" - %s\n", $v);
        }
    }

    if ($hasIssues) {
        // Non-zero exit to fail quality gate; do not override prior exit codes if already failing
        exit(1);
    }
})();
