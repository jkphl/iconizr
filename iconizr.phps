#!/usr/bin/env php
<?php

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
		'width'		=> self::DEFAULT_LENGTH,
		'height'	=> self::DEFAULT_LENGTH,
		'maxwidth'	=> self::DEFAULT_MAXLENGTH,
		'maxheight'	=> self::DEFAULT_MAXLENGTH,
		'svg'		=> self::DEFAULT_THRESHOLD_SVG,
		'png'		=> self::DEFAULT_THRESHOLD_PNG,
		'scour'		=> null,
		'sassout'	=> null,
		'python'	=> null,
		'pseudo'	=> self::PSEUDO_SPLIT,
		'padding'	=> 0,
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
		'python'	=> false,
	);
	/**
	 * Current working directory
	 * 
	 * @var string
	 */
	protected $_cwd = null;
	/**
	 * SVG directories
	 * 
	 * @var array
	 */
	protected $_dirs = array();
	/**
	 * List of unique output directory names
	 * 
	 * @var array
	 */
	protected $_uniqueDirs = array();
	/**
	 * Target directory for CSS files
	 * 
	 * @var string
	 */
	protected $_target = null;
	/**
	 * Target directory for Sass files
	 *
	 * @var string
	 */
	protected $_sassTarget = null;
	/**
	 * Prefix to the CSS directory path for embedding in HTML documents
	 * 
	 * @var string
	 */
	protected $_embed = null;
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
			self::SINGLE	=> array(),
			self::DATA		=> array(),
			self::SPRITE	=> array(),
		),
		self::PNG			=> array(
			self::SINGLE	=> array(),
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
			self::SINGLE	=> array(),
			self::DATA		=> array(),
			self::SPRITE	=> array(),
		),
		self::PNG			=> array(
			self::SINGLE	=> array(),
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
	 * Whether to optimize PNG images
	 * 
	 * @var boolean
	 */
	protected $_optimize = true;
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
	 * Absolute path to scour script for cleaning the SVG files
	 * 
	 * @var string
	 */
	protected $_scour = null;
	/**
	 * Default icon width
	 * 
	 * @var int
	 */
	protected $_width = self::DEFAULT_LENGTH;
	/**
	 * Default icon height
	 * 
	 * @var int
	 */
	protected $_height = self::DEFAULT_LENGTH;
	/**
	 * Maximum icon width
	 * 
	 * @var int
	 */
	protected $_maxwidth = self::DEFAULT_MAXLENGTH;
	/**
	 * Maximum icon height
	 * 
	 * @var int
	 */
	protected $_maxheight = self::DEFAULT_MAXLENGTH;
	/**
	 * Padding around the icons (pixel)
	 * 
	 * @var int
	 */
	protected $_padding = 0;
	/**
	 * Logging group (indentation level)
	 * 
	 * @var int
	 */
	protected $_logGroup = 0;
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
	 * Single image mode
	 * 
	 * @var string
	 */
	const SINGLE = 'single';
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
	/**
	 * Default icon width / height
	 * 
	 * @var int
	 */
	const DEFAULT_LENGTH = 32;
	/**
	 * Default maximum icon width / height
	 * 
	 * @var int
	 */
	const DEFAULT_MAXLENGTH = 1000;
	/**
	 * Default padding around the icons (pixel)
	 * 
	 * @var int
	 */
	const DEFAULT_PADDING = 0;
	/**
	 * Character sequence for denoting pseudo-selectors
	 * 
	 * @var string
	 */
	const PSEUDO_SPLIT = '~';
	/**
	 * Info message
	 * 
	 * @var int
	 */
	const LOG_INFO = 0;
	/**
	 * Creation message
	 * 
	 * @var int
	 */
	const LOG_CREATE = 1;
	/**
	 * Alert message
	 * 
	 * @var int
	 */
	const LOG_ALERT = 2;
	/**
	 * Group message
	 * 
	 * @var int
	 */
	const LOG_GROUP = 3;
	/**
	 * Error message
	 * 
	 * @var int
	 */
	const LOG_ERROR = 4;
	/**
	 * PCRE character class for the first char of XML IDs
	 * 
	 * @var string
	 */
	const PCRE_ID_START_CHARS = 'A-Za-z\:\_\xC0-\xD6\xD8-\xF6\xF8-\x{2FF}\x{370}-\x{37D}\x{37F}-\x{1FFF}\x{200C}-\x{200D}\x{2070}-\x{218F}\x{2C00}-\x{2FEF}\x{3001}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFFD}\x{10000}-\x{EFFFF}';
	/**
	 * PCRE character class for the first char of XML IDs
	 * 
	 * @var string
	 */
	const PCRE_ID_FOLLOWER_CHARS = '\-\.\d\xB7\x{0300}-\x{036F}\x{203F}-\x{2040}';
	
	/************************************************************************************************
	 * PUBLIC METHODS
	 ***********************************************************************************************/
	
	/**
	 * Constructor
	 * 
	 * @return void
	 */
	public function __construct() {
		$this->_cwd								= getcwd();
		
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
				'out|o=s'						=> 'Output directory for the CSS files and the icons subdirectory',
				'sassout=s'						=> 'Optional separate output directory for Sass files',
				'css|c-s'						=> 'Render CSS files (optionally provide a CSS file prefix, default: iconizr)',
				'sass|s-s'						=> 'Render Sass files (optionally provide a Sass file prefix, default: iconizr)',
				'prefix|p=s'					=> 'CSS selector prefix (default: '.self::DEFAULT_PREFIX.')',
				'level|l=i'						=> 'PNG image optimization level: 0 (no optimization), 1 (fast & rough) - 11 (slow & high quality), default: '.self::DEFAULT_LEVEL,
				'quantize|q'					=> 'Quantize PNG images (reduce to 8-bit color depth)',
				'embed|e=s'						=> 'Prefix to the CSS directory path for embedding the stylesheets into your HTML documents (default: no prefix, use output directory as root-relative path)',
				'width=i'						=> 'Default icon width (if SVG is missing a width value; defaults to '.self::DEFAULT_LENGTH.' pixels)',
				'height=i'						=> 'Default icon height (if SVG is missing a height value; defaults to '.self::DEFAULT_LENGTH.' pixels)',
				'maxwidth=i'					=> 'Maximum icon width, default: '.self::DEFAULT_MAXLENGTH.' pixels',
				'maxheight=i'					=> 'Maximum icon height, default: '.self::DEFAULT_MAXLENGTH.' pixels',
				'padding=i'						=> 'Transparent padding around the icons (in pixel), default: '.self::DEFAULT_PADDING.' pixels',
				'svg=i'							=> 'Data URI byte threshold for SVG files, default: '.self::DEFAULT_THRESHOLD_SVG,
				'png=i'							=> 'Data URI byte threshold for PNG files, default: '.self::DEFAULT_THRESHOLD_PNG,
				'pseudo=s'						=> 'Character sequence for denoting CSS pseudo classes, default: '.self::PSEUDO_SPLIT,
				'dims|d'						=> 'Render icon dimensions as separate CSS and / or Sass rules',
				'keep|k'						=> 'Keep intermediate SVG and PNG files (inside the sprite subdirectory)',
				'verbose|v-i'					=> 'Output verbose progress information',
				'scour=s'						=> 'Absolute path to scour script for cleaning SVG files (see http://www.codedread.com/scour)',
				'python=s'						=> 'Absolute path to Python 2 binary (only necessary if another Python version is the machine default)',
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
			$workingDirectory					= rtrim($this->_cwd, DIRECTORY_SEPARATOR);
			$target								= rtrim(strncmp($target, DIRECTORY_SEPARATOR, 1) ? $workingDirectory.DIRECTORY_SEPARATOR.$target : $target, DIRECTORY_SEPARATOR);
			if (strncmp($workingDirectory, $target, strlen($workingDirectory))) {
				$this->_usage('The output directory must be a subdirectory of the current working directory');
			}
			if (@is_dir($target) || @mkdir($target, 0777, true)) {
				$this->_target					= $target.DIRECTORY_SEPARATOR;
			}
		}
		if ($this->_target === null) {
			$this->_usage('Please provide a valid output directory');
		}
		
		// Determine the HTML embed CSS directory path
		$this->_embed							= rtrim((array_key_exists('embed', $options) && strlen($options['embed'])) ? $options['embed'] : '', DIRECTORY_SEPARATOR).'/';
		
		// Verify the Python binary
		if (strlen($options['python']) && @is_file($options['python']) && ($this->_pythonMajorVersion($options['python']) == 2)) {
			$this->_binaries['python']			= $options['python'];
		} elseif ($this->_binaries['python'] && ($this->_pythonMajorVersion($this->_binaries['python']) != 2)) {
			$this->_binaries['python']			= null;
		}
		
		// Select Scour als SVG cleaner if possible
		$this->_scour							= (strlen(trim($options['scour'])) && @is_readable($options['scour'])) ? trim($options['scour']) : null;
		
		// Set the CSS class prefix
		$this->_prefix							= strlen(trim($options['prefix'])) ? trim($options['prefix']) : self::DEFAULT_PREFIX;
		
		// Determine default and maximum icon width & height
		$this->_width							= max(1, min(1000, intval($options['width'])));
		$this->_height							= max(1, min(1000, intval($options['height'])));
		$this->_maxwidth						= max($this->_width, intval($options['maxwidth']));
		$this->_maxheight						= max($this->_width, intval($options['maxheight']));
		$this->_padding							= max($this->_padding, intval($options['padding']));
		
		// Determine quantize speed, optimization level and other flags
		$level									= max(0, min(11, intval($options['level']))) - 1;
		$this->_optimize						= $level >= 0;
		$this->_speed							= $this->_optimize ? round(10 - (9 * $level / 10)) : 0;
		$this->_optimization					= $this->_optimize ? round($level * 7 / 10) : 0;
		$this->_thresholds[self::SVG]			= max(1024, intval($options['svg']));
		$this->_thresholds[self::PNG]			= max(1024, intval($options['png']));
		$this->_flags['css']					= (is_string($options['css']) && strlen(trim($options['css']))) ? trim($options['css']) : (intval($options['css']) ? self::DEFAULT_FILE : false);
		$this->_flags['sass']					= (is_string($options['sass']) && strlen(trim($options['sass']))) ? trim($options['sass']) : (intval($options['sass']) ? self::DEFAULT_FILE : false);
		$this->_flags['verbose']				= intval($options['verbose']);
		$this->_flags['quantize']				= !!$options['quantize'];
		$this->_flags['dims']					= !!$options['dims'];
		$this->_flags['keep']					= !!$options['keep'];
		$this->_flags['pseudo']					= strlen(trim($options['pseudo'])) ? trim($options['pseudo']) : self::PSEUDO_SPLIT;
		
		// Determine Sass output directory (if any)
		if ($this->_flags['sass']) {
			$this->_sassTarget					= $this->_target;
			if (array_key_exists('sassout', $options) && strlen($sassTarget = trim($options['sassout']))) {
				$sassTarget						= rtrim(strncmp($sassTarget, DIRECTORY_SEPARATOR, 1) ? $workingDirectory.DIRECTORY_SEPARATOR.$sassTarget : $sassTarget, DIRECTORY_SEPARATOR);
				if (strncmp($workingDirectory, $sassTarget, strlen($workingDirectory))) {
					$this->_usage('The sass output directory must be a subdirectory of the current working directory');
				}
				if (@is_dir($sassTarget) || @mkdir($sassTarget, 0777, true)) {
					$this->_sassTarget			= $sassTarget.DIRECTORY_SEPARATOR;
				}
			}
		}
		
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
					$this->_target.$this->_uniqueName($dir);
					
				// Else: Drop the input directory again
				} else {
					unset($this->_dirs[$dir]);
				}
			}
		}
		
		// Register the unique output directory names
		$outputElements							= array_merge($outputElements, array_values($this->_uniqueDirs));
		
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
							file_put_contents($this->_sassTarget.$this->_flags['sass'].'-'.$type.'-'.$mode.'.scss', implode("\n", $content));
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
		
		// Prepare the loader script fragment 
		$this->_log('Creating the stylesheet loader fragment', self::LOG_CREATE);
		$loader														= '<script type="text/javascript" title="https://iconizr.com | © '.date('Y').' Joschi Kuphal | MIT">';
		$loader														.= file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'iconizr.min.js');
		$loader														.= '</script>';
		$loader														.= '<noscript><link href="'.$this->_embed.htmlspecialchars($css[self::PNG][self::SPRITE]).'" rel="stylesheet" type="text/css" media="all"></noscript>';
		file_put_contents($this->_target.$this->_flags['css'].'-loader-fragment.html', sprintf($loader, $this->_embed.htmlspecialchars($css[self::PNG][self::SPRITE]), $this->_embed.htmlspecialchars($css[self::PNG][self::DATA]), $this->_embed.htmlspecialchars($css[self::SVG][self::SPRITE]), $this->_embed.htmlspecialchars($css[self::SVG][self::DATA])));

		// Create the preview documents
		$this->_log('Creating the icon kit preview documents', self::LOG_CREATE);
		$stylesheets												= array('' => 'Automatic detection');
		if ($this->_flags['keep']) {
			$stylesheets[basename($css[self::PNG][self::SINGLE])]	= 'PNG single images'; 
		}
		$stylesheets[basename($css[self::PNG][self::SPRITE])]		= 'PNG sprite';
		$stylesheets[basename($css[self::PNG][self::DATA])]			= 'PNG data URIs';
		if ($this->_flags['keep']) {
			$stylesheets[basename($css[self::SVG][self::SINGLE])]	= 'SVG single images';
		}
		$stylesheets[basename($css[self::SVG][self::SPRITE])]		= 'SVG sprite';
		$stylesheets[basename($css[self::SVG][self::DATA])]			= 'SVG data URIs';

		// Prepare the preview document
		$preview													= array(
			'primer'												=> '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/><meta http-equiv="cache-control" content="max-age=0"/><meta http-equiv="cache-control" content="no-cache"/><meta http-equiv="expires" content="0"/><meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT"/><meta http-equiv="pragma" content="no-cache"/><title>Icon kit preview | iconizr.com</title>',
			'css'													=> '',
			'styles'												=> '<style type="text/css">@CHARSET "UTF-8";body{padding:0;margin:0;color:#666;background:#fafafa;font-family:Arial, Helvetica, sans-serif;font-size:1em;line-height:1.4}header{display:block;padding:3em 3em 2em 3em;background-color:#fff}header p{margin:2em 0 0 0}#logo{font-size:xx-large;font-size:3rem;padding:0;margin:0;height:53px;background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASwAAAA1CAMAAADMDSagAAAABGdBTUEAALGPC/xhBQAAADNQTFRFD3WVD3WVD3WVD3WVD3WVD3WVD3WVD3WVD3WVD3WVD3WVD3WVD3WVD3WVD3WVD3WVAAAA8Cs4JwAAABF0Uk5Tj2BQr89w3yAwn+8Qv4BA/wBvsGvcAAAFV0lEQVRo3tWb2aKrKgyGEZV5ev+nPRerqzIkAYW9TstlyxA+yU/EwEJdbMKK3LSLryIc22TqFHXux6u+cZ4DNXxdVDmiR8r2O0Jtfd7ehrHyql7//Ds9ezoTY4zRsViXAE/cbkdT9ThxssmeoqpudNN306Uo/g4RKe5VwWF/kK2rgtjif6bhzfuXUVhZk6JoZHlZuIELHVgvEz8B1p5SSip74mIMlhL4UAxaXUGMVQcqyE+B5VJKPH/kY264kWOZVoxOovohaVjuU2CJugc2Aov1RvNVA03DVSSsuH0IrJhUKSV+ANbWH07fYFXQAv+2HwJLVltaYIr7/aBg8XJt7t57z1wl3yfqg4f23vuy/nHhkMFrR6BvzDOOnVd4Y8PJ3CQscZlSDlXbpdirX43Bktk0jb/0hmtkfrmJWQPu6o0GfSDt4r7WPxiryEDDEk2cljuUqmJDfAWy39AAszSb415aKnfogVqBNch3ljNR8ZRoApfaZdB4DYTlmvoaNaVZj5qrV4DbhcUxYSrVzAHziqwOQAykS5CBfi0sT8jwnkhYOje0B0sQrLIH9LZRUnAzWpqO1OU/hZXZISwJayvVgIa1Eb3mf7t2fR9A/RPBEahNbR5W5Wn2wASrtmVLd2AdWLtqHf3aaDuvTQ720UAFW/OwwrBglbbUOkLDUrjmll7qmqWjwfqXJYaGVYraUliUYJUm2luwGL2wsofUrEQFN7iWFqcjIr0QlhwWrMIWlm7BOuj4JntKtbwfyKw4SOMyUIB2PINlqwOrAcEqbPG3YFlyvRZ0ai88sWMuAzn2ZeAG/v8MFu0LmI1PYfH+3I9iEhrb+6+yQ1UuA8MJ2boOVkewJmD53vEphq6UbyR64CCszPXlelg9wZqAtfdsfGAzaEsOS5GvBXOwuoI1Act1AofmueHjAPPSMCxoC14FqytYE7DEsGx2x6HnVcC6XsXfgc4iWH3BmoAVH8MiNM71YAHhxRpYA4L1h7D4GliZVoaFsEYE6w9h+VuwDApL1sHWElgjgvWxsCLuv3WwtQLWkGB94cpKdbC1ANaYYH2hZqU62JqHNShY37cbtucd87AGBet/ibPOiTir+u5h7AJYo4L1dRE8cJYzC2tYsL7t3bAdPMzCGhesvzx1EP2l2D11aL/uillY44L1b8+zBg6rMMNVZ2fIgq05WDcEawKW7A5iy5zCOyelpruNXs6jZ2DdEawJWJdbmV7PftEZPPxtKc7AwgVLVtmjc7C6X3d41bHo+eEO9ogEaGwFLIYL1k6nZt6EpXrbm6469p3vhhL+KIfAsmIeFicEyy2FlS1hcD+0ptraZGePdjDM0E9LeQhLUoJ1rIW10SLU5k3RuQ68m+sQMK99CIuMsOJaWJkIMWpP3ls/AxoMZNEQqXRPYDEywloNa4u4xm9QFghhXpYgbuTgG/g5BYuTEZZcDSvP/CsSu5LdQUuswRrkmX9++LjimIAl6QgrPISV5ZSeCnUE499DyvwGBbKzFQ2CQ9OvZDa6DhJ7OcdhZTmlR373KAO9AxeANEQlyyltbs+02coxxmP3XAHO9k4+ZmULhZyX/lxL8N77XcD5RECucoxO+4AFW80bxNl0YBw7A+TCnTz+Jls5xihcRoK4EIBMns7yr99bn+TBV1DKYAsNRYD5+3uw0L/DDVi9CxOcest/cMOihsK/Cha5noUiv1y05VDpHqwi2Pp8WMOXvG43GBo932O+ANbo9cHbDcZGP78LVkr2bG6yavL8tL3JGvf+TVZ49ONDYPVuDufusOnfC0vGMd4/SFPXvj7W4HZRmPUyO67qlp/QDv37bfd/o0qpoArCXu0AAAAASUVORK5CYII=) center left no-repeat;white-space:nowrap;overflow:hidden;text-indent:100%}nav{font-size:.7em;display:block;width:100%;margin:0 0 2em 0}nav a{display:inline-block;text-decoration:none;margin-left:2em;color:#0f7595;white-space:nowrap}nav a:hover{text-decoration:underline}nav a.current{font-weight:bold;text-decoration:underline;color:#666}section{border-top:1px solid #eee;padding:2em 3em 0 3em}ul{margin:0;padding:0}li{display:inline;display:inline-block;background-color:#fff;position:relative;margin:0 2em 2em 0;vertical-align:top;border:1px solid #ccc;padding:1em 1em 3em 1em;cursor:default}.icon-box{margin:0;width:144px;height:144px;position:relative;background:#ccc url(data:image/gif;base64,R0lGODlhDAAMAIAAAMzMzP///yH/C1hNUCBEYXRhWE1QPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS4wLWMwNjEgNjQuMTQwOTQ5LCAyMDEwLzEyLzA3LTEwOjU3OjAxICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M1LjEgV2luZG93cyIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDozQjk4OTI0MUY5NTIxMUUyQkJDMEI5NEFEM0Y1QTYwQyIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDozQjk4OTI0MkY5NTIxMUUyQkJDMEI5NEFEM0Y1QTYwQyI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjNCOTg5MjNGRjk1MjExRTJCQkMwQjk0QUQzRjVBNjBDIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjNCOTg5MjQwRjk1MjExRTJCQkMwQjk0QUQzRjVBNjBDIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+Af/+/fz7+vn49/b19PPy8fDv7u3s6+rp6Ofm5eTj4uHg397d3Nva2djX1tXU09LR0M/OzczLysnIx8bFxMPCwcC/vr28u7q5uLe2tbSzsrGwr66trKuqqainpqWko6KhoJ+enZybmpmYl5aVlJOSkZCPjo2Mi4qJiIeGhYSDgoGAf359fHt6eXh3dnV0c3JxcG9ubWxramloZ2ZlZGNiYWBfXl1cW1pZWFdWVVRTUlFQT05NTEtKSUhHRkVEQ0JBQD8+PTw7Ojk4NzY1NDMyMTAvLi0sKyopKCcmJSQjIiEgHx4dHBsaGRgXFhUUExIREA8ODQwLCgkIBwYFBAMCAQAAIfkEAAAAAAAsAAAAAAwADAAAAhaEH6mHmmzcgzJAUG/NVGrfOZ8YLlABADs=) top left repeat;border:1px solid #ccc;display:table-cell;vertical-align:middle;text-align:center}.icon{display:inline;display:inline-block}h2{margin:0;padding:0;font-size:1em;font-weight:normal;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;position:absolute;left:1em;right:1em;bottom:1em}footer{display:block;margin:0;padding:0 3em 3em 3em}footer p{margin:0;font-size:.7em}footer a{color:#0f7595;margin-left:0}</style>',
			'prenav'												=> '</head><body><header><h1 id="logo">iconizr</h1><p>These are the <strong>'.count($this->_iconNames).' icons</strong> contained in your icon kit, along with their CSS class names.</p></header><section><nav>Icon type:',
			'typelinks'												=> '',
			'postnav'												=> '</nav><ul>',
			'icons'													=> '',
			'end'													=> '</ul></section><footer><p>Generated at '.gmdate('D, d M Y H:i:s', time()).' GMT by <a href="http://iconizr.com" target="_blank">iconizr</a>.</p></footer></body></html>',
		);
		
		// Render the icon previews
		foreach ($this->_iconNames as $icon) {
			$pseudoClassPos											= strrpos($icon, $this->_flags['pseudo']);
			$icon													= ($pseudoClassPos === false) ? $icon : substr($icon, 0, $pseudoClassPos).':'.substr($icon, $pseudoClassPos + 1);
			$preview['icons']										.= '<li title="'.$icon.'"><div class="icon-box"><div class="icon '.$icon.' '.$icon.'-dims"><!-- '.$icon.' --></div></div><h2>'.$icon.'</h2></li>';
		}
		
		// Run through all available icon types / style sheets
		foreach ($stylesheets as $stylesheet => $label) {
			$iconTypePreview										= $preview;
			$iconTypePreview['css']									= strlen($stylesheet) ? '<link href="'.htmlspecialchars($stylesheet).'" rel="stylesheet" type="text/css" media="all"/>' : sprintf($loader, htmlspecialchars(basename($css[self::PNG][self::SPRITE])), htmlspecialchars(basename($css[self::PNG][self::DATA])), htmlspecialchars(basename($css[self::SVG][self::SPRITE])), htmlspecialchars(basename($css[self::SVG][self::DATA])));

			// Run through all available icon types and create the navigation elements
			foreach ($stylesheets as $linkStylesheet => $linkLabel) {
				$linkIconPreviewFilename							= strlen($linkStylesheet) ? pathinfo($linkStylesheet, PATHINFO_FILENAME).'-preview.html' : $this->_flags['css'].'-preview.html';
				$currentIconType									= $stylesheet == $linkStylesheet;
				$iconTypePreview['typelinks']						.= '<a href="'.$linkIconPreviewFilename.'"'.($currentIconType ? ' class="current"' : '').'>'.htmlspecialchars($linkLabel).'</a>';
			}
			
			$previewFilename										= strlen($stylesheet) ? pathinfo($stylesheet, PATHINFO_FILENAME).'-preview.html' : $this->_flags['css'].'-preview.html';
			
			// Write the preview file to disk
			file_put_contents($this->_target.$previewFilename, implode('', $iconTypePreview));
		}
	}
	
	/**
	 * Create a single icon stack
	 * 
	 * @param string $directory					Directory
	 * @param array $icons						SVG icons
	 * @return void
	 */
	protected function _createIconStack($directory, array $icons) {
		$this->_log(sprintf('Processing icon directory "%s"', substr($directory, strlen($this->_cwd) + 1)), self::LOG_INFO);
		
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
	protected function _processSVGIcons($directory, array &$icons) {
		$this->_logGroupStart('Processing SVG icons ...');
		$optimizeSVG							= false;
		
		// If the SVG files should be optimized using Scour
		if ($this->_scour && $this->_binaries['python']) {
			$this->_logGroupStart('Optimizing SVG icons using Scour ...');
			$optimizeSVG						= true;
			
		// If the SVG files should be optimized using SVGO
		} elseif ($this->_binaries['svgo']) {
			$this->_logGroupStart('Optimizing SVG icons using SVGO '.($this->_scour ? '(No Scour due to missing Python 2 support) ' : '').'...');
			$optimizeSVG						= true;
		}
		
		// Run through all icons
		foreach ($icons as $iconIndex => $icon) {
			
			/**
			 * Resolve all named entities inside the SVG document
			 * 
			 * @see https://github.com/jkphl/iconizr/issues/5#issuecomment-23448050
			 */
			try {
				$iconSVG						= new \DOMDocument();
				$iconSVG->load($directory.DIRECTORY_SEPARATOR.$icon, LIBXML_NOENT);
				$iconSVG->save($directory.DIRECTORY_SEPARATOR.$icon);
				unset($iconSVG);
				
			// If an error had occured
			} catch (\Exception $e) {
				$this->_error(sprintf('The icon "%s" seems to be no valid XML document, skipping ...: %s', basename($targetIcon), $e->getMessage()), false, 0);
				continue;
			}
			
			$iconName							= pathinfo($icon, PATHINFO_FILENAME);
			$this->_iconNames[]					= (strlen($this->_prefix) ? $this->_prefix : $this->_tmpName).'-'.$iconName;
			$this->_tmpResources[]				=
			$targetIcon							= $this->_tmpDir.DIRECTORY_SEPARATOR.$icon;
			$iconOptimized						= false;
				
			// If the Scour script is available
			if ($optimizeSVG && $this->_scour && $this->_binaries['python']) {
				$this->_log(sprintf('[%s/%s] Optimizing SVG icon "%s" using Scour', $iconIndex + 1, count($icons), basename($targetIcon)));
				
				// If an optimized copy of the icon can be created ...
				if ($this->_do($this->_binaries['python'], array(
					array($this->_scour),
					'--create-groups',
					'--enable-comment-stripping',
					'--remove-metadata',
					'--indent=none',
					'--renderer-workaround',
					'--strip-xml-prolog',
					'-q',
					'-i'						=> $directory.DIRECTORY_SEPARATOR.$icon,
					'-o'						=> $targetIcon,
				))) {
					$iconOptimized				= true;
					
				// Else: Error and fallback to SVGO (if available)
				} else {
					$this->_error(sprintf('Optimization of icon "%s" with Scour failed, skipping ...', basename($targetIcon)), false, 0);
				}
			}
			
			// If the SVGO binary is available
			if ($optimizeSVG && !$iconOptimized && $this->_binaries['svgo']) {
				$this->_log(sprintf('[%s/%s] Optimizing SVG icon "%s" using SVGO', $iconIndex + 1, count($icons), basename($targetIcon)));
		
				// If an optimized copy of the icon can be created ...
				if ($this->_do($this->_binaries['svgo'], array(
					'-i'						=> $directory.DIRECTORY_SEPARATOR.$icon,
					'-o'						=> $targetIcon,
				))) {
					$iconOptimized				= true;
					
				// Else: Error and fallback to the unoptimized SVG file 
				} else {
					$this->_error(sprintf('Optimization of icon "%s" with SVGO failed, skipping ...', basename($targetIcon)), false, 0);
				}
			}
		
			// If the unoptimized SVG file is to be used
			if (!$iconOptimized && !@copy($directory.DIRECTORY_SEPARATOR.$icon, $targetIcon)) {
				$this->_error(sprintf('Could not copy icon "%s", exiting', basename($targetIcon)));
			}
			
			try {
			
				// Sanitize and prepare optimized SVG file
				$this->_sanitizeSVGIcon($directory, $iconName, $targetIcon);
					
				// Create and register a data URI for this PNG icon
				$this->_registerDataURI($directory, self::SVG, $iconName, $targetIcon);
			
				$this->_useSprite[$directory][self::SVG][$iconName]			= $targetIcon;
				
			// If an error had occured
			} catch (\Exception $e) {
				$this->_error(sprintf('The icon "%s" could not be processed due to the following error: %s', basename($targetIcon), $e->getMessage()));
			}
		}
		
		// If optimization has taken place
		if ($optimizeSVG) {
			$this->_logGroupEnd();
		}
		
		// If single image icons should be created / kept
		if ($this->_flags['keep']) {
			
			// If CSS rules shall be generated
			if ($this->_flags['css'] !== false) {
				$this->_css[self::SVG][self::SINGLE]					= array_merge($this->_css[self::SVG][self::SINGLE], $this->_createSingleImageCssRules($directory, $this->_useSprite[$directory][self::SVG], self::SVG));
			}
				
			// If Sass rules shall be generated
			if ($this->_flags['sass'] !== false) {
				$this->_sass[self::SVG][self::SINGLE]					= array_merge($this->_sass[self::SVG][self::SINGLE], $this->_createSingleImageSassRules($directory, $this->_useSprite[$directory][self::SVG], self::SVG));
			}
		}
		
		// If data URIs can be used
		if ($dataUris = is_array($this->_dataUris[$directory][self::SVG])) {
		
			// If CSS rules shall be generated
			if ($this->_flags['css'] !== false) {
				$this->_css[self::SVG][self::DATA]						= array_merge($this->_css[self::SVG][self::DATA], $this->_createDataURICssRules($directory, $this->_dataUris[$directory][self::SVG], self::SVG));
			}
			
			// If Sass rules shall be generated
			if ($this->_flags['sass'] !== false) {
				$this->_sass[self::SVG][self::DATA]						= array_merge($this->_sass[self::SVG][self::DATA], $this->_createDataURISassRules($directory, $this->_dataUris[$directory][self::SVG], self::SVG));
			}
		}

		// Create SVG sprite and appropriate rules
		$this->_createSVGIconSpriteAndRules($directory, $this->_useSprite[$directory][self::SVG], !$dataUris);
		$this->_logGroupEnd();
	}
	
	/**
	 * Process the PNG icons
	 *
	 * @param string $directory					Directory
	 * @param array $icons						SVG icons
	 * @return void
	 */
	protected function _processPNGIcons($directory, array $icons) {
		$this->_logGroupStart('Processing PNG icons ...');
		
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

			$this->_log('Rendering SVG icons to PNG images ...');
			$rendered															= $this->_do($this->_binaries['phantomjs'], array('iconizr.js'));
			@unlink($phantomJSScript);
			if (!$rendered) {
				$this->_error('Could not render SVG images, exiting');
			}
			
			// If PNG optimization should happen: Optimize and run through als PNG images
			if ($this->_optimize) {
				$this->_logGroupStart('Optimizing PNG images ...');
				$optimizePNGImages													= $this->_optimizePNGImages($this->_useSprite[$directory][self::PNG]);
				foreach ($optimizePNGImages as $png => $optimized) {
					$targetIcon														= $this->_tmpDir.DIRECTORY_SEPARATOR.$optimized;
					if (($targetIcon != $png) && (filesize($targetIcon) < filesize($png))) {
						unlink($png);
						rename($targetIcon, $png);
					}
					
					// Create and register a data URI for this PNG icon
					$this->_registerDataURI($directory, self::PNG, array_search($png, $this->_useSprite[$directory][self::PNG]), $png);
				}
				$this->_logGroupEnd();
				
			// Else: Create data URIs for the unoptimized PNG images
			} else {
				foreach ($this->_useSprite[$directory][self::PNG] as $icon => $png) {
					$this->_registerDataURI($directory, self::PNG, $icon, $png);
				}
			}
			
			// If single image icons should be created / kept
			if ($this->_flags['keep']) {
					
				// If CSS rules shall be generated
				if ($this->_flags['css'] !== false) {
					$this->_css[self::PNG][self::SINGLE]						= array_merge($this->_css[self::PNG][self::SINGLE], $this->_createSingleImageCssRules($directory, $this->_useSprite[$directory][self::PNG], self::PNG));
				}
			
				// If Sass rules shall be generated
				if ($this->_flags['sass'] !== false) {
					$this->_sass[self::PNG][self::SINGLE]						= array_merge($this->_sass[self::PNG][self::SINGLE], $this->_createSingleImageSassRules($directory, $this->_useSprite[$directory][self::PNG], self::PNG));
				}
			}
			
			// If data URIs can be used
			if ($dataUris = is_array($this->_dataUris[$directory][self::PNG])) {
				
				// If CSS rules shall be generated
				if ($this->_flags['css'] !== false) {
					$this->_css[self::PNG][self::DATA]							= array_merge($this->_css[self::PNG][self::DATA], $this->_createDataURICssRules($directory, $this->_dataUris[$directory][self::PNG], self::PNG));
				}
				
				// If Sass rules shall be generated
				if ($this->_flags['sass'] !== false) {
					$this->_sass[self::PNG][self::DATA]							= array_merge($this->_sass[self::PNG][self::DATA], $this->_createDataURISassRules($directory, $this->_dataUris[$directory][self::PNG], self::PNG));
				}
			}
			
			// Create PNG sprite and appropriate rules
			$this->_createPNGIconSpriteAndRules($directory, $this->_useSprite[$directory][self::PNG], !$dataUris);
		}
		
		$this->_logGroupEnd();
	}
	
	/**
	 * Create and register a data URI for a file
	 * 
	 * @param string $directory					Directory
	 * @param string $type						Icon type
	 * @param string $name						Icon name
	 * @param string $file						Icon
	 * @return void
	 */
	protected function _registerDataURI($directory, $type, $name, $file) {
		if (is_array($this->_dataUris[$directory][$type])) {
			$this->_dataUris[$directory][$type][$name]				= ($type == self::PNG) ? 'data:image/png;base64,'.base64_encode(file_get_contents($file)) : 'data:image/svg+xml,'.rawurlencode(@file_get_contents($file));
			if (($dataUriLength = strlen($this->_dataUris[$directory][$type][$name])) > $this->_thresholds[$type]) {
				$this->_dataUris[$directory][$type]					= false;
				$this->_log(sprintf('Data URI for icon "%s" exceeds %s byte limit (%s), switching to sprite only mode', basename($file), $this->_thresholds[$type], $dataUriLength), self::LOG_ALERT);
			}
		}
	}
	
	/**
	 * Determine the dimensions of an icon
	 * 
	 * @param string $directory					Directory
	 * @param string\DOMDocument $icon			Icon
	 * @param string $name						Icon name
	 * @return array							Dimensions (width & height)
	 */
	protected function _getIconDimensions($directory, $icon, $name) {
		if (!array_key_exists($directory, $this->_dimensions)) {
			$this->_dimensions[$directory]				= array();
		}
		
		if (!array_key_exists($name, $this->_dimensions[$directory])) {
			
			// Determine the real icon dimensions (with fallback to the default dimensions)
			$iconSVG									= ($icon instanceof \DOMDocument) ? $icon : $this->_loadSVG($icon);
			$iconWidth									= $iconSVG->documentElement->hasAttribute('width') ? round(floatval($iconSVG->documentElement->getAttribute('width'))) : $this->_width;
			$iconHeight									= $iconSVG->documentElement->hasAttribute('height') ? round(floatval($iconSVG->documentElement->getAttribute('height'))) : $this->_height;
			
			// If the icon dimensions exceeds the maximum dimensions: Scale down
			if (($iconWidth > $this->_maxwidth) || ($iconHeight > $this->_maxheight)) {
				
				// Add a viewBox if it's currently missing
				if (!$iconSVG->documentElement->hasAttribute('viewBox')) {
					$iconSVG->documentElement->setAttribute('viewBox', '0 0 '.$iconWidth.' '.$iconHeight);
				}

				// Determine the icon aspect and scale down appropriately
				$iconAspect								= $iconWidth / $iconHeight;
				$maxIconAspect							= $this->_maxwidth / $this->_maxheight;
				if ($iconAspect >= $maxIconAspect) {
					$iconWidth							= $this->_maxwidth;
					$iconHeight							= round($iconWidth / $iconAspect);
				} else {
					$iconHeight							= $this->_maxheight;
					$iconWidth							= $iconHeight * $iconAspect;
				}
			}

			// Re-set the icon dimensions 
			$iconSVG->documentElement->setAttribute('width', $iconWidth);
			$iconSVG->documentElement->setAttribute('height', $iconHeight);
			
			// Register the icon dimensions
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
		$this->_log('Creating SVG sprite', self::LOG_CREATE);
		$spriteName								= $this->_tmpName.'.svg';
		$spritePath								= $this->_tmpName.DIRECTORY_SEPARATOR.$spriteName;
		$prefix									= strlen($this->_prefix) ? $this->_prefix : $this->_tmpName;
		$css									= array('/* Icons from directory "'.substr($directory, strlen($this->_cwd) + 1).'" */');
		$sass									= array(
			'// Icons from directory "'.substr($directory, strlen($this->_cwd) + 1).'"',
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
				$iconSVG												= $this->_loadSVG($icon);
				list($iconWidth, $iconHeight)							= $this->_getIconDimensions($directory, $iconSVG, $name);
				
				// Clone the icon node and set it's offset in the sprite
				$iconNode												= $spriteSVG->importNode($iconSVG->documentElement, true);
				$iconNode->setAttribute('id', $name);
				$iconNode->setAttribute('y', $viewboxHeight);
				
				// Append the icon to the sprite
				$spriteSVG->documentElement->appendChild($iconNode);
				
				// Construct the selectors (including pseudo-classes)
				$pseudoClassPos											= strrpos($name, $this->_flags['pseudo']);
				if ($pseudoClassPos !== false) {
					$selector											= "$prefix-".substr($name, 0, $pseudoClassPos);
					$selectorDimensions									= "$selector-dims";
					$sassSelector										= $selector.':'.substr($name, $pseudoClassPos + 1).',
.'.$selector.'\\:'.substr($name, $pseudoClassPos + 1);
					$sassSelectorDimensions								= $selectorDimensions.':'.substr($name, $pseudoClassPos + 1).',
.'.$selector.'\\:'.substr($name, $pseudoClassPos + 1).'-dims';
					$selector											.=
					$selectorSuffix										= ':'.substr($name, $pseudoClassPos + 1).',.'.$selector.'\\:'.substr($name, $pseudoClassPos + 1);
					$selectorDimensions									.= "$selectorSuffix-dims";
				} else {
					$selector											= "$prefix-$name";
					$selectorDimensions									=
					$sassSelectorDimensions								= "$selector-dims";
					$sassSelector										= "$selector,
.$selector\\:regular";
					$selector											.= ",.$selector\\:regular";
				}
				
				// Write out the appropriate CSS rules
				$css[$directory.DIRECTORY_SEPARATOR.$name]				= ".$selector{background-image:url('$spritePath');background-position:0 ".(-$viewboxHeight).($viewboxHeight ? 'px' : '').";background-repeat:no-repeat}";
				if ($this->_flags['dims']) {
					$css[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$selectorDimensions{width:".$iconWidth.'px;height:'.$iconHeight.'px}';
				}

				// Write out the appropriate Sass rules
				$sass[$directory.DIRECTORY_SEPARATOR.$name]				= ".$sassSelector {
	@extend %$prefix;
	background-image: url('$spritePath');
	background-position: 0 ".(-$viewboxHeight).($viewboxHeight ? 'px' : '').';
}';
				if ($this->_flags['dims']) {
					$sass[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$sassSelectorDimensions {
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
		$spriteSVG->insertBefore(new \DOMComment('Icons from directory "'.substr($directory, strlen($this->_cwd) + 1).'"'), $spriteSVG->documentElement);
		$spriteSVG->documentElement->setAttribute('width', $viewboxWidth);
		$spriteSVG->documentElement->setAttribute('height', $viewboxHeight);
		$spriteSVG->documentElement->setAttribute('viewBox', '0 0 '.$viewboxWidth.' '.$viewboxHeight);
		
		// Save the SVG sprite
		$spriteSVG->save($this->_target.$spritePath);
		
		// If CSS rules shall be generated
		if ($this->_flags['css'] !== false) {
			$this->_log('Creating SVG sprite CSS rules', self::LOG_CREATE);
			$this->_css[self::SVG][self::SPRITE]						= array_merge($this->_css[self::SVG][self::SPRITE], $css);
			if ($spriteOnly) {
				$this->_css[self::SVG][self::DATA]						= array_merge($this->_css[self::SVG][self::DATA], $css);
			}
		}
		
		// If Sass rules shall be generated
		if ($this->_flags['sass'] !== false) {
			$this->_log('Creating SVG sprite Sass rules', self::LOG_CREATE);
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
		$this->_log('Creating PNG sprite', self::LOG_CREATE);
		$spriteName								= $this->_tmpName.'.png';
		$spritePath								= $this->_tmpName.DIRECTORY_SEPARATOR.$spriteName;
		$prefix									= strlen($this->_prefix) ? $this->_prefix : $this->_tmpName;
		$css									= array('/* Icons from directory "'.substr($directory, strlen($this->_cwd) + 1).'" */');
		$sass									= array(
			'// Icons from directory "'.substr($directory, strlen($this->_cwd) + 1).'"',
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
		imagefill($image, 0, 0, $transparent);
		imagealphablending($image, false);
		imagesavealpha($image, true);
		
		// Run through the single icons
		foreach ($iconParameters as $name => $icon) {
			
			// Construct the selectors (including pseudo-classes)
			$pseudoClassPos											= strrpos($name, $this->_flags['pseudo']);
			if ($pseudoClassPos !== false) {
				$selector											= "$prefix-".substr($name, 0, $pseudoClassPos);
				$selectorDimensions									= "$selector-dims";
				$sassSelector										= $selector.':'.substr($name, $pseudoClassPos + 1).',
.'.$selector.'\\:'.substr($name, $pseudoClassPos + 1);
				$sassSelectorDimensions								= $selectorDimensions.':'.substr($name, $pseudoClassPos + 1).',
.'.$selector.'\\:'.substr($name, $pseudoClassPos + 1).'-dims';
				$selector											.=
				$selectorSuffix										= ':'.substr($name, $pseudoClassPos + 1).',.'.$selector.'\\:'.substr($name, $pseudoClassPos + 1);
				$selectorDimensions									.= "$selectorSuffix-dims";
			} else {
				$selector											= "$prefix-$name";
				$selectorDimensions									=
				$sassSelectorDimensions								= "$selector-dims";
				$sassSelector										= "$selector,
.$selector\\:regular";
				$selector											.= ",.$selector\\:regular";
			}
			
			// Merge the icon with the sprite
			$iconImage												= @imagecreatefrompng($icon[1]);
			imagecopy($image, $iconImage, 0, $icon[2], 0, 0, $icon[3], $icon[4]);
			imagedestroy($iconImage);
			
			// Write out the appropriate CSS rules
			$css[$directory.DIRECTORY_SEPARATOR.$name]				= ".$selector{background-image:url('$spritePath');background-position:0 ".(-$icon[2]).($icon[2] ? 'px' : '').';background-repeat:no-repeat}';
			if ($this->_flags['dims']) {
				$css[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$selectorDimensions{width:".$icon[3].'px;height:'.$icon[4].'px}';
			}

			// Write out the appropriate Sass rules
			$sass[$directory.DIRECTORY_SEPARATOR.$name]				= ".$sassSelector {
	@extend %$prefix;
	background-image: url('$spritePath');
	background-position: 0 ".(-$icon[2]).($icon[2] ? 'px' : '').';
}';
			if ($this->_flags['dims']) {
				$sass[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$sassSelectorDimensions {
	width: ".$icon[3].'px;
	height: '.$icon[4].'px;
}';
			}
		}
		
		// Save the PNG sprite
		imagepng($image, $this->_target.$spritePath);
		imagedestroy($image);
		
		// If PNG optimization should happen: optimize the sprite itself
		if ($this->_optimize) {
			$this->_logGroupStart('Optimizing the PNG sprite ...');
			$optimizedSprite										= $this->_optimizePNGImages(array('sprite' => $this->_target.$spritePath));
			$optimizedSprite										= $this->_target.$this->_tmpName.DIRECTORY_SEPARATOR.$optimizedSprite[$this->_target.$spritePath];
			if (($optimizedSprite != $this->_target.$spritePath) && (filesize($optimizedSprite) < filesize($this->_target.$spritePath))) {
				unlink($this->_target.$spritePath);
				rename($optimizedSprite, $this->_target.$spritePath);
			}
			$this->_logGroupEnd();
		}
		
		// If CSS rules shall be generated
		if ($this->_flags['css'] !== false) {
			$this->_log('Creating PNG sprite CSS rules', self::LOG_CREATE);
			$this->_css[self::PNG][self::SPRITE]					= array_merge($this->_css[self::PNG][self::SPRITE], $css);
			if ($spriteOnly) {
				$this->_css[self::PNG][self::DATA]					= array_merge($this->_css[self::PNG][self::DATA], $css);
			}
		}
		
		// If Sass rules shall be generated
		if ($this->_flags['sass'] !== false) {
			$this->_log('Creating PNG sprite Sass rules', self::LOG_CREATE);
			$this->_sass[self::PNG][self::SPRITE]					= array_merge($this->_sass[self::PNG][self::SPRITE], $sass);
			if ($spriteOnly) {
				$this->_sass[self::PNG][self::DATA]					= array_merge($this->_sass[self::PNG][self::DATA], $sass);
			}
		}
	}
	
	/**
	 * Create CSS rules using single images
	 *
	 * @param string $directory					Directory
	 * @param array $iconImages					Icon images
	 * @param string $type						Icon type
	 * @return array							CSS rules
	 */
	protected function _createSingleImageCssRules($directory, array $iconImages, $type) {
		$this->_log('Creating '.strtoupper($type).' single image CSS rules', self::LOG_CREATE);
		$prefix														= strlen($this->_prefix) ? $this->_prefix : $this->_tmpName;
		$css														= array('/* Icons from directory "'.substr($directory, strlen($this->_cwd) + 1).'" */');
		foreach ($iconImages as $name => $icon) {
			
			// Construct the selectors (including pseudo-classes)
			$pseudoClassPos											= strrpos($name, $this->_flags['pseudo']);
			if ($pseudoClassPos !== false) {
				$selector											= "$prefix-".substr($name, 0, $pseudoClassPos);
				$selectorDimensions									= "$selector-dims";
				$selector											.=
				$selectorSuffix										= ':'.substr($name, $pseudoClassPos + 1).',.'.$selector.'\\:'.substr($name, $pseudoClassPos + 1);
				$selectorDimensions									.= "$selectorSuffix-dims";
			} else {
				$selector											= "$prefix-$name";
				$selectorDimensions									= "$selector-dims";
				$selector											.= ",.$selector\\:regular";
			}
			
			$css[$directory.DIRECTORY_SEPARATOR.$name]				= ".$selector{background-image:url('".substr($icon, strlen($this->_target))."');background-repeat:no-repeat}";
			if ($this->_flags['dims']) {
				list($iconWidth, $iconHeight)						= $this->_getIconDimensions($directory, $icon, $name);
				$css[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$selectorDimensions{width:".$iconWidth.'px;height:'.$iconHeight.'px}';
			}
		}
		return $css;
	}
	
	/**
	 * Create CSS rules using data URIs
	 * 
	 * @param string $directory					Directory
	 * @param array $dataURIs					Data URIs
	 * @param string $type						Icon type
	 * @return array							CSS rules
	 */
	protected function _createDataURICssRules($directory, array $dataURIs, $type) {
		$this->_log('Creating '.strtoupper($type).' data URI CSS rules', self::LOG_CREATE);
		$prefix														= strlen($this->_prefix) ? $this->_prefix : $this->_tmpName;
		$css														= array('/* Icons from directory "'.substr($directory, strlen($this->_cwd) + 1).'" */');
		foreach ($dataURIs as $name => $icon) {

			// Construct the selectors (including pseudo-classes)
			$pseudoClassPos											= strrpos($name, $this->_flags['pseudo']);
			if ($pseudoClassPos !== false) {
				$selector											= "$prefix-".substr($name, 0, $pseudoClassPos);
				$selectorDimensions									= "$selector-dims";
				$selector											.=
				$selectorSuffix										= ':'.substr($name, $pseudoClassPos + 1).',.'.$selector.'\\:'.substr($name, $pseudoClassPos + 1);
				$selectorDimensions									.= "$selectorSuffix-dims";
			} else {
				$selector											= "$prefix-$name";
				$selectorDimensions									= "$selector-dims";
				$selector											.= ",.$selector\\:regular";
			}
			
			$css[$directory.DIRECTORY_SEPARATOR.$name]				= ".$selector{background-image:url('$icon');background-repeat:no-repeat}";
			if ($this->_flags['dims']) {
				list($iconWidth, $iconHeight)						= $this->_getIconDimensions($directory, $icon, $name);
				$css[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$selectorDimensions{width:".$iconWidth.'px;height:'.$iconHeight.'px}';
			}
		}
		return $css;
	}

	/**
	 * Create Sass rules using single images
	 *
	 * @param string $directory					Directory
	 * @param array $iconImages					Icon images
	 * @param string $type						Icon type
	 * @return array							Sass rules
	 */
	protected function _createSingleImageSassRules($directory, array $iconImages, $type) {
		$this->_log('Creating '.strtoupper($type).' single image Sass rules', self::LOG_CREATE);
		$prefix														= strlen($this->_prefix) ? $this->_prefix : $this->_tmpName;
		$sass														= array(
			'// Icons from directory "'.substr($directory, strlen($this->_cwd) + 1).'"',
			"%$prefix {
	background-repeat: no-repeat
}",
		);
		foreach ($iconImages as $name => $icon) {
			
			// Construct the selectors (including pseudo-classes)
			$pseudoClassPos											= strrpos($name, $this->_flags['pseudo']);
			if ($pseudoClassPos !== false) {
				$selector											= "$prefix-".substr($name, 0, $pseudoClassPos);
				$selectorDimensions									= "$selector-dims";
				$selector											.=
				$selectorSuffix										= ':'.substr($name, $pseudoClassPos + 1).',
.'.$selector.'\\:'.substr($name, $pseudoClassPos + 1);
				$selectorDimensions									.= "$selectorSuffix-dims";
			} else {
				$selector											= "$prefix-$name";
				$selectorDimensions									= "$selector-dims";
				$selector											.= ",
.$selector\\:regular";
			}
			
			$sass[$directory.DIRECTORY_SEPARATOR.$name]				= ".$selector {
	@extend %$prefix;
	background-image: url('".substr($icon, strlen($this->_target))."');
}";
			if ($this->_flags['dims']) {
				list($iconWidth, $iconHeight)						= $this->_getIconDimensions($directory, $icon, $name);
				$sass[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$selectorDimensions {
	width: ".$iconWidth.'px;
	height: '.$iconHeight.'px;
}';
		}
		}
		return $sass;
	}
	
	/**
	 * Create Sass rules using data URIs
	 *
	 * @param string $directory					Directory
	 * @param array $dataURIs					Data URIs
	 * @param string $type						Icon type
	 * @return array							Sass rules
	 */
	protected function _createDataURISassRules($directory, array $dataURIs, $type) {
		$this->_log('Creating '.strtoupper($type).' data URI Sass rules', self::LOG_CREATE);
		$prefix														= strlen($this->_prefix) ? $this->_prefix : $this->_tmpName;
		$sass														= array(
			'// Icons from directory "'.substr($directory, strlen($this->_cwd) + 1).'"',
			"%$prefix {
	background-repeat: no-repeat
}",
		);
		foreach ($dataURIs as $name => $icon) {
			
			// Construct the selectors (including pseudo-classes)
			$pseudoClassPos											= strrpos($name, $this->_flags['pseudo']);
			if ($pseudoClassPos !== false) {
				$selector											= "$prefix-".substr($name, 0, $pseudoClassPos);
				$selectorDimensions									= "$selector-dims";
				$selector											.=
				$selectorSuffix										= ':'.substr($name, $pseudoClassPos + 1).',
.'.$selector.'\\:'.substr($name, $pseudoClassPos + 1);
				$selectorDimensions									.= "$selectorSuffix-dims";
			} else {
				$selector											= "$prefix-$name";
				$selectorDimensions									= "$selector-dims";
				$selector											.= ",
.$selector\\:regular";
			}
			
			$sass[$directory.DIRECTORY_SEPARATOR.$name]				= ".$selector {
	@extend %$prefix;
	background-image: url('$icon');
}";
			if ($this->_flags['dims']) {
				list($iconWidth, $iconHeight)						= $this->_getIconDimensions($directory, $icon, $name);
				$sass[$directory.DIRECTORY_SEPARATOR.$name.'-dims']	= ".$selectorDimensions {
	width: ".$iconWidth.'px;
	height: '.$iconHeight.'px;
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
		$svg							= new \DOMDocument();
		$svg->preserveWhitespace		= false;
		$svg->formatOutput				= false;
		
		if (!$svg->load($file)) {
			$message					= array();
			
			/* @var $error \libXMLError */
			foreach (libxml_get_errors() as $error) {
				$message[]				= $error->message;
			}
		
			libxml_clear_errors();
			libxml_use_internal_errors(false);
			
			throw new \Exception(implode('; ', $message));
		}
		
		libxml_use_internal_errors(false);
		return $svg;
	}
	
	/**
	 * Sanitize an SVG file
	 * 
	 * @param string $directory				Directory
	 * @param string $name					Icon name
	 * @param string $file					SVG file
	 * @return void
	 */
	protected function _sanitizeSVGIcon($directory, $name, $file) {
		file_put_contents($file, preg_replace("%[\r\n]+%", '', file_get_contents($file)));
		$icon								= $this->_loadSVG($file);
		list($iconWidth, $iconHeight)		= $this->_getIconDimensions($directory, $icon, $name);
		
		// If a global icon padding should be applied
		if ($this->_padding > 0) {
			$viewBox									= array(0, 0, $iconWidth, $iconHeight);
			foreach (($icon->documentElement->hasAttribute('viewBox') ? preg_split('%\s+%', trim($icon->documentElement->getAttribute('viewBox'))) : array()) as $index => $value) {
				if (strlen($value)) {
					$viewBox[$index]					= floatval($value);
				}
			}
			$iconWidth									+= 2 * $this->_padding;	
			$iconHeight									+= 2 * $this->_padding;	
			$this->_dimensions[$directory][$name]		= array($iconWidth, $iconHeight);
			
			$viewBox[0]									-= $this->_padding;
			$viewBox[1]									-= $this->_padding;
			$viewBox[2]									+= 2 * $this->_padding;
			$viewBox[3]									+= 2 * $this->_padding;
			$icon->documentElement->setAttribute('viewBox', implode(' ', $viewBox));
		}
		
		$icon->documentElement->setAttribute('width', $iconWidth);
		$icon->documentElement->setAttribute('height', $iconHeight);
		
		// Experimental: ID substitution / namespacing
		$xpath								= new \DOMXPath($icon);
		$ids								= array();
		foreach ($xpath->query('//@id') as $id) {
			$id->nodeValue					=
			$ids[$id->nodeValue]			= $name.'-'.$id->nodeValue;
		}
		
		// Serialize icon SVG
		$icon								= $icon->saveXML($icon->documentElement);
		
		// Experimental: ID substitution / namespacing
		foreach ($ids as $from => $to) {
			$pattern						= "%\#".quotemeta($from)."(?![".self::PCRE_ID_START_CHARS.self::PCRE_ID_FOLLOWER_CHARS."])%u";
			$icon							= preg_replace($pattern, "#$to", $icon);
		}
		
		// Save sanitized SVG file to disk
		file_put_contents($file, $icon);
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
		$optimized							= 0;
		$pngFilenames						= array();
		foreach ($pngs as $png) {
			$pngFilenames[$png]				= pathinfo($png, PATHINFO_FILENAME);
		}
		
		// If pngcrush is available
		if ($this->_binaries['pngcrush']) {
			$this->_logGroupStart('Optimizing using "pngcrush" ...');
			$suffix							= '-pc';
			$suffices[]						= 'pc';
			$params							= array(
				'-brute',
				'-reduce',
				'-e'						=> '-pc.png',
			);
			if ($this->_flags['verbose'] < 3) {
				$params[]					= '-q';
			}
			foreach (array_values($pngs) as $pngIndex => $png) {
				$pngParams					= $params;
				$pngParams[]				= array($png);
				$this->_log(sprintf('[%s/%s] Optimizing PNG icon "%s" with "pngcrush"', $pngIndex + 1, count($pngs), basename($png)));
				$this->_do($this->_binaries['pngcrush'], $pngParams);
			}
			$pngs							= $this->_mapFileExtension($pngFilenames, $suffix);
			$this->_logGroupEnd();
			++$optimized;
		}
			
		// If pngquant is available
		if ($this->_flags['quantize'] && $this->_binaries['pngquant']) {
			$this->_logGroupStart('Optimizing using "pngquant" ...');
			$suffix							.= '-pq';
			$suffices[]						= 'pq';
			$params							= array(
				'--speed'					=> $this->_speed,
				'--force',
				'--transbug',
				'--ext'						=> '-pq.png',
			);
			if ($this->_flags['verbose'] >= 3) {
				$params[]					= '--verbose';
			}
			foreach (array_values($pngs) as $pngIndex => $png) {
				$pngParams					= $params;
				$pngParams[]				= array($png);
				$this->_log(sprintf('[%s/%s] Optimizing PNG icon "%s" with "pngquant"', $pngIndex + 1, count($pngs), basename($png)));
				$this->_do($this->_binaries['pngquant'], $pngParams);
			}
			
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
			$this->_logGroupEnd();
			++$optimized;
		}
		
		// If optipng is available
		if ($this->_binaries['optipng']) {
			$this->_logGroupStart('Optimizing using "optipng" ...');
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
			if ($this->_flags['verbose'] < 3) {
				$params[]					= '-quiet';
			}
			foreach (array_values($optipngs) as $pngIndex => $png) {
				$pngParams					= $params;
				$pngParams[]				= array($png);
				$this->_log(sprintf('[%s/%s] Optimizing PNG icon "%s" with "optipng"', $pngIndex + 1, count($pngs), basename($png)));
				$this->_do($this->_binaries['optipng'], $pngParams);
			}
			
			foreach ($optipngs as $png => $optipng) {
				$directory					= dirname($png).DIRECTORY_SEPARATOR;
				if (@is_file($directory.$optipng) && (@filesize($directory.$optipng) < filesize($directory.$pngs[$png]))) {
					@unlink($directory.$pngs[$png]);
					$pngs[$png]				= $optipng;
				} else {
					@unlink($directory.$optipng);
				}
			}
			$this->_logGroupEnd();
			++$optimized;
		}
		
		// If no optimization could be applied
		if (!$optimized) {
			$pngs							= $this->_mapFileExtension($pngFilenames, '');
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
	 * Return the major version of a Python binary
	 * 
	 * @param string $python				Absolute Python binary path
	 * @return int							Major Python version
	 */
	protected function _pythonMajorVersion($python) {
		$pythonMajorVersion					= null;
		$pythonHandle						= popen($python.' --version 2>&1', 'r');
		if ($pythonHandle) {
			$pythonVersionString			= fread($pythonHandle, 1024);
			pclose($pythonHandle);
			if (preg_match("%^Python\s+(\d+)(?:\.\d+)*$%i", $pythonVersionString, $pythonVersion)) {
				$pythonMajorVersion			= intval($pythonVersion[1]);
			}
		}
		return $pythonMajorVersion;
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
		if (!array_key_exists($path, $this->_uniqueDirs)) {
			$dirname									= basename($path);
			$mainDirnamePath							= array_search($dirname, $this->_uniqueDirs);
			
			// If a directory with the exact same name already exists
			if ($mainDirnamePath !== false) {
				$this->_uniqueDirs[$mainDirnamePath]	.= '~1';
				$this->_uniqueDirs[$path]				= $dirname.'~2';
				
			// Else: find the next free Slot
			} elseif (in_array($dirname.'~1', $this->_uniqueDirs)) {
				$suffix									= 1;
				while(in_array($dirname.'~'.++$suffix, $this->_uniqueDirs)) {}
				$this->_uniqueDirs[$path]				= $dirname.'~'.$suffix;
				
			// Else 
			} else {
				$this->_uniqueDirs[$path]				= $dirname;
			}
		}
		return $this->_uniqueDirs[$path];
	}
	
	/**
	 * Output a progress message
	 * 
	 * @param string $message				Message
	 * @param integer $type					Message type
	 * @param boolean $verbose				Output only if verbose mode is active
	 * @return void
	 */
	protected function _log($message, $type = self::LOG_INFO, $verbose = false) {
		if (strlen(trim($message)) && ($this->_flags['verbose'] > ($verbose ? 2 : 0))) {
			
			// If the output should be indented / formatted
			if (($this->_flags['verbose'] >= 2) && !$verbose) {
				
				// If this is an error message
				if ($type == self::LOG_ERROR) {
					$formatted				= '!!!';
					
				// If this is an alert message
				} elseif ($type == self::LOG_ALERT) {
					$formatted				= '>>>';
					
				// If this message is part of a message group
				} elseif (($this->_logGroup > 0) && ($type != self::LOG_GROUP)) {
					$formatted				= '| '.str_repeat('  | ', $this->_logGroup - 2);					
					
				// Else
				} else {
					$formatted				= '';
				}
				
				switch ($type) {
					case self::LOG_ERROR:
					case self::LOG_ALERT:
						break;
						
					case self::LOG_GROUP:
						$formatted			.= ($this->_logGroup > 1) ? '|'.str_repeat('   |', $this->_logGroup - 2).'--' : '|==';
						break;
						
					case self::LOG_CREATE:
						$formatted			.= '+';
						break;
						
					default:
						$formatted			.= ($this->_logGroup > 0) ? ' ' : '';
						break;
				}
				
				$message					= "$formatted $message";
			}
			
			echo trim($message)."\n";
		}
	}
	
	/**
	 * Output an error message and exit
	 * 
	 * @param string $message				Message
	 * @param int $exit						Exit with exit code
	 * @return void
	 */
	protected function _error($message, $verbose = false, $exit = 1) {
		$this->_log('ERROR: '.$message, self::LOG_ERROR);
		if (intval($exit) > 0) {
			exit(intval($exit));
		}
	}
	
	/**
	 * Start a new logging group
	 * 
	 * @param string $message				Message
	 * @param boolean $verbose				Output only if verbose mode is active
	 * @return void
	 */
	protected function _logGroupStart($message, $verbose = false) {
		++$this->_logGroup;
		$this->_log($message, self::LOG_GROUP, $verbose);
	}

	/**
	 * End a new logging group
	 *
	 * @return void
	 */
	protected function _logGroupEnd() {
		--$this->_logGroup;
	}
}

new Iconizr();
