<?php

class State {

	var $map, $resX, $resY, $source;
	var $average, $standardDeviation;
	
	public function State($resX, $resY, $img = null) {
		$this->resX = $resX;
		$this->resY = $resY;
		if ($img != null) 
			$this->fromImage($img);
	}
	
	public function fromImage($image) {
		$width = $image->getWidth() / $this->resX;
		$height = $image->getHeight() / $this->resY;
		$this->map = array();
		$img = $image->getImage();
		for ($y = 0; $y < $this->resY; $y++) {
			$this->map[$y] = array();
			for ($x = 0; $x < $this->resX; $x++) {
				$ga = array("r" => 0, "g" => 0, "b" => 0);
				for ($yi = 0; $yi < $height; $yi++)
					for ($xi = 0; $xi < $width; $xi++) {
						$v = imagecolorat($img, ($x * $width) + $xi, ($y * $height) + $yi);
						$ga["r"] += ($v >> 16) & 0xFF;
						$ga["g"] += ($v >> 8) & 0xFF;
						$ga["b"] += $v & 0xFF;
					}
				$ga["r"] /= ($width * $height);
				$ga["g"] /= ($width * $height);
				$ga["b"] /= ($width * $height);
				$this->map[$y][$x] = $ga;
			}
		}
	}
	
	public function difference($state, $function = null) {
		$map = array();
		for ($y = 0; $y < $this->resY; $y++) {
			$map[$y] = array();
			for ($x = 0; $x < $this->resX; $x++) {
				if (isset($function)) {
					$map[$y][$x] = $function($this->map[$y][$x], $state->map[$y][$x]);
				} else {
					$map[$y][$x] = $this->map[$y][$x] - $state->map[$y][$x];
				}
			}
		}
		$i = new State($this->resX, $this->resY);
		$i->map = $map;
		return $i;
	}
	
	public function avg() {
		if (empty($this->average)) {
			$this->average = 0;
			for ($y = 0; $y < $this->resY; $y++)
				for ($x = 0; $x < $this->resX; $x++)
					$this->average += $this->map[$y][$x];
			$this->average /= ($this->resX * $this->resY);
		}
		return $this->average;
	}
	
	public function max() {
		$this->maximum = 0;
		for ($y = 0; $y < $this->resY; $y++)
			for ($x = 0; $x < $this->resX; $x++)
				if ($this->map[$y][$x] > $this->maximum)
					$this->maximum = $this->map[$y][$x];
		return $this->maximum;
	}
	
	public function stdDev() {
		$this->standardDeviation = 0;
		$avg = $this->avg();
		for ($y = 0; $y < $this->resY; $y++) {
			for ($x = 0; $x < $this->resX; $x++) {
				$m = ($this->map[$y][$x] - $avg) * ($this->map[$y][$x] - $avg);
				$this->standardDeviation += $m;
			}
		}
		$this->standardDeviation /= (($this->resX * $this->resY)-1);
		$this->standardDeviation = sqrt($this->standardDeviation);
		return $this->standardDeviation;
	}
	
	public function denoiseStdDev() {
		$dev = $this->stdDev();
		for ($y = 0; $y < $this->resY; $y++)
			for ($x = 0; $x < $this->resX; $x++)
				if (abs($this->map[$y][$x]) < $dev)
					$this->map[$y][$x] = 0;
		return $this;
	}
	
	public function scale($top) {
		$max = $this->max(); 
		for ($y = 0; $y < $this->resY; $y++)
			for ($x = 0; $x < $this->resX; $x++)
				$this->map[$y][$x] = ($this->map[$y][$x] / $max) * $top;
		return $this;
	}
	
	public function round($round) {
		for ($y = 0; $y < $this->resY; $y++)
			for ($x = 0; $x < $this->resX; $x++)
				$this->map[$y][$x] = round($this->map[$y][$x], $round);
		return $this;
	}
	
	public function abs() {
		for ($y = 0; $y < $this->resY; $y++)
			for ($x = 0; $x < $this->resX; $x++)
				$this->map[$y][$x] = abs($this->map[$y][$x]);
		return $this;
	}
		
	public function toString() {
		$s = "";
		for ($y = 0; $y < $this->resY; $y++) {
			for ($x = 0; $x < $this->resX; $x++) {
				$s .= $this->map[$y][$x]."\t";
			}
			$s .= "\n";
		}
		return $s;
	}

	public function drawImageIndicator($image) {
		$max = $this->max(); 
		$i = $image->getImage();
		$width = $image->getWidth() / $this->resX;
		$height = $image->getHeight() / $this->resY;
		$black = imagecolorallocate($i, 0, 0, 0);
		for ($y = 0; $y < $this->resY; $y++) {
			for ($x = 0; $x < $this->resX; $x++) {
				$v = ($this->map[$y][$x] / $max) * 255;
				if ($v > 0) {
					$color = imagecolorallocate($i, $v, $v, $v);
					imagerectangle($i, $x * $width, $y * $height, $x * $width + $width - 1, $y * $height + $height - 1, $color);
					imagestring($i, 2, $x * $width + 3, $y * $height + 1, $this->map[$y][$x], $black);
					imagestring($i, 2, $x * $width + 1, $y * $height - 1, $this->map[$y][$x], $black);
					imagestring($i, 2, $x * $width + 2, $y * $height, $this->map[$y][$x], $color);
				}
			}
		}
		return new SimpleImage($i);
	}
	
	public function getBoundingBox($w = null, $h = null) {
		if (!isset($w)) $w = $this->resX;
		if (!isset($h)) $h = $this->resY;
		
		$ax = $this->resX;
		$bx = 0;
		$ay = $this->resX;
		$by = 0;
		for ($y = 0; $y < $this->resY; $y++) {
			for ($x = 0; $x < $this->resX; $x++) {
				if ($this->map[$y][$x] > 0) {
					if ($x > $bx) $bx = $x;
					if ($x < $ax) $ax = $x;
					if ($y > $by) $by = $y;
					if ($y < $ay) $ay = $y;
				}
			}
		}
		if ($ax > $bx) {
			return null;
		} else {
			$ax = ($ax / $this->resX) * $w;
			$bx = ((($bx+1) / $this->resX) * $w) - $ax;
			$ay = ($ay / $this->resY) * $h;
			$by = ((($by+1) / $this->resY) * $h) - $ay;
			return array("x" => $ax, "y" => $ay, "w" => $bx, "h" => $by);
		}
	}
	
	public function getCenterOfGravity($w = null, $h = null) {
		if (!isset($w)) $w = $this->resX;
		if (!isset($h)) $h = $this->resY;
		
		$box = $this->getBoundingBox();
		
		$tx = 0;
		$ty = 0;
		$m = 0;
		for ($y = 0; $y < $this->resY; $y++) {
			for ($x = 0; $x < $this->resX; $x++) {
				if ($this->map[$y][$x] > 0) {
					$m += $this->map[$y][$x];
					$tx += $this->map[$y][$x] * (($x+1) - $box["x"]);
					$ty += $this->map[$y][$x] * (($y+1) - $box["y"]);
				}
			}
		}
		$tx = (($tx / $m)-1) * ($w/$this->resX);
		$ty = (($ty / $m)-1) * ($h/$this->resY);
		$tx += ($w/$this->resX) * $box["x"] + (($w/$this->resX)/2);
		$ty += ($h/$this->resY) * $box["y"] + (($h/$this->resY)/2);
		return array("x" => $tx, "y" => $ty);
	}
	
}

function rgbColorDistance ($x, $y) {
	$r = $x["r"] - $y["r"];
	$r *= $r;
	$g = $x["g"] - $y["g"];
	$g *= $g;
	$b = $x["b"] - $y["b"];
	$b *= $b;
	$v = $r + $g + $b;
	return sqrt($v);
}



?>
