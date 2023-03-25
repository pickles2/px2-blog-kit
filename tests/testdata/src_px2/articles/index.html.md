これはブログ記事の一覧ページです。

<?= $px->blog->mk_list_page(
	array(
		'blog_id'=> 'articles',
		'list_page_id'=>null,
		'dpp'=>10,
		'index_size'=>5,
		"orderby" => "update_date",
		"scending" => "desc",
		'template' => null,
	)
); ?>
<?php
$px->blog->generate_feeds(
	array(
		'blog_id'=> 'articles',
		"orderby" => "update_date",
		"scending" => "desc",
		'dpp'=>10,
		'lang'=>'ja',
		'scheme'=>'https',
		'domain'=>'yourdomain.com',
		'title'=>'test list 1',
		'description'=>'TEST LIST',
		'url_home'=>'https://yourdomain.com/',
		'url_index'=>'https://yourdomain.com/listsample/',
		'author'=>'Tomoya Koyanagi',
		'dist'=>array(
			'atom-1.0'=>'/rss/atom0100.xml',
			'rss-1.0'=>'/rss/rss0100.rdf',
			'rss-2.0'=>'/rss/rss0200.xml',
		),
	)
); ?>
