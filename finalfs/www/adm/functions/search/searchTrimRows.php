<?php

    // Takes hits from one search table (array), the table's limit and whether to spread the hits over TITLE categories.
    // Returns at most limit hits.
    function searchTrimRows($rows, $limit, $groupByTitle)
    {
        if ($groupByTitle)
        {
            return searchSelectRowsByTitle($rows, $limit);
        }
        return array_slice(searchDiversifyRows($rows, $limit), 0, $limit);
    }
