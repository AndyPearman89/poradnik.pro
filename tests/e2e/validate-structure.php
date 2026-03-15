<?php
/**
 * validate-structure.php
 *
 * Validates that the consolidated poradnik.pro repository contains all
 * required directories and key files.
 *
 * Usage: php tests/e2e/validate-structure.php [--repo-root /path/to/repo]
 */

$repoRoot = dirname(__DIR__, 2);

foreach (getopt('', ['repo-root:']) as $key => $value) {
    if ($key === 'repo-root') {
        $repoRoot = rtrim((string) $value, '/');
    }
}

$required = [
    // Top-level directories
    'theme'                                          => 'dir',
    'backend'                                        => 'dir',
    'migrations'                                     => 'dir',
    'docs'                                           => 'dir',

    // Loader
    'poradnik-platform-loader.php'                   => 'file',

    // Theme: key files
    'theme/style.css'                                => 'file',
    'theme/functions.php'                            => 'file',
    'theme/header.php'                               => 'file',
    'theme/footer.php'                               => 'file',
    'theme/front-page.php'                           => 'file',
    'theme/index.php'                                => 'file',

    // Theme: assets
    'theme/assets/css/main.css'                      => 'file',
    'theme/assets/css/components.css'                => 'file',
    'theme/assets/css/layout.css'                    => 'file',
    'theme/assets/css/responsive.css'                => 'file',
    'theme/assets/js/main.js'                        => 'file',
    'theme/assets/js/ajax.js'                        => 'file',
    'theme/assets/js/search.js'                      => 'file',
    'theme/assets/js/filters.js'                     => 'file',

    // Theme: template-parts (key subdirs)
    'theme/template-parts/front-page'                => 'dir',
    'theme/template-parts/article'                   => 'dir',
    'theme/template-parts/content'                   => 'dir',
    'theme/template-parts/ranking'                   => 'dir',
    'theme/template-parts/review'                    => 'dir',
    'theme/template-parts/nav'                       => 'dir',
    'theme/template-parts/schema'                    => 'dir',

    // Backend: core subdirs
    'backend/Core/Bootstrap.php'                     => 'file',
    'backend/Core/ModuleRegistry.php'                => 'file',
    'backend/Api/RestKernel.php'                     => 'file',
    'backend/Infrastructure/Database/Migrator.php'   => 'file',

    // Migrations
    'migrations/README.md'                           => 'file',
    'migrations/v1.4.0.sql'                          => 'file',

    // Docs
    'docs/cloud-deployment.md'                       => 'file',

    // Root README
    'README.md'                                      => 'file',
];

$pass = 0;
$fail = 0;

echo "=== Repository Structure Validation ===" . PHP_EOL;
echo "Root: $repoRoot" . PHP_EOL . PHP_EOL;

foreach ($required as $path => $type) {
    $full = "$repoRoot/$path";
    $exists = $type === 'dir' ? is_dir($full) : is_file($full);

    if ($exists) {
        echo "  PASS  [$type] $path" . PHP_EOL;
        $pass++;
    } else {
        echo "  FAIL  [$type missing] $path" . PHP_EOL;
        $fail++;
    }
}

echo PHP_EOL . "=== Results: $pass passed, $fail failed ===" . PHP_EOL;

exit($fail > 0 ? 1 : 0);
