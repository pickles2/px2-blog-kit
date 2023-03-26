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
		const articlePath = state.getState('articlePath');
		const articleList = state.getState('articleList');
		const articleInfo = state.getState('articleInfo');
		const sitemapDefinition = state.getState('sitemapDefinition');

		if( !articlePath ){
			state.setState({
				"page": "ArticleList",
			});
			return;
		}
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

		if( !articleInfo ){
			let newState = {};
			articleList[blogId].forEach(function(row){
				if( row.path == articlePath ){
					newState.articleInfo = row;
					return;
				}
			});
			state.setState(newState);
			return;
		}

		const template = require('./templates/main.twig');
		let html = template({
			blog_id: blogId,
			article_info: articleInfo,
		});
		$elm.html(html);



		// --------------------------------------
		// Events

		// 記事編集へ
		$elm.find('[data-btn-edit-article]').on('click', function(){
			const path = $(this).attr('data-btn-edit-article');
			const blog_id = blogId;
			const template = require('./templates/editArticle.twig');
			let blogmapDefinition;
			let article_info;
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
					cceAgent.gpi({
						'command': 'getArticleInfo',
						'path': path,
					}, function(res){
						article_info = res.article_info;
						it.next();
					});
				},
				function(it){
					blogmapDefinition = utils.fixSitemapDefinition(blogmapDefinition, sitemapDefinition);
					const $body = $(template({
						blog_id: blog_id,
						blogmapDefinition: blogmapDefinition,
						article_info: article_info,
					}));
					px2style.modal({
						"title": "記事を編集する",
						"body": $body,
						"buttons": [
							$('<button type="submit" class="px2-btn px2-btn--primary">').text('保存する'),
						],
						"buttonsSecondary": [
							$('<button type="button" class="px2-btn px2-btn--secondary">').text('キャンセル')
								.on('click', function(){ px2style.closeModal(); }),
						],
						"form": {
							"submit": function(e){
								const $form = $(this);
								let fields = {};
								for( idx in blogmapDefinition ){
									const blogmapDefinitionRow = blogmapDefinition[idx];
									fields[blogmapDefinitionRow.key] = $form.find(`[name=${blogmapDefinitionRow.key}]`).val();
								}
								options.onUpdateArticle(
									{
										blog_id: blog_id,
										path: article_info.path,
										fields: fields,
									},
									function(result){
										if( !result.result ){
											alert('ERROR: '+result.message);
											$body.find('.px2-form-input-list__li').removeClass(`px2-form-input-list__li--error`);
											$body.find('.px2-error').remove();
											Object.keys(result.errors).forEach(function(key){
												const $input = $form.find(`[name=${key}]`);
												$input.closest(`.px2-form-input-list__li`).addClass(`px2-form-input-list__li--error`);
												$input.before(`<p class="px2-error">${result.errors[key]}</p>`);
											});
											return;
										}
										px2style.closeModal();
									}
								);
							},
						},
					});
					$body.find('input').on('change', function(){ const $li = $(this).closest(`.px2-form-input-list__li`); $li.removeClass(`px2-form-input-list__li--error`); $li.find('.px2-error').remove(); });
					it.next();
				},
			]);
		});

		// コンテンツ編集へ
		$elm.find('[data-btn-edit-content]').on('click', function(){
			const path = $(this).attr('data-btn-edit-content');
			cceAgent.editContent(path);
		});

		// プレビューへ
		$elm.find('[data-btn-preview]').on('click', function(){
			const path = $(this).attr('data-btn-preview');
			cceAgent.openInBrowser(path);
		});

		// 記事削除
		$elm.find('[data-delete-article]').on('click', function(){
			const blog_id = blogId;
			const template = require('./templates/deleteArticle.twig');
			const $body = $(template({
				blog_id: blog_id,
				path: articleInfo.path,
			}));
			px2style.modal({
				"title": "記事を削除する",
				"body": $body,
				"buttons": [
					$('<button type="submit" class="px2-btn px2-btn--danger">').text('削除する'),
				],
				"form": {
					"submit": function(e){
						options.onDeleteArticle(
							{
								blog_id: blog_id,
								path: articleInfo.path,
							},
							function(res){
								if( !res.result ){
									alert('ERROR: '+res.message);
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
			state.setState({
				"page": 'ArticleList',
				"articlePath": null,
				"articleList": {},
				"articleInfo": null,
			});
		});

	}
};
