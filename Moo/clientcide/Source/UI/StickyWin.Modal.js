/*
---

script: StickyWin.Modal.js

description: This script extends StickyWin and StickyWin.Fx classes to add Mask functionality.

license: MIT-Style License

requires:
- more:1.2.4.2/Mask
- /StickyWin

provides:
- StickyWin.Modal
...
*/
StickyWin.Modal = new Class({

	Extends: StickyWin,

	options: {
		modalize: true,
		maskOptions: {
			style: {
				'background-color':'#333',
				opacity:0.8
			}
		},
		hideOnClick: true,
		getWindowManager: function(){ return StickyWin.ModalWM; }
	},

	initialize: function(options) {
		this.options.maskTarget = this.options.maskTarget || document.body;
		this.setOptions(options);
		this.mask = new Mask(this.options.maskTarget, this.options.maskOptions).addEvent('click', function() {
			if (this.options.hideOnClick) this.hide();
		}.bind(this));
		this.parent(options);
	},

	show: function(showModal){
		if ($pick(showModal, this.options.modalize)) this.mask.show();
		this.parent();
	},

	hide: function(hideModal){
		if ($pick(hideModal, true)) this.mask.hide();
		this.parent();
	},

	destroy: function(){
		this.mask.destroy();
		this.parent.apply(this, arguments);
	}

});

StickyWin.ModalWM = new StickyWin.Stacker({
	zIndexBase: 11000
});
if (StickyWin.Fx) StickyWin.Fx.Modal = StickyWin.Modal;