<?php

    // Takes a hit name and the query (strings). Returns a relevance score used to rank hits from a cache.
    function searchCacheMatchScore($name, $query)
    {
        $lowerName=mb_strtolower($name);
        $lowerQuery=mb_strtolower($query);
        $score=0;

        // Starts-with has the highest priority
        if (mb_strpos($lowerName, $lowerQuery) === 0)
        {
            $score+=500;
        }

        // Word start (beginning of a word after a space)
        if (mb_strpos(' '.$lowerName, ' '.$lowerQuery) !== false)
        {
            $score+=250;
        }

        // Levenshtein bonus (at most 4 away)
        $distance=levenshtein($lowerName, $lowerQuery);
        if ($distance <= 4)
        {
            $score+=max(0, 5 - $distance) * 12;
        }

        // Word similarity bonus (like word_similarity in PostgreSQL)
        $maxSimilarity=0;
        foreach (preg_split('/\s+/', $lowerName) as $word)
        {
            similar_text($lowerQuery, $word, $percent);
            if ($percent > $maxSimilarity)
            {
                $maxSimilarity=$percent;
            }
        }
        return $score + (int) ($maxSimilarity * 1.2);
    }
