<?php
/**
 * Image resizer class.
 *
 * @author Dmitry Kourinski
 */
class O_Image_Resizer {
	private $src;
	private $ext;

	/**
	 * Creates resizer instance for specified image file
	 *
	 * @param string $filesource
	 * @throws O_Ex_NotFound if filesource is not readable
	 */
	public function __construct( $filesource )
	{
		$this->src = $filesource;
		if (!is_readable( $this->src ))
			throw new O_Ex_NotFound( "Image file is not found or is not readable." );
	}

	/**
	 * Returns image extension
	 *
	 * @return string
	 */
	public function getExtension()
	{
		if ($this->ext)
			return $this->ext;
		list (, , $type, ) = getimagesize( $this->src );
		return $this->ext = image_type_to_extension( $type );
	}

	/**
	 * Resizes image keeping its proportions and type, saves it on disc
	 *
	 * @param int $max_width
	 * @param int $max_height
	 * @param string $target_file
	 * @param bool $substitute_ext If set to true, ".$ext" will be substituted to target filename, if it's specified
	 * @return string Output filename
	 * @throws O_Ex_WrongArgument if image has unknown type
	 */
	public function resize( $max_width, $max_height, $target_file = null, $substitute_ext = false )
	{
		list ($width, $height, $type, ) = getimagesize( $this->src );
		
		if (!$target_file) {
			$target_file = $this->src;
		} elseif ($substitute_ext) {
			$target_file .= $this->ext ? $this->ext : image_type_to_extension( $type );
		}
		
		if ($type != IMAGETYPE_GIF && $type != IMAGETYPE_JPEG && $type != IMAGETYPE_PNG) {
			throw new O_Ex_WrongArgument( 
					"Need the image to be in png, jpg or gif format to process it correctly." );
		}
		
		// We do not need to resize image, so just copy it if needed
		if (($width <= $max_width && $height <= $max_height) || (!$max_height && !$max_width)) {
			if ($this->src != $target_file) {
				if (is_file( $target_file ))
					unlink( $target_file );
				copy( $this->src, $target_file );
			}
			return $target_file;
		}
		
		// Resizing is necessary
		$k = $width / $max_width > $height / $max_height ? $width / $max_width : $height /
						 $max_height;
		switch ($type) {
			case IMAGETYPE_GIF :
				$im = imagecreatefromgif( $this->src );
			break;
			case IMAGETYPE_JPEG :
				$im = imagecreatefromjpeg( $this->src );
			break;
			case IMAGETYPE_PNG :
				$im = imagecreatefrompng( $this->src );
			break;
		}
		$newim = imagecreatetruecolor( round( $width / $k ), round( $height / $k ) );
		imagecopyresized( $newim, $im, 0, 0, 0, 0, imagesx( $newim ), imagesy( $newim ), $width, 
				$height );
		imagedestroy( $im );
		
		if (is_file( $target_file ))
			unlink( $target_file );
		switch ($type) {
			case IMAGETYPE_GIF :
				imagegif( $newim, $target_file );
			break;
			case IMAGETYPE_JPEG :
				imagejpeg( $newim, $target_file );
			break;
			case IMAGETYPE_PNG :
				imagepng( $newim, $target_file );
			break;
		}
		
		imagedestroy( $newim );
		return $target_file;
	}

}