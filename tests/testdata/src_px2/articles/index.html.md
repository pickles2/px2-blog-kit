これはブログ記事の一覧ページです。

<?= $px->blog->mk_list_page(
	array(
		'blog_id'=> 'articles',
		'scheme'=>'https',
		'domain'=>'yourdomain.com',
		'title'=>'test list 1',
		'description'=>'TEST LIST',
		"orderby" => "update_date", // 並び替えに用いるサイトマップ項目のキー (v2.2.0 で追加)
		"scending" => "desc", // 昇順(asc)、または降順(desc)。デフォルトは `desc` です。 orderby と併せて指定します。 (v2.2.0 で追加)
		'dpp'=>10,
		'lang'=>'ja',
		'url_home'=>'https://yourdomain.com/',
		'url_index'=>'https://yourdomain.com/listsample/',
		'author'=>'Tomoya Koyanagi',
		'rss'=>array(
			'atom-1.0'=>'/rss/atom0100.xml',
			'rss-1.0'=>'/rss/rss0100.rdf',
			'rss-2.0'=>'/rss/rss0200.xml',
		),
	)
); ?>
