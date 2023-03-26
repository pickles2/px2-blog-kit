/**
 * ArticleList.js
 */
module.exports = function(state, cceAgent, options){
	const $ = require('jquery');
	const it79 = require('iterate79');
	const $elm = $(cceAgent.elm());

	this.draw = function(){
		const blogId = state.getState('blogId');
		const articleList = state.getState('articleList');

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

		let html = '';
		html += `<p>記事一覧: ${blogId}</p>`;
		html += `<div>`;
		html += `	<p><button type="button" class="px2-btn px2-btn--primary" data-btn-create-new-article="${blogId}">新規記事を追加</button></p>`;
		html += `</div>`;
		html += `<div class="px2-p">`;
		html += `<table class="px2-table" style="width: 100%;">`;
		articleList[blogId].forEach(function(row){
			html += `<tr>`;
			html += `<td>${row.title}</td>`;
			html += `<td>${row.update_date}</td>`;
			html += `<td style="text-align: center;">`;
			html += `<button type="button" class="px2-btn" data-btn-edit-content="${row.path}">コンテンツ編集</button>`;
			html += `<button type="button" class="px2-btn px2-btn--primary" data-btn-article="${row.path}">詳細</button>`;
			html += `</td>`;
			html += `</tr>`;
		});
		html += `</table>`;
		html += `</div>`;
		html += `<p class="px2-text-align-right"><button type="button" class="px2-btn px2-btn--danger" data-delete-blog="${blogId}">ブログ ${blogId} を削除する</button></p>`;
		html += `<p><button type="button" class="px2-btn" data-back>戻る</button></p>`;
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

		// コンテンツ編集へ
		$elm.find('[data-btn-edit-content]').on('click', function(){
			const path = $(this).attr('data-btn-edit-content');
			cceAgent.editContent(path);
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
