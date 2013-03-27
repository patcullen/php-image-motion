<?

function listFilesInDirectory($path) {
	$ia = array();
	$ih = @opendir($path) or die("Unable to open f $path");
	while ($img = readdir($ih)) {
		if(is_dir($path.$img) || $img == "." || $img == ".." || $img == "thumb" || $img == "Thumbs" || $img == "thumbs"  || $img == "Thumbs.db" ) continue;
		$ia[] = $img;
	}
	closedir($ih);
	return $ia;
}





?>
