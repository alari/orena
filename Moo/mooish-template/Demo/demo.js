window.addEvent('domready', function(){
	$$('.not-rad').addEvent('click', function(){
		new RadClass(this);
	});
});