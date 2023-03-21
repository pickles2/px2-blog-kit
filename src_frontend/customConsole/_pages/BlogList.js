/**
 * BlogList.js
 */
module.exports = function(state, cceAgent){
	const $ = require('jquery');
	const $elm = $(cceAgent.elm());

	this.draw = function(){
		const blogList = state.getState('blogList');

		let html = '';
		html += '<ul>';
		blogList.forEach(function(row){
			html += '<li><a href="javascript:;" data-blog-id="'+row.blog_id+'">'+row.blog_id+'</a></li>';
		});
		html += '</ul>';
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
	}
};
