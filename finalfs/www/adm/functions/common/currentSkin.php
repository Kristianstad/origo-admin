<?php

function currentSkin(array $skins): array
{
	$skinId=$_COOKIE['origo_admin_skin'] ?? null;
	if (is_string($skinId))
	{
		$skin=arrayColumnSearch($skinId, 'skin_id', $skins);
		if (!empty($skin))
		{
			return $skin;
		}
	}
	return array();
}