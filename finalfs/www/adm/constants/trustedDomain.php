<?php

    // The organisation's internet domain (string), for example 'example.com'. Hosts in it (the domain itself and its subdomains) are trusted as redirect targets after login (forwardauth) and as hosts for export requests.
    // Empty (default) trusts any host, which works everywhere but is less secure: login can redirect to, and exports can call, any host. Set it in production.
    $trustedDomain = '';
