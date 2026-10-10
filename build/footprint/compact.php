<?php

// Regenerate only representations, never mapping values. PHP >= 7.1.
$write = in_array('--write', $argv, true);
$root = dirname(__DIR__, 2) . '/src/voku/helper/data/';
$changed = 0;
foreach (glob($root . 'x*.php') as $file) {
    $map = include $file;
    // Preserve sparse key domains, including the supplementary-plane x1f1 bank.
    if (array_keys($map) !== range(0, count($map) - 1)) {
        continue;
    }
    foreach ($map as $value) {
        if (strpos($value, "\t") !== false || strpos($value, "\0") !== false) {
            throw new RuntimeException('Unsupported delimiter in ' . basename($file));
        }
    }
    $packed = str_replace(['\\', "'"], ['\\\\', "\\'"], implode("\t", $map));
    // Escape actual line breaks so Git/editor newline normalization cannot
    // change mapping values inside the generated PHP string.
    $packed = str_replace(["\r", "\n"], ["' . \"\\r\" . '", "' . \"\\n\" . '"], $packed);
    $output = '<?php return \\explode("\\t", \'' . $packed . "');\n";
    if ($output !== file_get_contents($file)) {
        ++$changed;
        if ($write) {
            file_put_contents($file, $output);
        }
    }
}
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
