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
	const view = new (require('./_modules/View.js'))(
		state,
		cceAgent,
		{
			"onCreateNewBlog": function( params, callback ){
				// --------------------------------------
				// 新規ブログを作成する
				let newState = {};
				cceAgent.gpi({
					'command': 'createNewBlog',
					'params': params,
				}, function(res){
					if( !res.result ){
						callback(res);
						return;
					}

					cceAgent.gpi({
						'command': 'getBlogList'
					}, function(res){
						newState.blogList = res.blog_list;
						state.setState(newState);

						callback(res);
					});
				});
			},
			"onCreateNewArticle": function(params, callback){
				// --------------------------------------
				// 新規記事を作成する
				// TODO: 開発する
				let newState = {};
				cceAgent.gpi({
					'command': 'createNewArticle',
					'params': params,
				}, function(res){
					if( !res.result ){
						callback(res);
						return;
					}

					cceAgent.gpi({
						'command': 'getArticleList',
						'blog_id': blogId,
					}, function(res){
						let newState = {
							"articleList": {},
						};
						newState.articleList[blogId] = res.article_list;
						state.setState(newState);
						callback(res);
					});
				});
			},
			"onDeleteBlog": function( params, callback ){
				// --------------------------------------
				// ブログを削除する
				let newState = {};
				cceAgent.gpi({
					'command': 'deleteBlog',
					'params': params,
				}, function(res){
					console.info('result:', res);
					if( !res.result ){
						callback(res);
						return;
					}

					cceAgent.gpi({
						'command': 'getBlogList'
					}, function(res){
						newState.page = '';
						newState.blogList = res.blog_list;
						state.setState(newState);

						callback(res);
					});
				});
			},
		}
	);

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
