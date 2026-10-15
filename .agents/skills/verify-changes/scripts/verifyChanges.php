<?php
// Kontrollerar ändrade och nya filer i git-arbetsytan: PHP-syntax, radslut (LF) och slutradbrytning.
// Körs med: php verifyChanges.php [--fix-new-eol]   Avslutar med 1 vid fel.

function runCommand(string $command, ?int &$exitCode = null): array
{
	exec($command.' 2>&1', $output, $exitCode);
	return $output;
}

function changedFiles(array &$untracked): array
{
	$tracked = runCommand('git -c core.quotepath=false diff --name-only --diff-filter=ACMR HEAD');
	$untracked = runCommand('git -c core.quotepath=false ls-files --others --exclude-standard');
	return array_values(array_unique(array_merge($tracked, $untracked)));
}

function isTextFile(string $path): bool
{
	$extensions = array('php', 'js', 'css', 'md', 'sql', 'sh', 'html', 'json', 'yml', 'yaml', 'txt');
	$names = array('Dockerfile', '.gitattributes', '.dockerignore', '.gitignore');
	return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $extensions, true)
		|| in_array(basename($path), $names, true)
		|| strpos($path, 'finalfs/start/') === 0;
}

function hasCommand(string $command): bool
{
	runCommand($command.' --version', $exitCode);
	return $exitCode === 0;
}

$fixNewEol = in_array('--fix-new-eol', $argv, true);
$root = runCommand('git rev-parse --show-toplevel', $exitCode);
if ($exitCode !== 0 || empty($root[0]))
{
	fwrite(STDERR, "Kör skriptet i ett git-repo.\n");
	exit(2);
}
chdir($root[0]);

$errors = array();
$warnings = array();
$skipped = array();
$untracked = array();
$files = changedFiles($untracked);
$nodeAvailable = hasCommand('node');

foreach ($files as $file)
{
	if (!is_file($file) || !isTextFile($file))
	{
		continue;
	}
	$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
	if ($extension === 'php')
	{
		$output = runCommand(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file), $exitCode);
		if ($exitCode !== 0)
		{
			$errors[] = "$file: PHP-syntaxfel\n  ".implode("\n  ", $output);
		}
	}
	elseif ($extension === 'js' && $nodeAvailable)
	{
		$output = runCommand('node --check '.escapeshellarg($file), $exitCode);
		if ($exitCode !== 0)
		{
			$errors[] = "$file: JavaScript-syntaxfel\n  ".implode("\n  ", $output);
		}
	}
	elseif ($extension === 'js')
	{
		$skipped['JavaScript-syntax (node saknas)'] = true;
	}

	$content = file_get_contents($file);
	if (strpos($content, "\r\n") !== false)
	{
		if ($fixNewEol && in_array($file, $untracked, true))
		{
			file_put_contents($file, str_replace("\r\n", "\n", $content));
			$warnings[] = "$file: CRLF ersatt med LF (ny fil)";
		}
		else
		{
			$errors[] = "$file: innehåller CRLF, förväntat LF";
		}
	}
	if ($content !== '' && substr($content, -1) !== "\n")
	{
		$warnings[] = "$file: saknar avslutande radbrytning";
	}
}

foreach (array('git diff --check', 'git diff --cached --check') as $command)
{
	$output = runCommand($command, $exitCode);
	if ($exitCode !== 0)
	{
		$errors[] = "$command: ".implode("\n  ", $output);
	}
}

echo 'Kontrollerade '.count($files)." ändrade eller nya filer.\n";
foreach ($warnings as $warning)
{
	echo "Varning: $warning\n";
}
foreach (array_keys($skipped) as $check)
{
	echo "Hoppades över: $check\n";
}
foreach ($errors as $error)
{
	echo "FEL: $error\n";
}
echo $errors ? "Misslyckades.\n" : "OK.\n";
exit($errors ? 1 : 0);
