<?php
// Läser kod och dokumentation och listar mekaniska avvikelser; skriver ingenting.
// Körs med: php docsDrift.php [modul]   Utdata är underlag att granska, inte färdiga slutsatser.

const CODE_ROOT = 'finalfs/www/adm';
const DOCS_ROOT = 'docs/www/adm';
const DOCS_WITHOUT_CODE = array('export.md');
const EXTERNAL_FILES = array('export.php');

function readLines(string $path): array
{
	$lines = file($path, FILE_IGNORE_NEW_LINES);
	return $lines === false ? array() : $lines;
}

function folderNames(string $directory): array
{
	return array_map('basename', glob($directory.'/*', GLOB_ONLYDIR) ?: array());
}

function fileNames(string $directory, string $extension): array
{
	return array_map('basename', glob($directory.'/*.'.$extension) ?: array());
}

function mentions(string $text, string $fileName): bool
{
	$functionName = pathinfo($fileName, PATHINFO_FILENAME);
	return strpos($text, $fileName) !== false || strpos($text, $functionName.'(') !== false;
}

function knownFileNames(): array
{
	$names = array();
	$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('finalfs', FilesystemIterator::SKIP_DOTS));
	foreach ($files as $file)
	{
		$names[$file->getFilename()] = true;
	}
	return $names;
}

function undocumentedFiles(?string $scope): array
{
	$findings = array();
	$sources = array(array(CODE_ROOT.'/functions', 'php'), array(CODE_ROOT.'/js-functions', 'js'));
	foreach ($sources as list($root, $extension))
	{
		foreach (folderNames($root) as $module)
		{
			if ($scope !== null && $scope !== $module)
			{
				continue;
			}
			$docPath = DOCS_ROOT.'/'.$module.'.md';
			if (!is_file($docPath))
			{
				$findings[] = "$root/$module/: dokumentet $docPath saknas";
				continue;
			}
			$text = file_get_contents($docPath);
			foreach (fileNames("$root/$module", $extension) as $file)
			{
				if (!mentions($text, $file))
				{
					$findings[] = "$root/$module/$file: nämns inte i $docPath";
				}
			}
		}
	}
	if ($scope === null || $scope === 'constants')
	{
		$text = file_get_contents(DOCS_ROOT.'/constants.md');
		foreach (fileNames(CODE_ROOT.'/constants', 'php') as $file)
		{
			if (!mentions($text, $file))
			{
				$findings[] = CODE_ROOT."/constants/$file: nämns inte i ".DOCS_ROOT.'/constants.md';
			}
		}
	}
	return $findings;
}

function documentedButMissing(array $docs, array $knownFiles): array
{
	$findings = array();
	foreach ($docs as $doc)
	{
		if (in_array(basename($doc), DOCS_WITHOUT_CODE, true))
		{
			continue;
		}
		$seen = array();
		foreach (readLines($doc) as $index => $line)
		{
			preg_match_all('~`([a-z][A-Za-z0-9]*\.(?:php|js))`~', $line, $matches);
			foreach ($matches[1] as $file)
			{
				if (!isset($knownFiles[$file]) && !isset($seen[$file]) && !in_array($file, EXTERNAL_FILES, true))
				{
					$seen[$file] = true;
					$findings[] = $doc.':'.($index + 1).": `$file` finns inte i repot";
				}
			}
		}
	}
	return $findings;
}

function staleFileCounts(array $docs): array
{
	$findings = array();
	foreach ($docs as $doc)
	{
		foreach (readLines($doc) as $index => $line)
		{
			if (preg_match('~adm/(functions|js-functions)/(\w+)/\*\.(php|js)`[^(]*\((\d+) filer\)~', $line, $match))
			{
				$actual = count(glob(CODE_ROOT.'/'.$match[1].'/'.$match[2].'/*.'.$match[3]));
				if ((int) $match[4] !== $actual)
				{
					$findings[] = $doc.':'.($index + 1).": anger {$match[4]} filer, mappen har $actual";
				}
			}
		}
	}
	return $findings;
}

function brokenLinks(array $files): array
{
	$findings = array();
	foreach ($files as $file)
	{
		foreach (readLines($file) as $index => $line)
		{
			preg_match_all('~\[[^\]]*\]\(([^)\s]+)\)~', $line, $matches);
			foreach ($matches[1] as $target)
			{
				if (preg_match('~^(https?:|mailto:|#)~', $target))
				{
					continue;
				}
				$path = urldecode(strtok($target, '#'));
				if (!file_exists(dirname($file).'/'.$path))
				{
					$findings[] = $file.':'.($index + 1).": länken $target pekar på en saknad fil";
				}
			}
		}
	}
	return $findings;
}

chdir(dirname(__DIR__, 4));
if (!is_dir(CODE_ROOT) || !is_dir(DOCS_ROOT))
{
	fwrite(STDERR, "Hittar inte ".CODE_ROOT." eller ".DOCS_ROOT.".\n");
	exit(2);
}

$scope = $argv[1] ?? null;
$docs = glob(DOCS_ROOT.'/*.md');
$markdownFiles = array_merge($docs, glob('docs/*.md'), glob('.agents/skills/*/SKILL.md'), array('README.md', 'AGENTS.md'));
if ($scope !== null)
{
	$docs = array(DOCS_ROOT.'/'.$scope.'.md');
	$markdownFiles = $docs;
}

$categories = array(
	'Kod som inte nämns i dokumentationen' => undocumentedFiles($scope),
	'Dokumenterade filer som saknas i repot' => documentedButMissing($docs, knownFileNames()),
	'Antal filer i dokumentationen stämmer inte' => staleFileCounts($docs),
	'Länkar till saknade filer' => brokenLinks(array_filter($markdownFiles, 'is_file')),
);

$total = 0;
foreach ($categories as $title => $findings)
{
	echo "== $title (".count($findings).") ==\n";
	foreach ($findings as $finding)
	{
		echo "$finding\n";
	}
	echo "\n";
	$total += count($findings);
}
echo $total === 0
	? "Inga mekaniska avvikelser hittades.\n"
	: "$total avvikelser att granska; kontrollera varje mot koden innan de rapporteras.\n";
exit($total === 0 ? 0 : 1);
