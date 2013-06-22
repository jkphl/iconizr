(function() {
	var win					= window,
	doc						= win.document,
	stylesheets				= arguments,
	insertStylesheet		= function(dataUri) {
		var firstStylesheet	= doc.getElementsByTagName('script')[0],
		link				= doc.createElement('link');
		link.rel			= 'stylesheet';
		link.type			= 'text/css';
		link.href			= stylesheets[(dataUri * 1) | ((doc.createElementNS && doc.createElementNS('http://www.w3.org/2000/svg', 'svg').createSVGRect && doc.implementation.hasFeature('http://www.w3.org/TR/SVG11/feature#Image', '1.1')) * 2)];
		firstStylesheet.parentNode.insertBefore(link, firstStylesheet);
	},
	dataUriImage			= new win.Image();
	dataUriImage.onerror	= function() { insertStylesheet(0); };
	dataUriImage.onload		= function() { insertStylesheet((dataUriImage.width === 1) && (dataUriImage.height === 1)); };
	dataUriImage.src		= 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
})('%s', '%s', '%s', '%s');