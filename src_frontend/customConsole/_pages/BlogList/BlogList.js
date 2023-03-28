/**
 * BlogList.js
 */
module.exports = function(state, cceAgent, options){
	const $ = require('jquery');
	const utils = new (require('../../_modules/Utils.js'))();
	const $elm = $(cceAgent.elm());

	this.draw = function(){
		const blogList = state.getState('blogList');

		const template = require('./templates/main.twig');
		let html = template({
			blog_list: blogList,
		});
		$elm.html(html);


		// --------------------------------------
		// Events

		// 記事一覧へ
		$elm.find('[data-btn-article-list]')
			.on('click', function(){
				const blog_id = $(this).attr('data-btn-article-list');
				state.setState({
					"page": "ArticleList",
					"blogId": blog_id,
					"articleList": [],
				});
			});

		// 新規ブログ作成
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

							options.onCreateNewBlog(
								{
									blog_id: newBlogId,
								},
								function(result){
									if( !result.result ){
										alert('ERROR: '+result.message);
										form.reportValidationError({
											errors: result.errors,
										});
										return;
									}
									px2style.closeModal();
								}
							);
						},
					},
				});
				var form = px2style.form($body);
			});
	}
};
