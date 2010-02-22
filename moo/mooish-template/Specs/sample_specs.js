Screw.Unit(function() {
  describe('UtilityClasses', function() {
    before(function(){
			hella_tight = $("hella-tight").clone(true).inject(test_sandbox);
		});

		describe('RadClass', function(){
			it ('should change the html', function(){
				new RadClass(hella_tight);
				expect(hella_tight.get('html')).to(equal,'I am rad.');
			})	
		});
  });
});
