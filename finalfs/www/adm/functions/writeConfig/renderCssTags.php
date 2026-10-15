<?php

/**
 * NEW: Render CSS tags – identisk logik, bara annat HTML-element
 */
use MatthiasMullie\Minify;

function renderCssTags(array $items): string
{
    $output = [];

    foreach ($items as $original) {
        $item = trim($original);
        if ($item === '') {
            continue;
        }

        // include(...) → inline <style>
        if (preg_match('#^include\s*\(\s*(.+?)\s*\)$#i', $item, $m)) {
            $resource = $m[1];
            $content  = fetchResourceContent($resource);

            if ($content === false) {
                require "./constants/proxyRoot.php";
                $escaped = htmlspecialchars($resource, ENT_QUOTES, 'UTF-8');
                $url     = $proxyRoot . $_SERVER["REQUEST_URI"] . (str_contains($_SERVER["REQUEST_URI"], '?') ? '&' : '?') . 'badJson=y';
                echo '<script>';
                echo 'alert("CSS-filen ' . $escaped . ' kunde inte läsas! Ingen konfiguration skriven.");';
                echo '</script>';
                exit;
            }

            $cssMinifier = new Minify\CSS($content);
            $minifiedContent=$cssMinifier->minify();
            $output[] = "\t\t<style>\n\t\t\t{$minifiedContent}\n\t\t</style>";
            continue;
        }

        // Normal <link> tag
        $href = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
        $output[] = "\t\t<link rel=\"stylesheet\" href=\"{$href}\">";
    }

    return "\n".implode(PHP_EOL, $output);
}