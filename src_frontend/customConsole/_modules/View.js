/**
 * View.js
 */
module.exports = function(state, cceAgent){
	const blogList = new (require('../_pages/BlogList.js'))(state, cceAgent);
	const articleList = new (require('../_pages/ArticleList.js'))(state, cceAgent);
	const article = new (require('../_pages/Article.js'))(state, cceAgent);

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
