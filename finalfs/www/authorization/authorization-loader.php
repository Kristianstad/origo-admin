<?php
    /**
     * REN loader för authorization (inloggning + displayLogin)
     * Används av Traefik ForwardAuth redirect + formulär-POST
     * Ingen HTML-output före PHP-logiken!
     */
    chdir('../adm/');
    require ('authorization.php');