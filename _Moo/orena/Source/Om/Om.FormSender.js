Om.attachFormSender = function(instanceId){
			var _getEl = function(){
				el = $(instanceId);
				if(!el) {
					_getEl.delay(50);
					return;
				}
				el.getElements('input[type=submit]').addEvent("click", function(e){
					 e.stop();
					 el.getElements('input[type=submit]').setProperty("disabled", true);

				 	el.getElements('textarea.fckeditor').each(function(_el){
						_el.value = FCKeditorAPI.GetInstance(_el.id). GetXHTML( 1 );
				  });

				 new Request.JSON({url:el.getAttribute('action'), onSuccess:function(response){
					if(response.status == 'SUCCEED') {
						if(response.refresh == 1) {
							window.location.reload(true);
						} else if(response.show) {
							el.getParent().set('html', response.show);
						} else if(response.redirect) {
							window.location.href = response.redirect;
						}
					} else {
						el.getElements('.form-row-error').dispose();
						for(field in response.errors) {
							erre = el.getElement('[name='+field+']').getParent();
							if(!erre) erre = el.getChildren().getLast();
							err = new Element('div', {class:'form-row-error'});
							err.set('html', response.errors[field]);
							err.inject(erre, 'after');
						}
						el.getElements('input[type=submit]').setProperty("disabled", false);
					}
			 	 }}).post(el);
			 });
		};
		_getEl();
	};