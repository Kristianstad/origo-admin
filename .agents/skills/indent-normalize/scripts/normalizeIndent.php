<?php
// Kontrollerar och normaliserar inledande tabbar till mellanslag i PHP, JS och CSS.
// Körs med: php normalizeIndent.php [--fix] [--width=4] [sökväg ...]   Utan --fix skrivs ingenting.

const DEFAULT_PATHS = array('finalfs/www', '.agents');
const EXCLUDED_PATHS = array('finalfs/www/plugins/', 'finalfs/www/demokarta/', 'finalfs/www/preview/', 'finalfs/www/img/');
const EXTENSIONS = array('php', 'js', 'css');

function sourceFiles(array $paths): array
{
    $files = array();
    foreach ($paths as $path) {
        if (is_file($path)) {
            $files[] = $path;
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $name = str_replace('\\', '/', $file->getPathname());
            if (in_array($file->getExtension(), EXTENSIONS, true) && !isExcluded($name)) {
                $files[] = $name;
            }
        }
    }
    sort($files);
    return $files;
}

function isExcluded(string $path): bool
{
    foreach (EXCLUDED_PATHS as $excluded) {
        if (strpos($path, $excluded) === 0) {
            return true;
        }
    }
    return false;
}

// Tabbar räknas som hopp till nästa tabbstopp, så blandad indentering får rätt bredd.
function expandLeadingWhitespace(string $leading, int $width): string
{
    $column = 0;
    foreach (str_split($leading) as $char) {
        $column = $char === "\t" ? (intdiv($column, $width) + 1) * $width : $column + 1;
    }
    return str_repeat(' ', $column);
}

// Rader vars inledande tecken ligger inne i en sträng, heredoc eller inline-HTML är innehåll, inte indentering.
function protectedLines(string $source): array
{
    $contentTokens = array(T_INLINE_HTML, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_START_HEREDOC, T_END_HEREDOC);
    $protected = array();
    $line = 0;
    $atLineStart = true;
    foreach (token_get_all($source) as $token) {
        $text = is_array($token) ? $token[1] : $token;
        $newlines = substr_count($text, "\n");
        if (is_array($token) && in_array($token[0], $contentTokens, true)) {
            if ($atLineStart) {
                $protected[$line] = true;
            }
            $inside = $newlines - (substr($text, -1) === "\n" ? 1 : 0);
            for ($i = 1; $i <= $inside; $i++) {
                $protected[$line + $i] = true;
            }
        }
        $line += $newlines;
        $atLineStart = substr($text, -1) === "\n";
    }
    return $protected;
}

function significantTokens(string $source): array
{
    $tokens = array();
    foreach (token_get_all($source, TOKEN_PARSE) as $token) {
        if (is_array($token)) {
            if (!in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) {
                $tokens[] = array($token[0], $token[1]);
            }
        } else {
            $tokens[] = $token;
        }
    }
    return $tokens;
}

function normalizeSource(string $source, string $extension, int $width): array
{
    $lines = preg_split('/(?<=\n)/', $source, -1, PREG_SPLIT_NO_EMPTY);
    if ($extension === 'js') {
        foreach ($lines as $line) {
            if (substr_count($line, '`') % 2 === 1) {
                return array('text' => $source, 'changed' => 0, 'skipped' => 0, 'error' => 'flerradig mallsträng, granska manuellt');
            }
        }
    }
    $protected = $extension === 'php' ? protectedLines($source) : array();
    $changed = 0;
    $skipped = 0;
    foreach ($lines as $index => $line) {
        if (!preg_match('/^([ \t]*\t[ \t]*)\S/', $line, $match)) {
            continue;
        }
        if (isset($protected[$index])) {
            $skipped++;
            continue;
        }
        $lines[$index] = expandLeadingWhitespace($match[1], $width).substr($line, strlen($match[1]));
        $changed++;
    }
    $text = implode('', $lines);
    $error = null;
    if ($extension === 'php' && $changed > 0) {
        try {
            if (significantTokens($source) !== significantTokens($text)) {
                $error = 'koden skulle ändras, hoppar över filen';
            }
        } catch (ParseError $exception) {
            $error = 'kunde inte tolkas: '.$exception->getMessage();
        }
    }
    return array('text' => $text, 'changed' => $changed, 'skipped' => $skipped, 'error' => $error);
}

chdir(dirname(__DIR__, 4));
$fix = false;
$width = 4;
$paths = array();
foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--fix') {
        $fix = true;
    } elseif (preg_match('/^--width=([1-9]\d*)$/', $argument, $match)) {
        $width = (int) $match[1];
    } else {
        $paths[] = $argument;
    }
}

$files = sourceFiles($paths ?: DEFAULT_PATHS);
$filesWithTabs = 0;
$lineTotal = 0;
$skippedTotal = 0;
$errors = 0;
foreach ($files as $file) {
    $source = file_get_contents($file);
    $result = normalizeSource($source, pathinfo($file, PATHINFO_EXTENSION), $width);
    if ($result['error'] !== null) {
        $errors++;
        echo "$file: {$result['error']}\n";
        continue;
    }
    if ($result['changed'] === 0 && $result['skipped'] === 0) {
        continue;
    }
    $filesWithTabs++;
    $lineTotal += $result['changed'];
    $skippedTotal += $result['skipped'];
    echo "$file: {$result['changed']} rader".($result['skipped'] > 0 ? ", {$result['skipped']} skyddade rader med tabb kvar (sträng, heredoc eller inline-HTML)" : '')."\n";
    if ($fix && $result['changed'] > 0) {
        file_put_contents($file, $result['text']);
    }
}

echo 'Kontrollerade '.count($files)." filer.\n";
echo $fix ? "Ändrade $lineTotal rader" : "$lineTotal rader att ändra (kör med --fix)";
echo " i $filesWithTabs filer med tabbindentering; $skippedTotal skyddade rader lämnades orörda; $errors filer hoppades över.\n";
exit($errors > 0 || (!$fix && $lineTotal > 0) ? 1 : 0);
