#!/usr/bin/php
<?php

/************************************************************************************************
 * VERARBEITUNGSABLAUF
 * 
 * 1.) Optimieren und Zwischenspeichern aller SVG-Dateien
 * 2.) Konvertieren in data-URIs (ASCII)
 * 2.a) Wenn eine data-URI > 32768 Zeichen: SVG-Sprite-Modus, Konstruieren des SVG-Sprite
 * 3.) Rendern des SVG-CSS mit data-URIs oder Referenz auf den SVG-Sprite
 * 
 * 4.) Rendern aller SVG -> PNG (phantomJS)
 * 5.) Crushen aller PNG-Bilder in Kopien
 * 6.) Konvertieren in data-URIs (Base64)
 * 6.a) Wenn eine data-URI > 32768 Zeichen: PNG-Sprite-Modus, Konstruieren des PNG-Sprites aus
 * 		den unkomprimierten Basis-PNGs, anschließendes crushen
 *  3.) Rendern des PNG-CSS mit data-URIs oder Referenz auf den PNG-Sprite
 * 
 ***********************************************************************************************/

namespace Jkphl;

// Require Zend classes for argument validation
require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'Zend'.DIRECTORY_SEPARATOR.'Console'.DIRECTORY_SEPARATOR.'Exception'.DIRECTORY_SEPARATOR.'ExceptionInterface.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'Zend'.DIRECTORY_SEPARATOR.'Console'.DIRECTORY_SEPARATOR.'Exception'.DIRECTORY_SEPARATOR.'BadMethodCallException.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'Zend'.DIRECTORY_SEPARATOR.'Console'.DIRECTORY_SEPARATOR.'Exception'.DIRECTORY_SEPARATOR.'InvalidArgumentException.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'Zend'.DIRECTORY_SEPARATOR.'Console'.DIRECTORY_SEPARATOR.'Exception'.DIRECTORY_SEPARATOR.'RuntimeException.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'Zend'.DIRECTORY_SEPARATOR.'Console'.DIRECTORY_SEPARATOR.'Getopt.php';

/**
 * Iconizr class
 * 
 * @author joschi
 *
 */
class Iconizr {
	/**
	 * Command line options
	 * 
	 * @var \Zend\Console\Getopt
	 */
	protected $_options = null;
	/**
	 * Default options
	 * 
	 * @var array
	 */
	protected $_defaultOptions = array(
		'prefix'	=> self::DEFAULT_PREFIX,
		'level'		=> self::DEFAULT_LEVEL,
		'speed'		=> 2,
		'css'		=> 0,
		'sass'		=> 0,
		'quantize'	=> 0,
		'dims'		=> 0,
		'verbose'	=> 0,
		'keep'		=> 0,
		'svg'		=> self::DEFAULT_THRESHOLD_SVG,
		'png'		=> self::DEFAULT_THRESHOLD_PNG,
	);
	/**
	 * Supporting binaries
	 * 
	 * @var array
	 */
	protected $_binaries = array(
		'svgo'		=> false,
		'phantomjs'	=> false,
		'pngcrush'	=> false,
		'pngquant'	=> false,
		'optipng'	=> false,
	);
	/**
	 * SVG directories
	 * 
	 * @var array
	 */
	protected $_dirs = array();
	/**
	 * Target directory
	 * 
	 * @var array
	 */
	protected $_target = null;
	/**
	 * Temporary directory
	 * 
	 * @var string
	 */
	protected $_tmpDir = null;
	/**
	 * Temporary directory name
	 * 
	 * @var string
	 */
	protected $_tmpName = null;
	/**
	 * Temporary resources
	 * 
	 * @var array
	 */
	protected $_tmpResources = array();
	/**
	 * CSS
	 * 
	 * @var array
	 */
	protected $_css = array(
		self::SVG			=> array(
			self::DATA		=> array(),
			self::SPRITE	=> array(),
		),
		self::PNG			=> array(
			self::DATA		=> array(),
			self::SPRITE	=> array(),
		),
	);
	/**
	 * Sass
	 *
	 * @var array
	 */
	protected $_sass = array(
		self::SVG			=> array(
			self::DATA		=> array(),
			self::SPRITE	=> array(),
		),
		self::PNG			=> array(
			self::DATA		=> array(),
			self::SPRITE	=> array(),
		),
	);
	/**
	 * Data URIs
	 *
	 * @var string
	 */
	protected $_dataUris = array();
	/**
	 * Use sprites
	 * 
	 * @var array
	 */
	protected $_useSprite = array();
	/**
	 * Icon dimensions
	 *
	 * @var array
	 */
	protected $_dimensions = array();
	/**
	 * Icon names
	 * 
	 * @var array
	 */
	protected $_iconNames = array();
	/**
	 * CSS class prefix
	 * 
	 * @var string
	 */
	protected $_prefix = self::DEFAULT_PREFIX;
	/**
	 * Quantize speed
	 * 
	 * @var string
	 */
	protected $_speed = 3;
	/**
	 * Optimization level
	 *
	 * @var string
	 */
	protected $_optimization = 2;
	/**
	 * Effective data URI byte thresholds
	 * 
	 * @var int
	 */
	protected $_thresholds = array(
		self::SVG			=> self::DEFAULT_THRESHOLD_SVG,
		self::PNG			=> self::DEFAULT_THRESHOLD_PNG,
	);
	/**
	 * Flags
	 * 
	 * @var array
	 */
	protected $_flags = array();
	/**
	 * PhantomJS script template
	 * 
	 * @var string
	 */
	protected static $_phantomJSSCript = 'var icons=%s,svg=function(){if(icons.length){var a=icons.pop();page.viewportSize={width:a[2],height:a[3]};page.open(a[0],function(){page.render(a[1]),svg()})}else phantom.exit()},page=require("webpage").create();svg();';
	/**
	 * SVG
	 * 
	 * @var string
	 */
	const SVG = 'svg';
	/**
	 * PNG
	 * 
	 * @var string
	 */
	const PNG = 'png';
	/**
	 * Data URI mode
	 * 
	 * @var string
	 */
	const DATA = 'data';
	/**
	 * Sprite mode
	 * 
	 * @var string
	 */
	const SPRITE = 'sprite';
	/**
	 * Default CSS class prefix
	 * 
	 * @var string
	 */
	const DEFAULT_PREFIX = 'icon';
	/**
	 * Default optimization level
	 * 
	 * @var ing
	 */
	const DEFAULT_LEVEL = 3;
	/**
	 * Default CSS and Sass file name prefix
	 *
	 * @var string
	 */
	const DEFAULT_FILE = 'iconizr';
	/**
	 * Default byte threshold for SVG data URIs
	 *
	 * @var int
	 */
	const DEFAULT_THRESHOLD_SVG = 1048576;
	/**
	 * Default byte threshold for PNG data URIs
	 * 
	 * @var int
	 */
	const DEFAULT_THRESHOLD_PNG = 32768;
	
	/************************************************************************************************
	 * PUBLIC METHODS
	 ***********************************************************************************************/
	
	/**
	 * Constructor
	 * 
	 * @return void
	 */
	public function __construct() {
		
		// Find the binary helpers supported by the system
		foreach ($this->_binaries as $name => $available) {
			$return								= 0;
			$output								= array();
			$binary								= @exec('which '.escapeshellarg($name), $output, $return);
			$this->_binaries[$name]				= (!intval($return) && strlen($binary)) ? $binary : false;
		}
		
		// Parse & validate the command line options
		try {
			$this->_options						= new \Zend\Console\Getopt(array(
				'out|o=s'						=> 'Output directory',
				'prefix|p=s'					=> 'CSS class prefix (default: '.self::DEFAULT_PREFIX.')',
				'level|l=i'						=> 'PNG image optimization level: 0 (fast & rough) - 10 (slow & high quality), default: '.self::DEFAULT_LEVEL,
				'svg=i'							=> 'Data URI byte threshold for SVG files, default: '.self::DEFAULT_THRESHOLD_SVG,
				'png=i'							=> 'Data URI byte threshold for PNG files, default: '.self::DEFAULT_THRESHOLD_PNG,
				'css|c-s'						=> 'Render CSS files (optionally provide a CSS file prefix, default: iconizr)',
				'sass|s-s'						=> 'Render Sass files (optionally provide a Sass file prefix, default: iconizr)',
				'quantize|q'					=> 'Quantize PNG images (reduce to 8-bit color depth)',
				'dims|d'						=> 'Render icon dimensions in CSS and Sass files',
				'keep|k'						=> 'Keep intermediate SVG and PNG files',
				'verbose|v-i'					=> 'Output verbose progress information',
			));
			$options							= $this->_defaultOptions;
			foreach ($this->_options->getOptions() as $option) {
				$options[$option]				= $this->_options->getOption($option);
			}
			
		// In case of errors: Die with a usage message
		} catch(\Zend\Console\Exception\ExceptionInterface $e) {
			$this->_usage($e->getMessage());
		}
		
		// Verify the output directory
		if (array_key_exists('out', $options) && strlen($target = trim($options['out']))) {
			$workingDirectory					= rtrim(getcwd(), DIRECTORY_SEPARATOR);
			$target								= strncmp($target, DIRECTORY_SEPARATOR, 1) ? $workingDirectory.DIRECTORY_SEPARATOR.$target : $target;
			if (strncmp($workingDirectory, $target, strlen($workingDirectory))) {
				$this->_usage('The output directory must be a subdirectory of the current working directory');
			}
			if (@is_dir($target) || (@mkdir($target, 0644, true) && chown($target, 0644))) {
				$this->_target					= $target.DIRECTORY_SEPARATOR;
			}
		}
		if ($this->_target === null) {
			$this->_usage('Please provide a valid output directory');
		}
		
		// Set the CSS class prefix
		$this->_prefix							= strlen(trim($options['prefix'])) ? trim($options['prefix']) : self::DEFAULT_PREFIX;

		// Determine quantize speed, optimization level and other flags
		$level									= max(0, min(10, intval($options['level'])));
		$this->_speed							= 10 - $level;
		$this->_optimization					= round($level * 7/10);
		$this->_thresholds[self::SVG]			= max(1024, intval($options['svg']));
		$this->_thresholds[self::PNG]			= max(1024, intval($options['png']));
		$this->_flags['css']					= (is_string($options['css']) && strlen(trim($options['css']))) ? trim($options['css']) : (intval($options['css']) ? self::DEFAULT_FILE : false);
		$this->_flags['sass']					= (is_string($options['sass']) && strlen(trim($options['sass']))) ? trim($options['sass']) : (intval($options['sass']) ? self::DEFAULT_FILE : false);
		$this->_flags['verbose']				= intval($options['verbose']);
		$this->_flags['quantize']				= !!$options['quantize'];
		$this->_flags['dims']					= !!$options['dims'];
		$this->_flags['keep']					= !!$options['keep'];
		
		// Prepare projected output elements
		$outputElements							= array();
		if ($this->_flags['css'] !== false) {
			$outputElements[]					= $this->_target.$this->_flags['css'].'-svg-data.css';
			$outputElements[]					= $this->_target.$this->_flags['css'].'-svg-sprite.css';
			$outputElements[]					= $this->_target.$this->_flags['css'].'-png-data.css';
			$outputElements[]					= $this->_target.$this->_flags['css'].'-png-sprite.css';
			$outputElements[]					= $this->_target.$this->_flags['css'].'-loader-fragment.html';
			$outputElements[]					= $this->_target.$this->_flags['css'].'-preview.php';
		}
		if ($this->_flags['sass'] !== false) {
			$outputElements[]					= $this->_target.$this->_flags['sass'].'-svg-data.scss';
			$outputElements[]					= $this->_target.$this->_flags['sass'].'-svg-sprite.scss';
			$outputElements[]					= $this->_target.$this->_flags['sass'].'-png-data.scss';
			$outputElements[]					= $this->_target.$this->_flags['sass'].'-png-sprite.scss';
		}
		
		// Extract and run through all directories
		foreach ($this->_options->getRemainingArgs() as $dir) {
			$dir								= @realpath($dir);
			if (strlen($dir) && @is_dir($dir)) {
				$this->_dirs[$dir]				= array();

				// Gather all SVG files in the directory
				foreach (scandir($dir) as $file) {
					$absFile					= $dir.DIRECTORY_SEPARATOR.$file;
					if (@is_file($absFile) && (($extension = strtolower(pathinfo($absFile, PATHINFO_EXTENSION))) == 'svg')) {
						$this->_dirs[$dir][]	= $file;
					}
				}
				
				// If there are SVG files to be converted
				if (count($this->_dirs[$dir])) {
					$outputElements[]			= $this->_target.$this->_uniqueName($dir);
					
				// Else: Drop the input directory again
				} else {
					unset($this->_dirs[$dir]);
				}
			}
		}
		
		// If at least one input directory is given
		if (count($this->_dirs)) {
			$cssFiles										= array();
			
			// Remove output elements if already present
			$this->_delete($outputElements);
		
			// Run through all directories and create icon packs
			foreach ($this->_dirs as $directory => $icons) {
				$this->_createIconStack($directory, $icons);
			}
			
			// Write out all CSS code
			if ($this->_flags['css'] !== false) {
				foreach ($this->_css as $type => $modeContent) {
					foreach ($modeContent as $mode => $content) {
						if (is_array($content) && count($content)) {
							$css				= $this->_target.$this->_flags['css'].'-'.$type.'-'.$mode.'.css';
							if (file_put_contents($css, implode("\n", $content))) {
								if (!array_key_exists($type, $cssFiles)) {
									$cssFiles[$type]		= array();
								}
								$cssFiles[$type][$mode]		= strtr(ltrim(substr($css, strlen($workingDirectory)), DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR, '/');
							}
						}
					}
				}
			}
			
			// Write out all Sass code
			if ($this->_flags['sass'] !== false) {
				foreach ($this->_sass as $type => $modeContent) {
					foreach ($modeContent as $mode => $content) {
						if (is_array($content) && count($content)) {
							file_put_contents($this->_target.$this->_flags['sass'].'-'.$type.'-'.$mode.'.scss', implode("\n", $content));
						}
					}
				}
			}
			
			// Write the loader fragment
			if (count($cssFiles)) {
				$this->_createPreviewAndLoaderFragment($cssFiles);
			}
			
		// Else
		} else {
			$this->_usage('Please provide at least one input directory containing SVG files');
		}
	}
	
	
	/************************************************************************************************
	 * PRIVATE METHODS
	 ***********************************************************************************************/

	/**
	 * Create the loader HTML fragment for the generated CSS files
	 * 
	 * @param array $css				Generated CSS files
	 * @return void
	 */
	protected function _createPreviewAndLoaderFragment(array $css) {
		$this->_log('Creating stylesheet loader fragment');
		$loader											= '<script>';
		$loader											.= '/* iconizr | https://github.com/jkphl/iconizr | © 2013 Joschi Kuphal | CC BY 3.0 */';
		$loader											.= file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'iconizr.min.js');
		$loader											.= '</script><noscript><link href="'.htmlspecialchars($css[self::PNG][self::SPRITE]).'" rel="stylesheet"></noscript>';
		file_put_contents($this->_target.$this->_flags['css'].'-loader-fragment.html', sprintf($loader, htmlspecialchars($css[self::PNG][self::SPRITE]), htmlspecialchars($css[self::PNG][self::DATA]), htmlspecialchars($css[self::SVG][self::SPRITE]), htmlspecialchars($css[self::SVG][self::DATA])));
		
		$this->_log('Creating preview document');
		$stylesheets									= array(
			''											=> 'Automatic detection',
			basename($css[self::PNG][self::SPRITE])		=> 'PNG sprite',
			basename($css[self::PNG][self::DATA])		=> 'PNG data URIs',
			basename($css[self::SVG][self::SPRITE])		=> 'SVG sprite',
			basename($css[self::SVG][self::DATA])		=> 'SVG data URIs',
				
		);
		$preview										= '<?php $stylesheets = '.var_export($stylesheets, true).'; $stylesheet = empty($_POST["stylesheet"]) ? "" : $_POST["stylesheet"]; ?>';
		$preview										.= '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><title>iconizr Icon Preview</title>';
		$preview										.= '<?php if ($stylesheet == ""): ?>';
		$preview										.= sprintf($loader, htmlspecialchars(basename($css[self::PNG][self::SPRITE])), htmlspecialchars(basename($css[self::PNG][self::DATA])), htmlspecialchars(basename($css[self::SVG][self::SPRITE])), htmlspecialchars(basename($css[self::SVG][self::DATA])));
		$preview										.= '<?php else: ?><link href="<?php echo htmlspecialchars($stylesheet); ?>" rel="stylesheet" type="text/css"/><?php endif; ?>';
		$preview										.= '<style>body{padding:2em;margin:0}body,p,h1{font-family:Arial,Helvetica,sans-serif}ul{margin:0,padding:0}li{margin:2em 0}.icon{background-color:#eee}</style></head><body>';
		$preview										.= '<form method="post"><h1>iconizr Icon Preview</h1><p>Please choose the icon rendering type: <select name="stylesheet"><?php foreach($stylesheets as $value => $label): ?><option value="<?php echo htmlspecialchars($value); ?>"<?php if($stylesheet == $value) echo \' selected="selected"\'; ?>><?php echo htmlspecialchars($label); ?></option><?php endforeach; ?></select><input type="submit" value="Set rendering type"/></p><ol>';
		foreach ($this->_iconNames as $icon) {
			$preview									.= '<li><div>'.$icon.'</div><div class="icon '.$icon.' '.$icon.'-dims"></div></li>';
		}
		$preview										.= '</ol></form></body></html>';
		file_put_contents($this->_target.$this->_flags['css'].'-preview.php', $preview);
	}
	
	/**
	 * Create a single icon stack
	 * 
	 * @param string $directory					Directory
	 * @param array $icons						SVG icons
	 * @return void
	 */
	protected function _createIconStack($directory, array $icons) {
		$this->_log(sprintf('Processing icon directory "%s"', $directory));
		
		// Create a temporary directory
		$this->_tmpResources					= array();
		$this->_tmpName							= $this->_uniqueName($directory);
		$this->_tmpDir							= $this->_target.$this->_tmpName;
		if (!@is_dir($this->_tmpDir) && !@mkdir($this->_tmpDir, 0777, true)) {
			$this->_error(sprintf('Could not create temporary directory "%s", exiting', $this->_tmpDir));
		}
		
		// Prepare variables
		$this->_dimensions[$directory]			= array();
		$this->_dataUris[$directory]			=
		$this->_useSprite[$directory]			= array(
			self::SVG							=> array(),
			self::PNG							=> array(),
		);
		
		// Process the SVG icons
		$this->_processSVGIcons($directory, $icons);
		
		// Process the PNG icons
		$this->_processPNGIcons($directory, $icons);
		
		// Remove the intermediate files if they shouldn't be kept
		if (!$this->_flags['keep']) {
			$this->_delete($this->_tmpResources);
		}
	}
	
	/**
	 * Process the SVG icons
	 * 
	 * @param string $directory					Directory
	 * @param array $icons						SVG icons
	 * @return void
	 */
	protected function _processSVGIcons($directory, array $icons) {
		$this->_log('=== Processing SVG icons ...');
		if ($this->_binaries['svgo']) {
			$this->_log('|-- Optimizing SVG icons ...');
		}
		
		// Run through all icons
		foreach ($icons as $icon) {
			$iconName							= pathinfo($icon, PATHINFO_FILENAME);
			$this->_iconNames[]					= (strlen($this->_prefix) ? $this->_prefix : $this->_tmpName).'-'.$iconName;
			$targetIcon							= $this->_tmpDir.DIRECTORY_SEPARATOR.$icon;
			$this->_tmpResources[]				= $targetIcon;
				
			// If the SVGO binary is available
			if ($this->_binaries['svgo']) {
				$this->_log(sprintf('|   Optimizing SVG icon "%s"', basename($targetIcon)));
		
				// Create an optimized copy of the icon
				if (!$this->_do($this->_binaries['svgo'], array(
					'-i'						=> $directory.DIRECTORY_SEPARATOR.$icon,
					'-o'						=> $targetIcon,
				))) {
					$this->_error(sprintf('Optimization of icon "%s" failed, exiting', basename($targetIcon)));
				}
		
			// Else
			} elseif (!@copy($directory.DIRECTORY_SEPARATOR.$icon, $targetIcon)) {
				$this->_error(sprintf('Could not copy icon "%s", exiting', basename($targetIcon)));
			}
				
			// Create a data URI from this SVG if sprite mode is not active (yet)
			if (is_array($this->_dataUris[$directory][self::SVG])) {
				$this->_dataUris[$directory][self::SVG][$iconName]		= 'data:image/svg+xml,'.rawurlencode(@file_get_contents($targetIcon));
				if (($dataUriLength = strlen($this->_dataUris[$directory][self::SVG][$iconName])) > $this->_thresholds[self::SVG]) {
					$this->_dataUris[$directory][self::SVG]				= false;
					$this->_log(sprintf('>>> Data URI for icon "%s" exceeds %s byte limit (%s), switching to SVG sprite only mode', $icon, $this->_thresholds[self::SVG], $dataUriLength));
				}
			}
		
			$this->_useSprite[$directory][self::SVG][$iconName]			= $targetIcon;
		}
		
		// If data URIs can be used
		if ($dataUris = is_array($this->_dataUris[$directory][self::SVG])) {
		
			// If CSS rules shall be generated
			if ($this->_flags['css'] !== false) {
				$this->_css[self::SVG][self::DATA]						= array_merge($this->_css[self::SVG][self::DATA], $this->_createDataURICssRules($directory, $this->_dataUris[$directory][self::SVG]));
			}
			
			// If Sass rules shall be generated
			if ($this->_flags['css'] !== false) {
				$this->_sass[self::SVG][self::DATA]						= array_merge($this->_sass[self::SVG][self::DATA], $this->_createDataURISassRules($directory, $this->_dataUris[$directory][self::SVG]));
			}
		}

		// Create SVG sprite and appropriate rules
		$this->_createSVGIconSpriteAndRules($directory, $this->_useSprite[$directory][self::SVG], !$dataUris);
	}
	
	/**
	 * Process the PNG icons
	 *
	 * @param string $directory					Directory
	 * @param array $icons						SVG icons
	 * @return void
	 */
	protected function _processPNGIcons($directory, array $icons) {
		$this->_log('=== Processing PNG icons ...');
		
		// If the phantomJS binary is available
		if ($this->_binaries['phantomjs']) {
			chdir($this->_tmpDir);
			$icons								= array();
			
			// Run through all icons, create phantomJS scripts an run phantomJS
			foreach ($this->_useSprite[$directory][self::SVG] as $name => $icon) {
				try {
					$icons[]													= array_merge(array(basename($icon), $name.'.png'), $this->_getIconDimensions($directory, $icon, $name));
					$this->_tmpResources[]										=
					$this->_useSprite[$directory][self::PNG][$name]				= $this->_tmpDir.DIRECTORY_SEPARATOR.$name.'.png';
				} catch (\Exception $e) {
					$this->_error('Problem processing icon "%s": '.$e->getMessage(), $name);
				}
			}

			$phantomJSScript													= $this->_tmpDir.DIRECTORY_SEPARATOR.'iconizr.js';
			if (!@file_put_contents($phantomJSScript, sprintf(self::$_phantomJSSCript, json_encode($icons)))) {
				$this->_error('Could not create PhantomJS script file, exiting');
			}

			$this->_log('|   Rendering SVG icons to PNG images ...');
			$rendered															= $this->_do($this->_binaries['phantomjs'], array('iconizr.js'));
			@unlink($phantomJSScript);
			if (!$rendered) {
				$this->_error('Could not render SVG images, exiting');
			}
			
			// Optimize and run through als PNG images
			$this->_log('|-- Optimizing PNG images ...');
			$optimizePNGImages													= $this->_optimizePNGImages($this->_useSprite[$directory][self::PNG]);
			foreach ($optimizePNGImages as $png => $optimized) {
				$targetIcon														= $this->_tmpDir.DIRECTORY_SEPARATOR.$optimized;
				if (($targetIcon != $png) && (filesize($targetIcon) < filesize($png))) {
					unlink($png);
					rename($targetIcon, $png);
				}
				$iconName														= array_search($png, $this->_useSprite[$directory][self::PNG]);
				
				// Create a data URI from this optimized PNG if sprite mode is not active (yet)
				if (is_array($this->_dataUris[$directory][self::PNG])) {
					$this->_dataUris[$directory][self::PNG][$iconName]			= 'data:image/png;base64,'.base64_encode(file_get_contents($png));
					if (($dataUriLength = strlen($this->_dataUris[$directory][self::PNG][$iconName])) > $this->_thresholds[self::PNG]) {
						$this->_dataUris[$directory][self::PNG]					= false;
						$this->_log(sprintf('>>> Data URI for icon "%s" exceeds %s byte limit (%s), switching to PNG sprite only mode', basename($png), $this->_thresholds[self::PNG], $dataUriLength));
					}
				}
			}
			
			// If data URIs can be used
			if ($dataUris = is_array($this->_dataUris[$directory][self::PNG])) {
				
				// If CSS rules shall be generated
				if ($this->_flags['css'] !== false) {
					$this->_css[self::PNG][self::DATA]							= array_merge($this->_css[self::PNG][self::DATA], $this->_createDataURICssRules($directory, $this->_dataUris[$directory][self::PNG]));
				}
				
				// If Sass rules shall be generated
				if ($this->_flags['sass'] !== false) {
					$this->_sass[self::PNG][self::DATA]							= array_merge($this->_sass[self::PNG][self::DATA], $this->_createDataURISassRules($directory, $this->_dataUris[$directory][self::PNG]));
				}
			}
			
			// Create PNG sprite and appropriate rules
			$this->_createPNGIconSpriteAndRules($directory, $this->_useSprite[$directory][self::PNG], !$dataUris);
		}
	}
	
	/**
	 * Determine the dimensions of an icon
	 * 
	 * @param string $directory					Directory
	 * @param string $icon						Icon
	 * @param string $name						Icon name
	 * @return array							Dimensions (width & height)
	 */
	protected function _getIconDimensions($directory, $icon, $name) {
		if (!array_key_exists($directory, $this->_dimensions)) {
			$this->_dimensions[$directory]				= array();
		}
		if (!array_key_exists($name, $this->_dimensions[$directory])) {
			$iconSVG									= $this->_loadSVG($icon);
			$iconWidth									= intval($iconSVG->documentElement->getAttribute('width'));
			$iconHeight									= intval($iconSVG->documentElement->getAttribute('height'));
			$this->_dimensions[$directory][$name]		= array($iconWidth, $iconHeight);
		}
		return $this->_dimensions[$directory][$name];
	}
	
	/**
	 * Create an SVG sprite and the corresponding CSS and Sass rules
	 * 
	 * @param string $directory					Directory
	 * @param array $icons						SVG icon files
	 * @param boolean $spriteOnly				Sprite-only mode (apply sprite rules to data URI styles as well)
	 * @return void
	 */
	protected function _createSVGIconSpriteAndRules($directory, array $icons, $spriteOnly = false) {
		$this->_log('| + Creating SVG sprite');
		$spriteName								= $this->_tmpName.'.svg';
		$spritePath								= $this->_tmpName.DIRECTORY_SEPARATOR.$spriteName;
		$prefix									= strlen($this->_prefix) ? $this->_prefix : $this->_tmpName;
		$css									= array('/* Icons from directory "'.$directory.'" */');
		$sass									= array(
			'// Icons from directory "'.$directory.'"',
			"%$prefix {
	background-repeat: no-repeat
}",
		);
		
		// Create a sprite document
		$spriteSVG								= new \DOMDocument();
		$spriteSVG->loadXML('<svg xmlns="http://www.w3.org/2000/svg"/>');
		$viewboxWidth							=
		$viewboxHeight							= 0;
		
		// Run through all icon files
		foreach ($icons as $name => $icon) {
			try {
				
				// Load the SVG icon
				/* @var $iconSVG \DOMDocument */
				$iconSVG						= $this->_loadSVG($icon);
				$iconWidth						= intval($iconSVG->documentElement->getAttribute('width'));
				$iconHeight						= intval($iconSVG->documentElement->getAttribute('height'));
				$this->_dimensions[$directory][$name]					= array($iconWidth, $iconHeight);
				
				// Clone the icon node and set it's offset in the sprite
				$iconNode						= $spriteSVG->importNode($iconSVG->documentElement, true);
				$iconNode->setAttribute('id', $name);
				$iconNode->setAttribute('y', $viewboxHeight);
				
				// Append the icon to the sprite
				$spriteSVG->documentElement->appendChild($iconNode);
				
				// Write out the appropriate CSS rules
				$css[$directory.DIRECTORY_SEPARATOR.$name]				= ".$prefix-$name{background-image:url('$spritePath');background-position:0 ".(-$viewboxHeight).($viewboxHeight ? 'px' : '').";background-repeat:no-repeat}";
				if ($this->_flags['dims']) {
					$css[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$prefix-$name-dims{width:".$iconWidth.'px;height:'.$iconHeight.'px}';
				}

				// Write out the appropriate Sass rules
				$sass[$directory.DIRECTORY_SEPARATOR.$name]				= ".$prefix-$name {
	@extend %$prefix;
	background-image: url('$spritePath');
	background-position: 0 ".(-$viewboxHeight).($viewboxHeight ? 'px' : '').';
}';
				if ($this->_flags['dims']) {
					$sass[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$prefix-$name-dims {
	width: ".$iconWidth.'px;
	height: '.$iconHeight.'px;
}';
				}
				
				// Increment the offsets
				$viewboxWidth											= max($viewboxWidth, $iconWidth);
				$viewboxHeight											+= $iconHeight;
				
			} catch (\Exception $e) {
				$this->_error(sprintf('Problem processing SVG icon "%s": '.$e->getMessage(), $name));
			}
		}
		
		// Finalize the SVG sprite
		$spriteSVG->insertBefore(new \DOMComment('Icons from directory "'.$directory.'"'), $spriteSVG->documentElement);
		$spriteSVG->documentElement->setAttribute('width', $viewboxWidth);
		$spriteSVG->documentElement->setAttribute('height', $viewboxHeight);
		$spriteSVG->documentElement->setAttribute('viewBox', '0 0 '.$viewboxWidth.' '.$viewboxHeight);
		
		// Save the SVG sprite
		$spriteSVG->save($this->_target.$spritePath);
		
		// If CSS rules shall be generated
		if ($this->_flags['css'] !== false) {
			$this->_log('| + Creating SVG sprite CSS rules');
			$this->_css[self::SVG][self::SPRITE]						= array_merge($this->_css[self::SVG][self::SPRITE], $css);
			if ($spriteOnly) {
				$this->_css[self::SVG][self::DATA]						= array_merge($this->_css[self::SVG][self::DATA], $css);
			}
		}
		
		// If Sass rules shall be generated
		if ($this->_flags['sass'] !== false) {
			$this->_log('| + Creating SVG sprite Sass rules');
			$this->_sass[self::SVG][self::SPRITE]						= array_merge($this->_sass[self::SVG][self::SPRITE], $sass);
			if ($spriteOnly) {
				$this->_sass[self::SVG][self::DATA]						= array_merge($this->_sass[self::SVG][self::DATA], $sass);
			}
		}
	}	
	
	/**
	 * Create an PNG sprite and the corresponding CSS and Sass rules
	 *
	 * @param string $directory					Directory
	 * @param array $icons						PNG icon files
	 * @param boolean $spriteOnly				Sprite-only mode (apply sprite rules to data URI styles as well)
	 * @return void
	 */
	protected function _createPNGIconSpriteAndRules($directory, array $icons, $spriteOnly = false) {
		$this->_log('| + Creating PNG sprite');
		$spriteName								= $this->_tmpName.'.png';
		$spritePath								= $this->_tmpName.DIRECTORY_SEPARATOR.$spriteName;
		$prefix									= strlen($this->_prefix) ? $this->_prefix : $this->_tmpName;
		$css									= array('/* Icons from directory "'.$directory.'" */');
		$sass									= array(
			'// Icons from directory "'.$directory.'"',
			"%$prefix {
	background-repeat: no-repeat
}",
		);
		$imageWidth								=
		$imageHeight							= 0;
		$iconParameters							= array();
		
		// Determine image dimensions and parameters
		foreach ($icons as $name => $icon) {
			list($iconWidth, $iconHeight)		= getimagesize($icon);
			$iconParameters[$name]				= array($name, $icon, $imageHeight, $iconWidth, $iconHeight);
			$imageWidth							= max($imageWidth, $iconWidth);
			$imageHeight						+= $iconHeight;
		}
		
		// Create the sprite image
		$image	        						= imagecreatetruecolor($imageWidth, $imageHeight);
		$transparent							= imagecolorallocatealpha($image, 0, 0, 0, 127);
		$transparentIndex						= imagecolortransparent($image, $transparent);
		imageFill($image, 0, 0, $transparent);
		imagealphablending($image, false);
		imagesavealpha($image, true);
		
		// Run through the single icons
		foreach ($iconParameters as $name => $icon) {
			
			// Merge the icon with the sprite
			$iconImage							= @imagecreatefrompng($icon[1]);
			imagecopy($image, $iconImage, 0, $icon[2], 0, 0, $icon[3], $icon[4]);
			imagedestroy($iconImage);
			
			// Write out the appropriate CSS rules
			$css[$directory.DIRECTORY_SEPARATOR.$name]				= ".$prefix-$name{background-image:url('$spritePath');background-position:0 ".(-$icon[2]).($icon[2] ? 'px' : '').';background-repeat:no-repeat}';
			if ($this->_flags['dims']) {
				$css[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$prefix-$name-dims{width:".$icon[3].'px;height:'.$icon[4].'px}';
			}

			// Write out the appropriate Sass rules
			$sass[$directory.DIRECTORY_SEPARATOR.$name]				= ".$prefix-$name {
	@extend %$prefix;
	background-image: url('$spritePath');
	background-position: 0 ".(-$icon[2]).($icon[2] ? 'px' : '').';
}';
			if ($this->_flags['dims']) {
				$sass[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$prefix-$name-dims {
	width: ".$icon[3].'px;
	height: '.$icon[4].'px;
}';
			}
		}
		
		// Save the PNG sprite
		imagepng($image, $this->_target.$spritePath);
		imagedestroy($image);
		
		// Finally optimize the sprite itself
		$this->_log('|-- Optimizing the PNG sprite ...');
		chdir($this->_target);
		$optimizedSprite											= $this->_optimizePNGImages(array('sprite' => $this->_target.$spritePath));
		$optimizedSprite											= $this->_target.$this->_tmpName.DIRECTORY_SEPARATOR.$optimizedSprite[$this->_target.$spritePath];
		if (($optimizedSprite != $this->_target.$spritePath) && (filesize($optimizedSprite) < filesize($this->_target.$spritePath))) {
			unlink($this->_target.$spritePath);
			rename($optimizedSprite, $this->_target.$spritePath);
		}
		
		// If CSS rules shall be generated
		if ($this->_flags['css'] !== false) {
			$this->_log('| + Creating PNG sprite CSS rules');
			$this->_css[self::PNG][self::SPRITE]					= array_merge($this->_css[self::PNG][self::SPRITE], $css);
			if ($spriteOnly) {
				$this->_css[self::PNG][self::DATA]					= array_merge($this->_css[self::PNG][self::DATA], $css);
			}
		}
		
		// If Sass rules shall be generated
		if ($this->_flags['sass'] !== false) {
			$this->_log('| + Creating PNG sprite Sass rules');
			$this->_sass[self::PNG][self::SPRITE]					= array_merge($this->_sass[self::PNG][self::SPRITE], $sass);
			if ($spriteOnly) {
				$this->_sass[self::PNG][self::DATA]					= array_merge($this->_sass[self::PNG][self::DATA], $sass);
			}
		}
	}
	
	/**
	 * Create CSS rules using data URIs
	 * 
	 * @param string $directory					Directory
	 * @param array $dataURIs					Data URIs
	 * @return array							CSS rules
	 */
	protected function _createDataURICssRules($directory, array $dataURIs) {
		$this->_log('| + Creating data URI CSS rules');
		$prefix														= strlen($this->_prefix) ? $this->_prefix : $this->_tmpName;
		$css														= array('/* Icons from directory "'.$directory.'" */');
		foreach ($dataURIs as $name => $icon) {
			$css[$directory.DIRECTORY_SEPARATOR.$name]				= ".$prefix-$name{background-image:url('$icon');background-repeat:no-repeat}";
			if ($this->_flags['dims']) {
				list($iconWidth, $iconHeight)						= $this->_getIconDimensions($directory, $icon, $name);
				$css[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$prefix-$name-dims{width:".$iconWidth.'px;height:'.$iconHeight.'px}';
			}
		}
		return $css;
	}
	

	/**
	 * Create Sass rules using data URIs
	 *
	 * @param string $directory					Directory
	 * @param array $dataURIs					Data URIs
	 * @return array							CSS rules
	 */
	protected function _createDataURISassRules($directory, array $dataURIs) {
		$this->_log('| + Creating data URI Sass rules');
		$prefix														= strlen($this->_prefix) ? $this->_prefix : $this->_tmpName;
		$sass														= array(
			'// Icons from directory "'.$directory.'"',
			"%$prefix {
	background-repeat: no-repeat
}",
		);
		foreach ($dataURIs as $name => $icon) {
			$sass[$directory.DIRECTORY_SEPARATOR.$name]				= ".$prefix-$name {
	@extend %$prefix;
	background-image: url('$icon');
}";
			if ($this->_flags['dims']) {
				list($iconWidth, $iconHeight)						= $this->_getIconDimensions($directory, $icon, $name);
				$sass[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$prefix-$name-dims {
	width: ".$icon[3].'px;
	height: '.$icon[4].'px;
}';
			}
		}
		return $sass;
	}
	
	/**
	 * Run a binary command
	 * 
	 * @param string $binary					Binary
	 * @param array $arguments					Arguments
	 * @param array $output						Output
	 * @return boolean							Success
	 */
	protected function _do($binary, array $arguments = array(), array $output = null) {
		$return					= 0;
		$output					= array();
		$cmd					= $binary;
		foreach ($arguments as $argument => $value) {
			if (!is_numeric($argument) && strlen($argument)) {
				$cmd			.= ' '.$argument;
				if ($value !== null) {
					if (strncmp('=', substr($cmd, -1), 1)) {
						$cmd	.= ' ';
					}
					$cmd		.= escapeshellarg($value);
				}
			} elseif (is_array($value)) {
				foreach ($value as $arg) {
					$arg		= trim($arg);
					if (strlen($arg)) {
						$cmd	.= ' '.escapeshellarg($arg);
					}
				}
			} elseif (strlen($value)) {
				$cmd			.= ' '.$value;
			}
		}
// 		echo $cmd."\n";
		@exec($cmd, $output, $return);
		return !$return;
	}
	
	/**
	 * Load an SVG icon
	 * 
	 * @param string $file						SVG icon file path
	 * @return \DOMDocument						SVG icon instance
	 * @throws \Exception						If an error occurs during loading the SVG file
	 */
	protected function _loadSVG($file) {
		libxml_use_internal_errors(true);
		$svg					= new \DOMDocument();
		
		if (!$svg->load($file)) {
			$message			= array();
			
			/* @var $error \libXMLError */
			foreach (libxml_get_errors() as $error) {
				$message[]		= $error->message;
			}
		
			libxml_clear_errors();
			libxml_use_internal_errors(false);
			
			throw new \Exception(implode('; ', $message));
		}
		
		libxml_use_internal_errors(false);
		return $svg;
	}
	
	/**
	 * Optimize a list of PNG images in several steps / with several tools
	 * 
	 * @param array $pngs					PNG images
	 * @return array						Resulting PNG image file names (locally in temporary directory)
	 */
	protected function _optimizePNGImages(array $pngs) {
		$suffix								= '';
		$suffices							= array();
		$pngFilenames						= array();
		foreach ($pngs as $png) {
			$pngFilenames[$png]				= pathinfo($png, PATHINFO_FILENAME);
		}
			
		// If pngcrush is available
		if ($this->_binaries['pngcrush']) {
			$this->_log('|   Optimizing using "pngcrush" ...');
			$suffix							= '-pc';
			$suffices[]						= 'pc';
			$params							= array(
				'-brute',
				'-reduce',
				'-e'						=> '-pc.png',
			);
			if ($this->_flags['verbose'] < 2) {
				$params[]					= '-q';
			}
			$params[]						= $pngs;
			$this->_do($this->_binaries['pngcrush'], $params);
			
			// pngcrush will never result in bigger files, so it seems safe to always take the results
			$pngs							= $this->_mapFileExtension($pngFilenames, $suffix);
		}
			
		// If pngquant is available
		if ($this->_flags['quantize'] && $this->_binaries['pngquant']) {
			$this->_log('|   Optimizing using "pngquant" ...');
			$suffix							.= '-pq';
			$suffices[]						= 'pq';
			$params							= array(
				'--speed'					=> $this->_speed,
				'--force',
				'--transbug',
				'--ext'						=> '-pq.png',
			);
			if ($this->_flags['verbose'] >= 2) {
				$params[]					= '--verbose';
			}
			$params[]						= $pngs;
			$this->_do($this->_binaries['pngquant'], $params);
			
			// pngquant *can* produce bigger files, depending on the image contents, so the effects have to be checked for every file
			$quantpngs						= $this->_mapFileExtension($pngFilenames, $suffix);
			foreach ($quantpngs as $png => $quantpng) {
				$directory					= dirname($png).DIRECTORY_SEPARATOR;
				if (@is_file($directory.$quantpng) && (@filesize($directory.$quantpng) < filesize($directory.$pngs[$png]))) {
					@unlink($directory.$pngs[$png]);
					$pngs[$png]				= $quantpng;
				} else {
					@unlink($directory.$quantpng);
				}
			}
		}
		
		// If optipng is available
		if ($this->_binaries['optipng']) {
			$this->_log('|   Optimizing using "optipng" ...');
			$suffices[]						= 'op';
			$optipngs						= array();
			
			// Copy all files with new filename (works on the current file only)
			foreach ($pngs as $png => $currentpng) {
				$optipng					= substr($currentpng, 0, -4).'-op.png';
				$optipngs[$png]				= $optipng;
				copy($currentpng, $optipng);
			}
			
			$params							= array(
				'-o'.$this->_optimization,
				'-zm1-9',
				'-force',
				'-strip'					=> 'all',
			);
			if ($this->_flags['verbose'] < 2) {
				$params[]					= '-quiet';
			}
			$params[]						= $optipngs;
			$this->_do($this->_binaries['optipng'], $params);
			
			foreach ($optipngs as $png => $optipng) {
				$directory					= dirname($png).DIRECTORY_SEPARATOR;
				if (@is_file($directory.$optipng) && (@filesize($directory.$optipng) < filesize($directory.$pngs[$png]))) {
					@unlink($directory.$pngs[$png]);
					$pngs[$png]				= $optipng;
				} else {
					@unlink($directory.$optipng);
				}
			}
		}
		
		return $pngs;
	}
	
	/**
	 * Map file extensions to a list of file names
	 * 
	 * @param array $filenames				File names
	 * @param string $extension				Extension
	 * @return array						Mapped file names
	 * @return void
	 */
	protected function _mapFileExtension(array $filenames, $extension) {
		foreach ($filenames as $png => $filename) {
			$filenames[$png]				.= $extension.'.png';
		}
		return $filenames;
	}
	
	/**
	 * Die with a usage message
	 * 
	 * @param string $message				Message
	 * @return void
	 */
	protected function _usage($message = '') {
		die("\n".trim($message."\n\n".$this->_options->getUsageMessage())."\n\n");
	}
	
	/**
	 * Delete a list of files and folders
	 * 
	 * For security measures, each element has to be within the current output directory 
	 * 
	 * @param array $elements				Elements
	 * @return void
	 */
	protected function _delete(array $elements) {
		foreach ($elements as $element) {
			if (!strncmp($element, $this->_target, strlen($this->_target))) {
				if (@is_dir($element)) {
					$subelements			= array();
					foreach (scandir($element) as $subelement) {
						if (($subelement != '.') && ($subelement != '..')) {
							$subelements[]	= rtrim($element, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$subelement;
						}
					}
					$this->_delete($subelements);
					@rmdir($element);
				} elseif (@file_exists($element)) {
					@unlink($element);
				}
			}
		}		
	}
	
	/**
	 * Create a unique file / directory name of a file / directory path
	 * 
	 * @param string $path					File / directory path
	 * @return string						Unique file / directory name
	 */
	protected function _uniqueName($path) {
		$unique								= pathinfo($path, PATHINFO_FILENAME).'-';
		if (function_exists('fileinode')) {
			$unique							.= fileinode($path);
		} else {
			$unique							.= sha1($path);
		}
		return $unique;
	}
	
	/**
	 * Output a progress message
	 * 
	 * @param string $message				Message
	 * @param boolean $verbose				Output only if verbose mode is active
	 * @return void
	 */
	protected function _log($message, $verbose = false) {
		if (strlen(trim($message)) && ($this->_flags['verbose'] > ($verbose ? 1 : 0))) {
			echo trim($message)."\n";
		}
	}
	
	/**
	 * Output an error message and exit
	 * 
	 * @param string $message				Message
	 * @return void
	 */
	protected function _error($message) {
		$this->_log('!!! ERROR: '.$message);
		exit;
	}
}

new Iconizr();
