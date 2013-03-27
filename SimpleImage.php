<?

class SimpleImage {

	var $image;
	var $image_type;
	
	function SimpleImage($img, $img_type = IMAGETYPE_JPEG) {
		if (gettype($img) == "string") {
			$this->load($img);
		} else {
			$this->image = $img;
			$this->image_type == $img_type;
		}
	}
	
	function destroy() {
		if (is_set($this->image)) {
			imagedestroy($this->image);
		}
	}

	function load($filename) {
		$image_info = getimagesize($filename);
		$this->image_type = $image_info[2];
		if( $this->image_type == IMAGETYPE_JPEG ) {
			$this->image = imagecreatefromjpeg($filename);
		} elseif( $this->image_type == IMAGETYPE_GIF ) {
			$this->image = imagecreatefromgif($filename);
		} elseif( $this->image_type == IMAGETYPE_PNG ) {
			$this->image = imagecreatefrompng($filename);
		}
	}
	
	function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {
		if( $image_type == IMAGETYPE_JPEG ) {
			imagejpeg($this->image,$filename,$compression);
		} elseif( $image_type == IMAGETYPE_GIF ) {
			imagegif($this->image,$filename);         
		} elseif( $image_type == IMAGETYPE_PNG ) {
			imagepng($this->image,$filename);
		}   
		if( $permissions != null) {
			chmod($filename,$permissions);
		}
	}
	
	function output($image_type=IMAGETYPE_JPEG) {
		if( $image_type == IMAGETYPE_JPEG ) {
			header('Content-Type: image/jpeg');
			imagejpeg($this->image);
		} elseif( $image_type == IMAGETYPE_GIF ) {
			header('Content-Type: image/gif');
			imagegif($this->image);         
		} elseif( $image_type == IMAGETYPE_PNG ) {
			header('Content-Type: image/png');
			imagepng($this->image);
		}   
	}
	
	function getWidth() {
		return imagesx($this->image);
	}
	
	function getHeight() {
		return imagesy($this->image);
	}
	
	function getImage() {
		return $this->image;
	}
	
	function resizeToHeight($height) {
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		$this->resize($width,$height);
	}
	
	function resizeToWidth($width) {
		$ratio = $width / $this->getWidth();
		$height = $this->getheight() * $ratio;
		$this->resize($width,$height);
	}
	
	function scale($scale) {
		$width = $this->getWidth() * $scale/100;
		$height = $this->getheight() * $scale/100; 
		$this->resize($width,$height);
	}
	
	function resize($width,$height) {
		$new_image = imagecreatetruecolor($width, $height);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
		$this->image = $new_image;
	}
	      
	function crop($x, $y, $width,$height) {
		$new_image = imagecreatetruecolor($width, $height);
		imagecopyresampled($new_image, $this->image, 0, 0, $x, $y, $width, $height, $width, $height);
		$this->image = $new_image;   
	}      

	public function merge($images) {
		$imgArr = array($this);
		if (getType($images) == "array") {
			$imgArr = $images;
			$imgArr[] = $this;
		} else {
			$imgArr[] = $images;
		}
		$i = count($imgArr);
		// create blank canvas
		$w = $this->getWidth();
		$h = $this->getHeight();
		$new = imagecreatetruecolor($w, $h);
		// loop through all images pulling out rgb component and multiplying by above percentage
		for ($y = 0; $y < $h; $y++)
			for ($x = 0; $x < $w; $x++) {
				$r = 0; $g = 0; $b = 0;
				for ($o = 0; $o < $i; $o++) { 
					$v = imagecolorat($imgArr[$o]->getImage(), $x, $y);
					$r += ($v >> 16) & 0xFF;
					$g += ($v >> 8) & 0xFF;
					$b += $v & 0xFF;
				}
				$r /= $i;
				$g /= $i;
				$b /= $i;
				$color = imagecolorallocate($new, $r, $g, $b);
				imagesetpixel($new, $x, $y, $color);
			}
		imagedestroy($this->image);
		$this->image = $new;
	}
	
}

?>
