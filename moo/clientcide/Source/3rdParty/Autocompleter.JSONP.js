/*
---
script: Autocompleter.JSONP.js

description: Implements Request.JSONP support for the Autocompleter class.

license: MIT-Style License

requires:
- more:1.2.4.2/Request.JSONP
- /Autocompleter.Remote

provides:
- Autocompleter.JSONP
...
*/

Autocompleter.JSONP = new Class({

	Extends: Autocompleter.Ajax.Json,

	options: {
		postVar: 'query',
		jsonpOptions: {},
//		onRequest: $empty,
//		onComplete: $empty,
//		filterResponse: $empty
		minLength: 1
	},

	initialize: function(el, url, options) {
		this.url = url;
		this.setOptions(options);
		this.parent(el, options);
	},

	query: function(){
		var data = $unlink(this.options.jsonpOptions.data||{});
		data[this.options.postVar] = this.queryValue;
		this.jsonp = new Request.JSONP($merge({url: this.url, data: data},	this.options.jsonpOptions));
		this.jsonp.addEvent('onComplete', this.queryResponse.bind(this));
		this.fireEvent('onRequest', [this.element, this.jsonp, data, this.queryValue]);
		this.jsonp.send();
	},
	
	queryResponse: function(response) {
		this.parent();
		var data = (this.options.filter)?this.options.filter.run([response], this):response;
		this.update(data);
	}

});
Autocompleter.JsonP = Autocompleter.JSONP;