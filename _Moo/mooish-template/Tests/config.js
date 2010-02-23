var UnitTester = {
	site: 'Mooish Sample',
	title: 'Unit Tests',
	path: 'UnitTester/',
	ready: function(){
		var sources = {
			mootoolsCore : '../../mootools-core',
			Sample			 : '..'
		};
		new UnitTester(sources, {
			Sample : 'UserTests'
		}, {
			autoplay: true
		});
	}
};