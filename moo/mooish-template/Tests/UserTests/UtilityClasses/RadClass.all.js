{
	tests: [
		{
			title	 : "RadClass",
			verify : "did the text change after 3 seconds?",
			before : function(){
				(function(){
					new RadClass($('test'))
				}).delay(3000)
			}
		}
	]
}