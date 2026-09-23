<?php

	// An associative array of the different views that the manage tool can switch between. 
	// Each key contains a views name and the corresponding value contains an array of names of configuration tables that should be displayed by the tool
	$views = array(
		'Allt'	=> array(),
		'Origo' => array('maps', 'controls', 'plugins', 'groups', 'layers', 'sources', 'services', 'footers'),
		'Extra' => array('tilegrids', 'proj4defs', 'formats', 'mapstates', 'news', 'adusers'),
		'Meta'	=> array('databases', 'schemas', 'tables', 'contacts', 'origins', 'updates', 'keywords'),
		'Infoförv' => array('classes', 'infogroups'),
		'Verktyg' => array('helps', 'exttools', 'skins', 'edits')
	);