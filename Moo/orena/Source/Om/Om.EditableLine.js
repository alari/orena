Om.EditableLine = new Class({
	Implements: [Events, Options, Class.Binds],
	Binds: ['evtClick','evtBlur','evtKeypress','evtChange'],
	el: null,
	options: {
		classNull: 'om-editableline-null',
		classActive: 'om-editableline-active',
		classDefault: 'om-editableline'
		/*
		nullValue: string,
		onFocus: function(el, this)
		onBlur: function(value, el, this)
		onKeypress: function(event, el, this)
		onChange: function(value, el, this)
		*/
	},
	initialize: function(el, options){
		this.el = $(el);
		this.setOptions(options);
		this.el.addClass(this.options.classDefault);
		
		if(!this.options.nullValue) {
			this.options.nullValue = this.el.get("text");
		}
		this.checkNull();
		
		this.el.addEvent("click", this.evtClick);
		this.el.addEvent("blur", this.evtBlur);
		this.el.addEvent("keydown", this.evtKeypress);
		this.el.addEvent("keyup", this.evtChange);
	},
	
	checkNull: function(){
		if(this.el.get("text") == this.options.nullValue) {
			this.el.addClass(this.options.classNull);
			return true;
		}
		return false;
	},
	
	evtClick: function(){
		if(this.checkNull()) {
			this.el.set("text", "");
		}
		this.el.removeClass(this.options.classNull);
		this.el.addClass(this.options.classActive);
		this.el.setProperty("contentEditable", true);
		this.el.focus();
		this.fireEvent("focus", [this.el, this]);
	},
	evtBlur: function(){
		this.el.removeClass(this.options.classActive);
		this.el.setProperty("contentEditable", false);
		if(!this.el.get("text")) {
			this.el.set("text", this.options.nullValue);
		} else {
			this.el.set("html", this.el.get("text"));
		}
		this.checkNull();
		this.evtChange();
		this.fireEvent("blur", [this.el.get("text"), this.el, this]);
	},
	evtKeypress: function(e) {
		if(e.key == "enter") {
			this.el.blur();
			e.stop();
		}
		this.fireEvent("keypress", [e, this.el, this]);
	},
	evtChange: function(){
		this.fireEvent("change", [this.el.get("text"), this.el, this]);
	}
});