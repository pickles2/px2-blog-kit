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
					"articleList": {
					},
				};
				newState.articleList[blogId] = res.article_list;
				state.setState(newState);
			});
			return;
		}

		let html = '';
		html += `<p>記事一覧: ${blogId}</p>`;
		html += `<table class="px2-table">`;
		articleList[blogId].forEach(function(row){
			html += `<tr>`;
			html += `<td>${row.title}</td>`;
			html += `<td><button type="button" class="px2-btn" data-btn-article="${row.path}">詳細</button></td>`;
			html += `<td><button type="button" class="px2-btn" data-btn-edit-content="${row.path}">記事編集</button></td>`;
			html += `</tr>`;
		});
		html += `</table>`;
		html += `<p class="px2-text-align-right"><button type="button" class="px2-btn px2-btn--danger" data-delete-blog="${blogId}">ブログ ${blogId} を削除する</button></p>`;
		html += `<p><button type="button" class="px2-btn" data-back>戻る</button></p>`;
		$elm.html(html);

		$elm.find('[data-btn-article]').on('click', function(){
			const path = $(this).attr('data-btn-article');
			let newState = {
				"page": "Article",
			};
			articleList[blogId].forEach(function(row){
				if( row.path == path ){
					newState.articleInfo = row;
					return;
				}
			});
			state.setState(newState);
		});
		$elm.find('[data-btn-edit-content]').on('click', function(){
			const path = $(this).attr('data-btn-edit-content');
			cceAgent.editContent(path);
		});
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
		$elm.find('[data-back]').on('click', function(){
			const blog_id = $(this).attr('data-blog-id');
			state.setState({
				"page": null,
				"blogId": null,
			});
		});
	}
};
