/**
 * View.js
 */
module.exports = function(state, cceAgent, options){
	const blogList = new (require('../_pages/BlogList/BlogList.js'))(state, cceAgent, options);
	const articleList = new (require('../_pages/ArticleList/ArticleList.js'))(state, cceAgent, options);
	const article = new (require('../_pages/Article/Article.js'))(state, cceAgent, options);

	this.refresh = function(){
		const page = state.getState('page');
		if( page == 'ArticleList' ){
			articleList.draw();
		}else if( page == 'Article' ){
			article.draw();
		}else{
			blogList.draw();
		}
	}
};
