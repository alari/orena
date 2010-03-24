Om.Tag = new Class({
	Implements: [Events, Options, Class.Binds],
	Binds: ['fireClickTitle','fireClickDelete'],
	options: {
		id: null,
		defaultClass: 'om-tag',
		deleteClass: 'om-tag-delete',
		titleClass: 'om-tag-title',
		selectedClass: 'om-tag-selected',
		deleteText: 'X',
		
		/*
		onSelect
		onUnselect
		onClickTitle
		onClickDelete
		 */
		
		field: null,
		showDelete: true
	},
	
	title: null,
	el: null,
	isSelected: false,
	
	initialize: function(title, options){
		this.setOptions(options);
		// create element with title
		this.title = title;
		this.el = new Element("span", {class: this.options.defaultClass});
		this.el.adopt( new Element("span", {
			class: this.options.titleClass,
			text: this.title,
			events: {click: this.fireClickTitle}
		}));
		// if need to show delete, show it
		if(this.options.showDelete) {
			this.el.adopt( new Element("span", {
				class: this.options.deleteClass,
				text: this.options.deleteText,
				events: {click: this.fireClickDelete}
			}));
		}
		// if need to create hidden field for forms, create it
		if(this.options.field) {
			this.el.adopt(new Element("input", {
				type: "hidden",
				name: this.options.field,
				value: $pick(this.options.id, this.title)
			}));
		}
		
		// add default events listeners -- after those given in options
		this.addEvent("clickTitle", this.evtClickTitle);
		this.addEvent("clickDelete", this.evtClickDelete);
	},
	
	// $ shortcut
	toElement: function(){
		return this.el;
	},
	
	// selecting workaround
	select: function(){
		this.isSelected = true;
		this.el.addClass(this.options.selectedClass);
		this.fireEvent("select", this);
	},
	unselect: function(){
		this.isSelected = false;
		this.el.removeClass(this.options.selectedClass);
		this.fireEvent("unselect", this);
	},
	toggle: function(){
		this.isSelected ? this.unselect() : this.select();
	},
	
	// fire events
	fireClickTitle: function(){
		this.fireEvent("clickTitle", this);
	},
	fireClickDelete: function(){
		this.fireEvent("clickDelete", this);
	},
	
	// default handlers for events
	evtClickDelete: function(tag){
		$(tag).dispose();
	},
	evtClickTitle: function(tag){
		tag.toggle();
	}
});

Om.Tag.List;
Om.Tag;
Om.Tag.Editable;