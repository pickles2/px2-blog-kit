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

		// PX=blogkit
		// pickles2/px2-blog-kit v0.2.0 で追加
		$px->pxcmd()->register('blogkit', function($px) use ($options){
			self::pxcmd_route($px, $options);
		});

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

	/**
	 * PX=blogkitをルーティング
	 *
	 * px2-clover の `PX=admin.api.blogkit.*` から移管したもの。
	 * pickles2/px2-blog-kit v0.2.0 で追加
	 *
	 * @param object $px Picklesオブジェクト
	 * @param object $options プラグイン設定
	 */
	private static function pxcmd_route($px, $options){
		$command = $px->get_px_command();
		$blog = $px->blog ?? null;

		switch( $command[1] ?? '' ){
			case 'api':
				$px->header('Content-type: application/json');
				if( !is_object( $blog ) ){
					echo json_encode(array(
						'result' => false,
						'message' => 'BlogKit is NOT loaded.',
					));
					exit;
				}

				// --------------------------------------
				// API
				switch( $command[2] ?? '' ){
					case 'get_blog_list':
						$blog_list = $blog->get_blog_list();
						echo json_encode(array(
							"result" => true,
							"blog_list" => $blog_list,
						));
						exit;
						break;
					case 'get_article_list':
						$blog_id = $px->req()->get_param('blog_id');
						$dpp = intval($px->req()->get_param('dpp') ?? 0);
						if( !$dpp ){
							$dpp = 50;
						}
						$p = intval($px->req()->get_param('p') ?? 0);
						if( !$p ){
							$p = 1;
						}
						$article_list = $blog->get_article_list($blog_id);
						$sliced_article_list = array_slice(
							$article_list,
							$dpp * ($p-1),
							$dpp
						);
						echo json_encode(array(
							"result" => true,
							"blog_id" => $blog_id,
							"count" => count($article_list),
							"dpp" => $dpp,
							"p" => $p,
							"article_list" => $sliced_article_list,
						));
						exit;
						break;
					case 'get_article_info':
						$path = $px->req()->get_param('path');
						$article_info = $blog->get_article_info($path);
						echo json_encode(array(
							"result" => true,
							"blog_id" => $article_info->blog_id ?? null,
							"article_info" => $article_info->article_info ?? null,
							"originated_csv" => $article_info->originated_csv ?? null,
						));
						exit;
						break;
					case 'get_blogmap_definition':
						$writer = new \pickles2\px2BlogKit\writer($px, $blog, $blog->get_options());
						$blog_id = $px->req()->get_param('blog_id');
						$blogmap_definition = $writer->get_blogmap_definition($blog_id);
						echo json_encode(array(
							"result" => true,
							"blogmap_definition" => $blogmap_definition,
						));
						exit;
						break;
					case 'get_sitemap_definition':
						$sitemap_definition = $px->site()->get_sitemap_definition();
						echo json_encode(array(
							"result" => true,
							"sitemap_definition" => $sitemap_definition,
						));
						exit;
						break;
					case 'create_new_blog':
						$this->clover->allowed_method('post');
						$writer = new \pickles2\px2BlogKit\writer($px, $blog, $blog->get_options());
						$blog_id = $px->req()->get_param('blog_id');
						$result = $writer->create_new_blog( $blog_id );
						echo json_encode(array(
							"result" => $result->result ?? null,
							"message" => $result->message ?? null,
							"errors" => $result->errors ?? null,
						));
						exit;
						break;
					case 'delete_blog':
						$this->clover->allowed_method('post');
						$writer = new \pickles2\px2BlogKit\writer($px, $blog, $blog->get_options());
						$blog_id = $px->req()->get_param('blog_id');
						$result = $writer->delete_blog( $blog_id );
						echo json_encode(array(
							"result" => $result->result ?? null,
							"message" => $result->message ?? null,
							"errors" => $result->errors ?? null,
						));
						exit;
						break;
					case 'create_new_article':
						$this->clover->allowed_method('post');
						$writer = new \pickles2\px2BlogKit\writer($px, $blog, $blog->get_options());
						$blog_id = $px->req()->get_param('blog_id');
						$fields = json_decode($px->req()->get_param('fields'));
						$result = $writer->create_new_article($blog_id, $fields ?? null);
						echo json_encode(array(
							"result" => $result->result ?? null,
							"message" => $result->message ?? null,
							"errors" => $result->errors ?? null,
						));
						exit;
						break;
					case 'update_article':
						$this->clover->allowed_method('post');
						$writer = new \pickles2\px2BlogKit\writer($px, $blog, $blog->get_options());
						$blog_id = $px->req()->get_param('blog_id');
						$path = $px->req()->get_param('path');
						$fields = json_decode($px->req()->get_param('fields'));
						$result = $writer->update_article($blog_id, $path, $fields ?? null);
						echo json_encode(array(
							"result" => $result->result ?? null,
							"message" => $result->message ?? null,
							"errors" => $result->errors ?? null,
						));
						exit;
						break;
					case 'delete_article':
						$this->clover->allowed_method('post');
						$writer = new \pickles2\px2BlogKit\writer($px, $blog, $blog->get_options());
						$blog_id = $px->req()->get_param('blog_id');
						$path = $px->req()->get_param('path');
						$result = $writer->delete_article($blog_id, $path);
						echo json_encode(array(
							"result" => $result->result ?? null,
							"message" => $result->message ?? null,
							"errors" => $result->errors ?? null,
							"blog_id" => $request->blog_id ?? null,
							"path" => $request->path ?? null,
						));
						exit;
						break;
				}
				break;
		}
	}
}
