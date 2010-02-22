/*
Script: RadClass.js
	Makes the passed-in element rad.
*/

var RadClass = new Class({
	Implements: Options,	
	options : {},
	
	rad : true,
	
	initialize : function(elem, options) {
		this.setOptions(options);
		this.elem = elem;
		
		this.elem.set('html', "I am rad.")
						 .setStyles({ 'background-color':'red', 'color':'white' });
	}
});