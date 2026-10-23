<?php

    // Takes a host name (string) and a domain (string) as parameters
    // Returns true if the host is the domain itself or one of its subdomains (case-insensitive), else returns false
    function isHostInDomain(string $host, string $domain): bool
    {
        $domain = trim($domain, '.');
        return $domain !== '' && preg_match('/(?:^|\.)'.preg_quote($domain, '/').'$/iD', $host) === 1;
    }
