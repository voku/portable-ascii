<?php

// Print a temporary root manifest path; production composer.json remains untouched.
$root = dirname(__DIR__, 2);
$manifest = json_decode(file_get_contents($root . '/composer.json'), true);
$providers = [
    'voku/portable-ascii-mapping-data' => '3a5ae61a58e31cbad24f7014070e724bf38682fe',
];
foreach ($providers as $name => $reference) {
    $manifest['require'][$name] = 'dev-main#' . $reference . ' as 1.0.0';
    $manifest['repositories'][] = ['type' => 'vcs', 'url' => 'https://github.com/' . $name];
}
foreach (['autoload', 'autoload-dev'] as $section) {
    foreach ($manifest[$section]['psr-4'] as &$path) {
        $path = $root . '/' . $path;
    }
    unset($path);
    if (isset($manifest[$section]['files'])) {
        foreach ($manifest[$section]['files'] as &$path) {
            $path = $root . '/' . $path;
        }
        unset($path);
    }
}
$manifest['config']['vendor-dir'] = $root . '/vendor';
$directory = sys_get_temp_dir() . '/portable-ascii-provider-dev-' . substr(hash('sha256', $root), 0, 12);
if (!is_dir($directory)) {
    mkdir($directory, 0700, true);
}
$path = $directory . '/composer.json';
file_put_contents($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
echo $path . PHP_EOL;
