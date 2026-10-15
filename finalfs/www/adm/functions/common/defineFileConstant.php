<?php

    function defineFileConstant($key, $value)
    {
        file_put_contents("./tmp/$key.php", "<?php\ndefine('$key', ".var_export($value, true).');');
        define($key, $value);
    }