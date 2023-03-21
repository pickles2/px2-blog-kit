window.pickles2BlogKitCustomConsoleExtension = function(cceAgent){
	const it79 = require('iterate79');
	const $ = require('jquery');
	let initialState = {
		"page": null,
		"blogId": null,
		"blogList": [],
		"articleList": {},
	};

	const state = new (require('./_modules/State.js'))({
		onSetState: function(){
			view.refresh();
		},
		initialState: initialState,
	});
	const view = new (require('./_modules/View.js'))(state, cceAgent);

	it79.fnc({}, [
		function(it){
			cceAgent.gpi({
				'command': 'getBlogList'
			}, function(res){
				initialState.blogList = res.blog_list;
				it.next();
			});
		},
		function(it){
			it.next();
		},
		function(){
			state.setState(initialState);
		},
	]);
}
