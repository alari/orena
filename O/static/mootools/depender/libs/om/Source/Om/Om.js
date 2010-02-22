var OmClass = new Class({
	use: function(){
		var callback = $A(arguments).getLast();
		var deps = $A(arguments).erase(callback);
		Depender.require({scripts:deps,callback:callback.create({arguments:this})});
	},
	getHtml: function(url, updateElementId, params){
		this.use("Request.HTML", function(){
			new Request.HTML({url:url,update:$(updateElementId)}).post(params);
		});
	}
});
var Om = new OmClass();