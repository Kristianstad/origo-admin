<?php

    // Exceptions to the naming convention that identifies id references, keyed "<table>.<column>".
    // The value is the referenced table, or null if the column does not hold ids of another table.
    $idReferenceOverrides=array(
        'layers.format'=>null,
        'layers.style_layer'=>'layers',
        'layers.type'=>'formats'
    );
