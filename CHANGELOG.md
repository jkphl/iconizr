#### Bugs

1.	Fixed CSS selector bug in Sass files for SVG sprites (#6)


Beta 6 (2013-08-29)
-------------------

#### Bugs

1.	Improved robustness & tolerance for failed / erroneous SVG optimizations (#5)


Beta 5 (2013-08-27)
-------------------

#### Enhancements / Features

1.	Added support for maximum icon dimensions (#3)
2.	Added support for icon pseudo classes (#4)


Beta 4 (2013-08-26)
-------------------

#### Bugs

1.	Corrected some minor inline documentation errors

#### Enhancements / Features

1.	Dropped --root argument in favor of --embed argument (support for explicit HTML embed path prefix)


Beta 3 (2013-08-22)
-------------------

#### Bugs

1.	Data URI Sass rules were created although not configured 

#### Enhancements / Features

1.	Added support for single image icon stylesheets
2.	Added support for root-relative CSS directory


Beta 2 (2013-08-05)
-------------------

#### Bugs

1.	Python 2 verification for proper [Scour](http://www.codedread.com/scour) support (#2)

#### Enhancements / Features

1.	The XML prolog in [Scour](http://www.codedread.com/scour)-optimized SVG graphics now gets omitted (#1)
2.	Added command line switches for default icon width and height
3.	Added support for skipping PNG optimization
4.	Documentation updates
5.	Experimental SVG file sanitation (default width / height, ID namespacing) 
6.	Optimized handling of identical input directory names
7.	Optimized log messages


Initial release (2013-6-22)
---------------------------

Initial proof-of-concept release