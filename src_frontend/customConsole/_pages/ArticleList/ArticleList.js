/**
 * ArticleList.js
 */
module.exports = function(state, cceAgent, options){
	const $ = require('jquery');
	const it79 = require('iterate79');
	const utils = new (require('../../_modules/Utils.js'))();
	const $elm = $(cceAgent.elm());

	this.draw = function(){
		const blogId = state.getState('blogId');
		const articleList = state.getState('articleList');
		const sitemapDefinition = state.getState('sitemapDefinition');

		if( !articleList || !articleList[blogId] ){
			cceAgent.gpi({
				'command': 'getArticleList',
				'blog_id': blogId,
			}, function(res){
				let newState = {
					"articleList": {},
				};
				newState.articleList[blogId] = res.article_list;
				state.setState(newState);
			});
			return;
		}

		const template = require('./templates/main.twig');
		let html = template({
			blog_id: blogId,
			article_list: articleList,
		});
		$elm.html(html);



		// --------------------------------------
		// Events

		// 記事詳細へ
		$elm.find('[data-btn-article]').on('click', function(){
			const path = $(this).attr('data-btn-article');
			let newState = {
				"page": "Article",
				"articlePath": path,
			};
			articleList[blogId].forEach(function(row){
				if( row.path == path ){
					newState.articleInfo = row;
					return;
				}
			});
			state.setState(newState);
		});

		// 新規記事作成
		$elm.find('[data-btn-create-new-article]').on('click', function(){
			const blog_id = $(this).attr('data-btn-create-new-article');
			const template = require('./templates/createNewArticle.twig');
			let blogmapDefinition;
			it79.fnc({}, [
				function(it){
					cceAgent.gpi({
						'command': 'getBlogmapDefinition',
						'blog_id': blogId,
					}, function(res){
						blogmapDefinition = res.blogmap_definition;
						it.next();
					});
				},
				function(it){
					blogmapDefinition = utils.fixSitemapDefinition(blogmapDefinition, sitemapDefinition);
					const $body = $(template({
						blog_id: blog_id,
						blogmapDefinition: blogmapDefinition,
					}));
					px2style.modal({
						"title": "記事を作成する",
						"body": $body,
						"buttons": [
							$('<button type="submit" class="px2-btn px2-btn--primary">').text('作成する'),
						],
						"form": {
							"submit": function(e){
								const $form = $(this);
								let fields = {};
								for( idx in blogmapDefinition ){
									const blogmapDefinitionRow = blogmapDefinition[idx];
									fields[blogmapDefinitionRow.key] = $form.find(`[name=${blogmapDefinitionRow.key}]`).val();
								}
								options.onCreateNewArticle(
									{
										blog_id: blog_id,
										fields: fields,
									},
									function(result){
										if( !result.result ){
											alert('ERROR: '+result.message);
											return;
										}
										px2style.closeModal();
									}
								);
							},
						},
					});
					it.next();
				},
			]);
		});

		// ブログ削除
		$elm.find('[data-delete-blog]').on('click', function(){
			const blog_id = $(this).attr('data-delete-blog');
			const template = require('./templates/deleteBlog.twig');
			const $body = $(template({
				blog_id: blog_id,
			}));
			px2style.modal({
				"title": "ブログを削除する",
				"body": $body,
				"buttons": [
					$('<button type="submit" class="px2-btn px2-btn--danger">').text('削除する'),
				],
				"form": {
					"submit": function(e){
						const $form = $(this);
						options.onDeleteBlog(
							{
								blog_id: blog_id,
							},
							function(result){
								if( !result.result ){
									alert('ERROR: '+result.message);
									return;
								}
								px2style.closeModal();
							}
						);
					},
				},
			});
		});

		// 戻る
		$elm.find('[data-back]').on('click', function(){
			const blog_id = $(this).attr('data-blog-id');
			state.setState({
				"page": null,
				"blogId": null,
			});
		});
	}
};
