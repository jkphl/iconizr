Example output directory 
========================

To run the example conversion, change to the `/example` directory (this directory) and run the following code on the command line:

	 ../iconizr.phps --css weather --sass weather --dims --keep --verbose --out css --sassout sass weather
	 
or alternatively (with short arguments):

	 ../iconizr.phps -c weather -s weather -dkv -o css --sassout sass weather
	 
This will convert all SVG icons in the `/example/weather` directory and put the results in the `/example/css` respectively `/example/sass` directory. Also, a [preview page](css/weather-preview.php) will be generated, showing you the different rendering types (PNG with data URIs or sprite, SVG with data URIs or sprite).