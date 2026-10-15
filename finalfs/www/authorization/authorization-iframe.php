<!DOCTYPE html>
<html style="width:100%;height:100%">
	<body>
		<?php 
            require('../adm/constants/proxyRoot.php');
            $src=$proxyRoot.dirname($_SERVER["PHP_SELF"]).'/authorization-loader.php';
        ?>
		<iframe src="<?php echo $src; ?>" style="width:100%;height:100%;margin-top:-15px"></iframe>
	</body>
</html>