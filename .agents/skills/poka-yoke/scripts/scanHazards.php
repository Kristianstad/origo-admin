<?php
// Listar textuellt upptäckbara faror i PHP-koden; skriver ingenting.
// Körs med: php scanHazards.php [sökväg ...]   Utdata är underlag att granska, inte färdiga slutsatser.

const DEFAULT_PATHS = array('finalfs/www');
const EXCLUDED_PATHS = array('finalfs/www/plugins/', 'finalfs/www/demokarta/', 'finalfs/www/preview/');

function phpFiles(array $paths): array
{
    $files = array();
    foreach ($paths as $path)
    {
        if (is_file($path))
        {
            $files[] = $path;
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file)
        {
            $name = str_replace('\\', '/', $file->getPathname());
            if ($file->getExtension() === 'php' && !isExcluded($name))
            {
                $files[] = $name;
            }
        }
    }
    sort($files);
    return $files;
}

function isExcluded(string $path): bool
{
    foreach (EXCLUDED_PATHS as $excluded)
    {
        if (strpos($path, $excluded) === 0)
        {
            return true;
        }
    }
    return false;
}

// Variabelnamn som får bäddas in i SQL eftersom de bär schema-, tabell- eller kolumnnamn eller platshållare (värden ska gå som $1, $2 ...).
const SQL_IDENTIFIER_VARIABLE = '/^(configSchema|schema|table|tableName|column|columns|values|placeholders|idColumn|\w*Table|\w*WithSchema|\w*Column|\w*Columns|\w*Placeholders|\w*PkColumn)$/';

function hazardRules(): array
{
    return array(
        'Superglobal skrivs direkt ut' => '/\b(echo|print|exit|die)\b(?!.*\b(htmlspecialchars|escapeHtml|jsonForInlineJs|json_encode|intval)\()[^;]*\$_(GET|POST|REQUEST|COOKIE)\b/',
        'Variabel inbäddad i händelseattribut' => '/\bon(click|change|input|keyup|keydown|submit|load|focus|mouseover|mouseup)\s*=\s*[\'"][^\'"]*(\{\$(?!\w*Js\b)|\$(?!\w*Js\b)\w)/i',
        'Kommandoanrop eller dynamisk kod' => '/\b(eval|unserialize|shell_exec|exec|system|passthru|proc_open|popen)\s*\(/',
        'Include med variabel sökväg' => '/\b(include|include_once|require|require_once)\b\s*\(?\s*\$/',
    );
}

function isCommentLine(string $line): bool
{
    $trimmed = ltrim($line);
    return $trimmed === '' || strpos($trimmed, '//') === 0 || strpos($trimmed, '*') === 0 || strpos($trimmed, '/*') === 0 || strpos($trimmed, '#') === 0;
}

// Ger SQL-textliteraler där ett värde (inte ett schema-, tabell- eller kolumnnamn) bäddas in eller konkateneras.
function hasInterpolatedSqlValue(string $line): bool
{
    if (!preg_match_all('/"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'(?:\s*\.\s*\$(\w+))?/', $line, $matches, PREG_SET_ORDER))
    {
        return false;
    }
    foreach ($matches as $match)
    {
        if (!preg_match('/\b(SELECT\s|INSERT\s+INTO|UPDATE\s+\S|DELETE\s+FROM)/', $match[0]))
        {
            continue;
        }
        if (isset($match[1]) && !preg_match(SQL_IDENTIFIER_VARIABLE, $match[1]))
        {
            return true;
        }
        if ($match[0][0] === '"' && preg_match_all('/\{?\$([A-Za-z_]\w*)/', $match[0], $variables))
        {
            foreach ($variables[1] as $variable)
            {
                if (!preg_match(SQL_IDENTIFIER_VARIABLE, $variable))
                {
                    return true;
                }
            }
        }
    }
    return false;
}

function scanFile(string $file, array $rules): array
{
    $findings = array();
    foreach (file($file, FILE_IGNORE_NEW_LINES) ?: array() as $index => $line)
    {
        if (isCommentLine($line))
        {
            continue;
        }
        $location = $file.':'.($index + 1).': '.trim($line);
        if (hasInterpolatedSqlValue($line))
        {
            $findings['SQL med inbäddat värde'][] = $location;
        }
        foreach ($rules as $title => $pattern)
        {
            if (preg_match($pattern, $line))
            {
                $findings[$title][] = $location;
            }
        }
    }
    return $findings;
}

function postEntryPointsWithoutCsrf(array $files): array
{
    $findings = array();
    foreach ($files as $file)
    {
        if (dirname($file) !== 'finalfs/www/adm' || basename($file) === 'includeDirectory.php')
        {
            continue;
        }
        $text = file_get_contents($file);
        if (preg_match('/\$_POST|\$post\b/', $text) && strpos($text, 'validateCsrfToken(') === false)
        {
            $findings[] = $file;
        }
    }
    return $findings;
}

chdir(dirname(__DIR__, 4));
$paths = array_slice($argv, 1) ?: DEFAULT_PATHS;
$files = phpFiles($paths);
$rules = hazardRules();
$results = array();
foreach ($files as $file)
{
    foreach (scanFile($file, $rules) as $title => $lines)
    {
        $results[$title] = array_merge($results[$title] ?? array(), $lines);
    }
}
$results['Entry points med POST-data utan CSRF-kontroll'] = postEntryPointsWithoutCsrf($files);

echo 'Skannade '.count($files)." PHP-filer.\n\n";
$total = 0;
foreach ($results as $title => $lines)
{
    echo "== $title (".count($lines).") ==\n";
    foreach ($lines as $line)
    {
        echo "$line\n";
    }
    echo "\n";
    $total += count($lines);
}
echo $total === 0 ? "Inga textuella faror hittades.\n" : "$total leads att granska; verifiera varje mot koden innan de rapporteras.\n";
exit($total === 0 ? 0 : 1);
