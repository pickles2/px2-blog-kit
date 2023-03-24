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
		const articleInfo = state.getState('articleInfo');

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

		if( !articleInfo ){
			alert('Error');
			state.setState({
				"page": 'articleList',
				"articlePath": null,
			});
			return;
		}

		let html = '';
		html += '<p>記事詳細: '+blogId+' '+articleInfo.path+'</p>';
		html += '<p><button type="button" class="px2-btn" data-btn-edit-content="'+articleInfo.path+'">記事編集</button></p>';
		html += '<table class="px2-table">';
		Object.keys(articleInfo).forEach(function(key){
			html += '<tr>';
			html += '<th>'+key+'</th>';
			html += '<td>'+articleInfo[key]+'</td>';
			html += '</tr>';
		});
		html += '</table>';
		html += '<p><button type="button" data-back class="px2-btn">戻る</button></p>';
		$elm.html(html);

		$elm.find('[data-btn-edit-content]').on('click', function(){
			const path = $(this).attr('data-btn-edit-content');
			cceAgent.editContent(path);
		});
		$elm.find('[data-back]').on('click', function(){
			state.setState({
				"page": 'ArticleList',
				"articlePath": null,
			});
		});
	}
};
