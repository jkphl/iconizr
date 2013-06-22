iconizr
=======


Data URIs:
https://developer.mozilla.org/en-US/docs/data_URIs
http://caniuse.com/datauri
iPhone: 128kB http://blog.clawpaws.net/post/2007/07/16/Storing-iPhone-apps-locally-with-data-URLs#c1989348
http://roderick.dk/experiments/data-uri-limits/


SVG Support:
http://caniuse.com/svg

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
		<td>Size</td>
		<td>33.3 KB (31.2 KB)</td>
		<td>21.3 KB (22.3 KB)</td>
	</tr>
	<tr>
		<td rowspan="2">PNG data URI</td>
		<td>Requests</td>
		<td>1</td>
		<td>1</td>
	</tr>
	<tr>
		<td>Size</td>
		<td>30.1 KB (41.1 KB)</td>
		<td>26.2 KB (35.8 KB)</td>
	</tr>
	<tr>
		<td rowspan="2">SVG sprite</td>
		<td>Requests</td>
		<td>-</td>
		<td>2</td>
	</tr>
	<tr>
		<td>Size</td>
		<td>-</td>
		<td>42.8 KB (496.0 KB)</td>
	</tr>
	<tr>
		<td rowspan="2">SVG data URI</td>
		<td>Requests</td>
		<td>1</td>
		<td>1</td>
	</tr>
	<tr>
		<td>Size</td>
		<td>126.0 KB (1.9 MB)</td>
		<td>49.4 KB (720.0 KB)</td>
	</tr>
</table>