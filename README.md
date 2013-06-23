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