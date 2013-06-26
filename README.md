iconizr
=======
is a proof-of-concept command line tool that helps you prepare your vector based SVG icons for use with the widest possible range of devices. It takes a [folder of SVG files](example/weather) and processes them to a bunch of files in several formats, including

*	Cleaned versions of the original **SVG icons**,
*	a single compact **SVG icon sprite**,
*	single **PNG icons**,
*	a combined **PNG icon sprite**,
*	several **CSS files** with different formats inlcuding
	*	SVG data URIs,
	*	SVG sprite references,
	*	PNG data URIs and
	*	PNG sprite references,
*	**Sass variants** of these CSS files for easy inclusion into your Sass project,
*	an **HTML fragment** including some JavaScript for asynchronously loading the most appropriate icon variant
*	and finally an **HTML/PHP preview page** for testing the different icon variants. 

Currently *iconizr* is just at a **proof-of-concept stage** and requires some additional tools to be installed independently ([see below](#requirements)).  

Comparison to grunticon
-----------------------
While doing pretty much the same as the Filament Group's / Scott Jehl's [grunticon](https://github.com/filamentgroup/grunticon), *iconizr* especially focuses on reducing the size of files and number HTTP requests, which could be particularly interesting for mobile devices:

1.	SVG files are cleaned and freed from a lot of cruft typically introduced by SVG editing application before they get converted to data URIs or embedded into the SVG sprite.
2.	PNG files are losslessly optimized (and optionally quantized to 8-bit files) before being used in data URIs or the PNG sprite.
3.	As soon as **even one of the icons** needs to be loaded externally (due to exceeding a potential data URI size limitation), **all icons** will get loaded via the corresponding sprite.  


Evtl. kein Limit für data-URIs bei SVG? Welcher Browser mit SVG-Support hat das kleinste data-URI-Limit?

<table>
	<tr>
		<th colspan="2">Rendering mode</th>
		<th>grunticon</th>
		<th>iconizr</th>
	</tr>
	<tr>
		<td rowspan="2">PNG images / sprite</td>
		<td>Requests</td>
		<td>11</td>
		<td>2</td>
	</tr>
	<tr>
		<td>Size (KB)</td>
		<td>33.3 / 31.2</td>
		<td>20.0 / 21.1</td>
	</tr>
	<tr>
		<td rowspan="2">PNG data URI</td>
		<td>Requests</td>
		<td>1</td>
		<td>1</td>
	</tr>
	<tr>
		<td>Size (KB)</td>
		<td>30.1 / 41.1</td>
		<td>20.3 / 29.7</td>
	</tr>
	<tr>
		<td rowspan="2">SVG sprite</td>
		<td>Requests</td>
		<td>-</td>
		<td>2</td>
	</tr>
	<tr>
		<td>Size (KB)</td>
		<td>-</td>
		<td>42.8 / 496.0</td>
	</tr>
	<tr>
		<td rowspan="2">SVG data URI</td>
		<td>Requests</td>
		<td>1</td>
		<td>1</td>
	</tr>
	<tr>
		<td>Size (KB)</td>
		<td>126.0 / 1,913.0</td>
		<td>49.4 / 720.0</td>
	</tr>
</table>


Requirements
------------
[Scour - an SVG scrubber](http://www.codedread.com/scour)


Resources
---------

*	[Data URIs](https://developer.mozilla.org/en-US/docs/data_URIs)
*	[Data URI support](http://caniuse.com/datauri)
*	[Data URI limitation on iPhone](iPhone: 128kB http://blog.clawpaws.net/post/2007/07/16/Storing-iPhone-apps-locally-with-data-URLs#c1989348)
*	[Data URI limitation checker](http://odl-nbg.de/test/datauri.php)

SVG Support:
http://caniuse.com/svg

Legal
-----
*iconizr* by Joschi Kuphal is licensed under a [Creative Commons Attribution 3.0 Unported
License](http://creativecommons.org/licenses/by/3.0/).

