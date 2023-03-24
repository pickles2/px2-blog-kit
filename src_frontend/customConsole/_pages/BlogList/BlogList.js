/**
 * BlogList.js
 */
module.exports = function(state, cceAgent, options){
	const $ = require('jquery');
	const $elm = $(cceAgent.elm());

	this.draw = function(){
		const blogList = state.getState('blogList');

		let html = '';
		html += '<table class="px2-table">';
		blogList.forEach(function(row){
			html += '<tr>';
			html += '<td>'+row.blog_id+'</td>';
			html += '<td><button type="button" class="px2-btn" data-blog-id="'+row.blog_id+'">詳細</button></td>';
			html += '</tr>';
		});
		html += '</table>';
		html += '<div>';
		html += '	<p><button type="button" class="px2-btn" data-btn-create-new-blog>新規ブログを追加</button></p>';
		html += '</div>';
		$elm.html(html);

		$elm.find('[data-blog-id]')
			.on('click', function(){
				const blog_id = $(this).attr('data-blog-id');
				state.setState({
					"page": "ArticleList",
					"blogId": blog_id,
					"articleList": [],
				});
			});

		$elm.find('[data-btn-create-new-blog]')
			.on('click', function(){
				const template = require('./templates/createNewBlog.twig');
				const $body = $(template({}));
				px2style.modal({
					"title": "新規ブログを追加する",
					"body": $body,
					"buttons": [
						$('<button type="submit" class="px2-btn px2-btn--primary">').text('作成する'),
					],
					"form": {
						"submit": function(e){
							const $form = $(this);
							const newBlogId = $form.find(`[name=blog_id]`).val();
							if( !newBlogId ){
								alert('ブログIDを入力してください。');
								return false;
							}

							options.onCreateNewBlog(
								{
									blog_id: newBlogId,
								},
								function(result){
									if( !result ){
										alert('エラー');
										return;
									}
									px2style.closeModal();
								}
							);
						},
					},
				});
			});
	}
};
