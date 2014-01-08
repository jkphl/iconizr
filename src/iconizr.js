(function() {
	var win					= window,
	doc						= win.document,
	stylesheets				= arguments,
	createElementNS			= 'createElementNS',
	w3						= 'http://www.w3.org/',
	svg						= 'svg',
	insertStylesheet		= function(dataUri) {
		var firstStylesheet	= doc.getElementsByTagName('script')[0],
		link				= doc.createElement('link');
		link.rel			= 'stylesheet';
		link.type			= 'text/css';
		link.href			= stylesheets[(dataUri * 1) | ((doc[createElementNS] && doc[createElementNS](w3 + '2000/' + svg, svg).createSVGRect && doc.implementation.hasFeature(w3 + 'TR/SVG11/feature#Image', '1.1')) * 2)];
		firstStylesheet.parentNode.insertBefore(link, firstStylesheet);
	},
	dataUriImage			= new win.Image();
	dataUriImage.onerror	= function() { insertStylesheet(0); };
	dataUriImage.onload		= function() { insertStylesheet((dataUriImage.width === 1) && (dataUriImage.height === 1)); };
	dataUriImage.src		= 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
})('%s', '%s', '%s', '%s');