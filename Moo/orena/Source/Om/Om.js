var Om = {
	use: function(){
		var callback = $A(arguments).getLast();
		var deps = $A(arguments).erase(callback);
		Depender.require({scripts:deps,callback:callback});
	},
	getHtml: function(url, updateElementId, params){
		this.use("Request.HTML", function(){
			new Request.HTML({url:url,update:$(updateElementId)}).post(params);
		});
	},
	domready: function(callback){
		this.use("DomReady", function(){
			$(window).addEvent("domready", callback);
		});
	}
}