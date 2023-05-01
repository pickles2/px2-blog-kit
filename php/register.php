<?php
namespace pickles2\px2BlogKit;
class register {

	/**
	 * ブログ管理を登録する
	 * @param object $px Picklesオブジェクト
	 * @param object $options プラグイン設定
	 */
	public static function blog( $px = null, $options = null ){

		if( count(func_get_args()) <= 1 ){
			return __CLASS__.'::'.__FUNCTION__.'('.( is_array($px) ? json_encode($px) : '' ).')';
		}

		$request_file_path = $px->req()->get_request_file_path();
		if( !preg_match('/\.html?$/i', $request_file_path) ){
			// HTML以外のコンテンツでは実行しない
			return;
		}
 
		$px->blog = new blog($px, $options);
		return;
	}

	/**
	 * 管理画面拡張を登録する
	 * @param object $options プラグイン設定
	 */
	static public function consoleExtension( $options = null ){
		return 'pickles2\px2BlogKit\consoleExtension('.( json_encode($options) ).')';
	}


	/**
	 * RSSファイルを出力する
	 * @param object $px Picklesオブジェクト
	 * @param object $options プラグイン設定
	 */
	public static function feeds( $px = null, $options = null ){

		if( count(func_get_args()) <= 1 ){
			return __CLASS__.'::'.__FUNCTION__.'('.( is_array($px) ? json_encode($px) : '' ).')';
		}

		$blog = $px->blog;
		if( !$blog || !is_callable( array($blog, 'generate_feeds') ) ){
			// $px->blog が初期化されていなければスキップ
			return;
		}

		$path_trigger = $px->href($options->path_trigger ?? '/');
		$path_current = $px->href($px->req()->get_request_file_path());
		if( $path_trigger !== $path_current ){
			// trigger指定以外のパスではスキップ
			return;
		}

		// --------------------------------------
		// フィードの出力を実行する
		$px->blog->generate_feeds(
			array(
				'blog_id'=> $options->blog_id ?? null,
				"orderby" => $options->orderby ?? null,
				"scending" => $options->scending ?? null,
				'dpp' => $options->dpp ?? null,
				'lang' => $options->lang ?? null,
				'scheme' => $options->scheme ?? null,
				'domain' => $options->domain ?? null,
				'title' => $options->title ?? null,
				'description' => $options->description ?? null,
				'url_home' => $options->url_home ?? null,
				'url_index' => $options->url_index ?? null,
				'author' => $options->author ?? null,
				'dist' => array(
					'atom-1.0' => $options->dist->{'atom-1.0'} ?? null,
					'rss-1.0' => $options->dist->{'rss-1.0'} ?? null,
					'rss-2.0' => $options->dist->{'rss-2.0'} ?? null,
				),
			)
		);

	}
}
