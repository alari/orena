/***
 * MooRainbow
 *
 * @version		0.0.1
 * @license		Apache
 * @author		Boris Tsirkin - < bgdotmail [at] gmail.com >
 * @copyright	Author
 *
 */

Om.MooColor = new Class({
        options: {
		id: 'mooColor',
		prefix: 'mooc-',
		imgPath: '/Moo/orena/Assets/MooColor/',
		startColor: [100, 30, 65],
		wheel: false,
		onComplete: $empty,
		onChange: $empty
	},
	Implements: [Events, Options, Class.Binds],
	Binds: ['evtClick','evtBlur','evtKeypress','evtChange'],
	el: null,
	isFocused: false,
	initialize: function(el, options){
            this.element = $(el);
            if (!this.element)
                return;

            if(!options.imgPath)
                options.imgPath = Om.root + this.options.imgPath;

            this.el = $(el);
            this.setOptions(options);

            Asset.css(this.options.imgPath + "styles.css");
            this.sliderPos = 0;
            this.pickerPos = {x: 0, y: 0};
            this.backupColor = this.options.startColor;
            this.currentColor = this.options.startColor;
            this.sets = {
                    rgb: [],
                    hsb: [],
                    hex: []
            };
            this.pickerClick = this.sliderClick  = false;
            if (!this.layout)
                this.doLayout();

            this.element.addEvent('click', function(e) {
                this.toggle(e);
            }.bind(this));

           
        },
        toggle: function() {
		this[this.visible ? 'hide' : 'show']();
	},

	show: function() {
		//this.rePosition();
		this.layout.setStyle('display', 'block');
		this.visible = true;
	},

	hide: function() {
		this.layout.setStyles({'display': 'none'});
		this.visible = false;
	},
        closeAll: function(){

        },

        doLayout: function () {
            var id = this.options.id, prefix = this.options.prefix;
            var idPrefix = id + ' .' + prefix;
            alert ('rere'+ id);
            console.debug(id);
            
            this.layout = new Element('div', {
                    'styles': {'display': 'block', 'position': 'absolute', 'background-color': 'aquamarine'},
                    'id': id
            }).inject(document.body);
            var box = new Element('div', {
                    'styles':  {'position': 'relative'},
                    'class': prefix + 'box'
            }).inject(this.layout);

            var div = new Element('div', {
                    'styles': {'position': 'absolute', 'overflow': 'hidden', 'width': 256, 'height': 256},
                    'class': prefix + 'overlayBox'
            }).inject(box);

            var ar = new Element('div', {
                    'styles': {'position': 'absolute', 'zIndex': 1},
                    'class': prefix + 'arrows'
            }).inject(box);
            ar.width = ar.getStyle('width').toInt();
            ar.height = ar.getStyle('height').toInt();

            var ov = new Element('img', {
                    'styles': {'background-color': '#faf00f', 'position': 'relative', 'zIndex': 2},
                    'src': this.options.imgPath + 'moor_woverlay.png',
                    'class': prefix + 'overlay'
            }).inject(div);

            var ov2 = new Element('img', {
                    'styles': {'position': 'absolute', 'top': 0, 'left': 0, 'zIndex': 2},
                    'src': this.options.imgPath + 'moor_boverlay.png',
                    'class': prefix + 'overlay'
            }).inject(div);

            if ( "trident" == Browser.Engine.name ) {
                    div.setStyle('overflow', '');
                    var src = ov.src;
                    ov.src = this.options.imgPath + 'blank.gif';
                    ov.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + src + "', sizingMethod='scale')";
                    src = ov2.src;
                    ov2.src = this.options.imgPath + 'blank.gif';
                    ov2.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + src + "', sizingMethod='scale')";
            }
            ov.width = ov2.width = div.getStyle('width').toInt();
            ov.height = ov2.height = div.getStyle('height').toInt();

            var cr = new Element('div', {
                    'styles': {'overflow': 'hidden', 'position': 'absolute', 'zIndex': 2},
                    'class': prefix + 'cursor'
            }).inject(div);
            cr.width = cr.getStyle('width').toInt();
            cr.height = cr.getStyle('height').toInt();

            var sl = new Element('img', {
                    'styles': {'position': 'absolute', 'z-index': 2},
                    'src': this.options.imgPath + 'moor_slider.png',
                    'class': prefix + 'slider'
            }).inject(box);
            this.layout.slider = document.getElement('#' + idPrefix + 'slider');
            sl.width = sl.getStyle('width').toInt();
            sl.height = sl.getStyle('height').toInt();

            new Element('div', {
                    'styles': {'position': 'absolute'},
                    'class': prefix + 'colorBox'
            }).inject(box);

            new Element('div', {
                    'styles': {'zIndex': 2, 'position': 'absolute'},
                    'class': prefix + 'chooseColor'
            }).inject(box);

            this.layout.backup = new Element('div', {
                    'styles': {'zIndex': 2, 'position': 'absolute', 'cursor': 'pointer'},
                    'class': prefix + 'currentColor'
            }).inject(box);

        }

});

Om.MooColor.implement(new Options);
Om.MooColor.implement(new Events);
