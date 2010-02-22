{
	tests: [
		{
			title: "Element.hide",
			description: "Switches an element from display:block|inline|etc to display:none",
			verify: "Did the element disappear?",
			before: function(){$('foo').setStyle('display','block');},
			post: function(){return $('foo').getStyle('display') == 'none';},
			before: function(){
				$('foo').hide();
			}
		},
		{
			title: "Element.show",
			description: "Switches an element from display:none to display:block",
			verify: "Did the element appear?",
			before: function(){
				$('foo').setStyle('display','none');
				$('foo').show();
			},
			post: function(){return $('foo').getStyle('display') == 'block';}
		},
		{
			title: "Element.show(type)",
			description: "Switches an element from display:none to a different, specified display.",
			verify: "Did the element appear inline?",
			before: function(){
				$('foo').hide();
				$('foo').show('inline');
			},
			post: function(){return $('foo').getStyle('display') == 'inline';}
		},
		{
			title: "Element.toggle",
			description: "Toggles between display:none and block.",
			verify: "Did the element switch between hidden and visible?",
			before: function(){
				$('foo').show();
				$('foo').toggle();
				$('foo').toggle.delay(500, $('foo'));
				$('foo').toggle.delay(1000, $('foo'));
				$('foo').toggle.delay(1500, $('foo'));
			}
		},
		{
			title: "Element.isDisplayed",
			description: "Returns true if the element's display is not = none.",
			before: function(){
				$('foo').show();
				$('foo').visTest = false;
				if ($('foo').isDisplayed()){
					dbug.log('show successful; foo is visible');
					$('foo').visTest = true;
				}	else {
					dbug.log('either show or isDisplayed failed');
					$('foo').visTest = false;
				}
				$('foo').hide();
				if (!$('foo').isDisplayed()){
					dbug.log('hide successful; foo is not visible');
				} else {
					dbug.log('either hide or isDisplayed failed');
					$('foo').visTest = false;
				}
			},
			post: function(){return $('foo').visTest;}
		},
		{
			title: 'Element.swapClass',
			description: "Changes the text from blue to black.",
			before: function(){$('foo').show(); $('foo').swapClass('blackText', 'blueText');},
			post: function(){
				return $('foo').hasClass('blueText') && !$('foo').hasClass('blackText');
			}
		}

	]
}
