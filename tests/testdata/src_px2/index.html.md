px2-blog-kit の開発画面です。

<?= $px->blog->mk_list_page(
	array(
		'blog_id'=> 'articles',
		'scheme'=>'https',
		'domain'=>'yourdomain.com',
		'title'=>'test list 1',
		'description'=>'TEST LIST',
		'list_page_id' => '/articles/{*}', // ページネーションのリンク先をカレントページ以外のリストにしたい場合に指定する (省略可)
		"orderby" => "update_date", // 並び替えに用いるサイトマップ項目のキー (v2.2.0 で追加)
		"scending" => "desc", // 昇順(asc)、または降順(desc)。デフォルトは `desc` です。 orderby と併せて指定します。 (v2.2.0 で追加)
		'dpp'=>10,
		'lang'=>'ja',
		'url_home'=>'https://yourdomain.com/',
		'url_index'=>'https://yourdomain.com/listsample/',
		'author'=>'Tomoya Koyanagi',
	)
); ?>
