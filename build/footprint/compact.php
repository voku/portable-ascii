<?php

// Regenerate only representations, never mapping values. PHP >= 7.1.
$write = in_array('--write', $argv, true);
$root = dirname(__DIR__, 2) . '/src/voku/helper/data/';
$changed = 0;
// Unicode bank generation/import is owned by portable-ascii-mapping-data.
$file = $root . 'ascii_ord.php';
$output = "<?php return ['' => 0] + \\array_flip(\\array_map('chr', \\range(0, 255)));\n";
if ($output !== file_get_contents($file)) {
    ++$changed;
    if ($write) {
        file_put_contents($file, $output);
    }
}
echo $changed . ' representations ' . ($write ? 'written' : 'need regeneration') . PHP_EOL;
exit(!$write && $changed !== 0 ? 1 : 0);
