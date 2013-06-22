Example output directory 
========================

To run the example conversion, change to the `/example` directory (this directory's parent directory) and run the following code on the command line:

	 ../iconizr.phps --css weather --sass weather --dims --verbose --o css weather
	 
or alternatively (with short arguments):

	 ../iconizr.phps -c weather -s weather -dv -o css weather
	 
This will convert all SVG icons in the `/example/weather` directory and put the results in the `/example/css` directory (this one). Also, a [preview page](weather-preview.php) will be generated, showing you the different rendering types (PNG with data URIs or sprite, SVG with data URIs or sprite).