<?php
require_once(__DIR__ . "/class.php");

class SCSS {

	/**
	 * Process SCSS files from global assets folder.
	 */
	public static function loadWeb() {

		$folder = Path::assets() . "/scss";
		SCSS::loadDirectory($folder);

	}

	/**
	 * Compile SCSS files in this directory or subdirectories.
	 * 
	 * @param string $folder
	 */
	public static function loadDirectory(string $folder) {
		
		if (!is_dir($folder)) return;

		$files =  glob($folder . "/*.scss");
		if (sizeof($files) > 0) {

			$scss = new scssc();
			foreach($files as $file)
			{
				if ($file == "") continue;
			
				$name = file_name($file, false) . ".css";
	
				if (!is_dir($folder . "/compiled"))
					create_folder($folder . "/compiled");
	
				$output = $folder . "/compiled/$name";

				$renew = false;

				if (!file_exists($output)) {
					$renew = true;
				} else {

					$tc = new Time(last_modification_date($output));
					$ts = new Time(last_modification_date($file));

					if ($tc->isBefore($ts)) {
						$renew = true;
					}

				}
	
				if (!$renew) {
					$route = Path::toRelative($output);
					Assets::include_css($route);
				}
				else {
					$css = $scss->compile(file_get_contents($file));
					file_put_contents($output, $css);
				
					$route = Path::toRelative($output);
					Assets::include_css($route);
				}
			

			}
		}

	}

}