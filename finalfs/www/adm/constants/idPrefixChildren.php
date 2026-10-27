<?php

    // Tables whose ids are built as "<parent id>.<name>", keyed by the parent table
    $idPrefixChildren=array(
        'databases'=>array('schemas', 'tables'),
        'schemas'=>array('tables')
    );
