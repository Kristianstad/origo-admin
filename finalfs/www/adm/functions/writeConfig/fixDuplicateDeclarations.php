<?php

function fixDuplicateDeclarations($jsCode) {
    // Split the code into lines
    $lines = explode("\n", $jsCode);
    $declarations = []; // Tracks declared variables and their initial keyword
    $isReassigned = []; // Tracks if a variable is reassigned/redeclared in root scope
    $outputLines = [];
    $depth = 0; // Tracks scope depth (0 = root scope)
    $inString = false;
    $stringChar = '';

    // Regular expression to match variable declarations (single or multi-variable)
    $pattern = '/^\s*(const|let|var)\s+(.+?)\s*=\s*([^;]+);$/';

    // First pass: Identify declarations and reassignments in root scope
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // Update scope depth
        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];

            if ($inString) {
                if ($char === $stringChar && $line[$i - 1] !== '\\') {
                    $inString = false;
                }
                continue;
            }

            if ($char === '"' || $char === "'") {
                $inString = true;
                $stringChar = $char;
                continue;
            }

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
            }
        }

        // Process declarations only in root scope (depth == 0 before this line)
        if ($depth === 0 && preg_match($pattern, $line, $matches)) {
            $keyword = $matches[1];
            $varList = $matches[2];
            $valueList = $matches[3];

            // Split variables and values
            $vars = array_map('trim', explode(',', $varList));
            $values = splitValues($valueList);

            // Ensure the number of variables matches the number of values
            if (count($vars) !== count($values)) {
                continue; // Skip malformed declarations
            }

            foreach ($vars as $varName) {
                if (isset($declarations[$varName])) {
                    $isReassigned[$varName] = true; // Mark as reassigned/redeclared
                } else {
                    $declarations[$varName] = $keyword; // Store initial keyword
                }
            }
        }
    }

    // Reset declarations and depth for second pass
    $declarations = [];
    $depth = 0;
    $inString = false;
    $stringChar = '';

    // Second pass: Process declarations in root scope, preserve all else
    foreach ($lines as $line) {
        $originalLine = $line; // Preserve original line
        $line = trim($line);
        if (empty($line)) {
            $outputLines[] = $originalLine;
            continue;
        }

        // Update scope depth
        $newDepth = $depth;
        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];

            if ($inString) {
                if ($char === $stringChar && $line[$i - 1] !== '\\') {
                    $inString = false;
                }
                continue;
            }

            if ($char === '"' || $char === "'") {
                $inString = true;
                $stringChar = $char;
                continue;
            }

            if ($char === '{') {
                $newDepth++;
            } elseif ($char === '}') {
                $newDepth--;
            }
        }

        // Process only variable declarations in root scope
        if ($depth === 0 && preg_match($pattern, $line, $matches)) {
            $keyword = $matches[1];
            $varList = $matches[2];
            $valueList = $matches[3];

            // Split variables and values
            $vars = array_map('trim', explode(',', $varList));
            $values = splitValues($valueList);

            // Ensure the number of variables matches the number of values
            if (count($vars) !== count($values)) {
                $outputLines[] = $originalLine; // Keep malformed line unchanged
                continue;
            }

            foreach ($vars as $index => $varName) {
                $value = $values[$index];

                if (isset($declarations[$varName])) {
                    // Convert to assignment (include even for const redeclarations)
                    $outputLines[] = "$varName = $value;";
                } else {
                    // First declaration
                    $declarations[$varName] = true;
                    // Use 'let' if initially var/let or reassigned/redeclared, 'const' only if initially const and not reassigned
                    $newKeyword = ($keyword === 'var' || $keyword === 'let' || isset($isReassigned[$varName])) ? 'let' : 'const';
                    $outputLines[] = "$newKeyword $varName = $value;";
                }
            }
        } else {
            // Non-declaration line or non-root scope, keep unchanged
            $outputLines[] = $originalLine;
        }

        // Update depth after processing the line
        $depth = $newDepth;
    }

    // Join lines back into a string
    return implode("\n", $outputLines);
}