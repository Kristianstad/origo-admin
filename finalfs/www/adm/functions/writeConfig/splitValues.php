<?php

// Splits a comma-separated JS value list, ignoring commas inside strings and brackets.
function splitValues($valueString) {
    $values = [];
    $current = '';
    $depth = 0; // Tracks nested brackets/braces/parentheses
    $inString = false;
    $stringChar = '';

    for ($i = 0; $i < strlen($valueString); $i++) {
        $char = $valueString[$i];

        if ($inString) {
            if ($char === $stringChar && $valueString[$i - 1] !== '\\') {
                $inString = false;
            }
            $current .= $char;
            continue;
        }

        if ($char === '"' || $char === "'") {
            $inString = true;
            $stringChar = $char;
            $current .= $char;
            continue;
        }

        if ($char === '{' || $char === '[' || $char === '(') {
            $depth++;
            $current .= $char;
            continue;
        }

        if ($char === '}' || $char === ']' || $char === ')') {
            $depth--;
            $current .= $char;
            continue;
        }

        if ($char === ',' && $depth === 0) {
            $values[] = trim($current);
            $current = '';
            continue;
        }

        $current .= $char;
    }

    if (trim($current) !== '') {
        $values[] = trim($current);
    }

    return $values;
}