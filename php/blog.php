<?php
namespace pickles2\px2BlogKit;
class blog {

	private $px;
	private $options;
	private $blogmap_array = array();
	private $article_list = array();

	/**
	 * コンストラクタ
	 * @param object $px PxFWコアオブジェクト
	 * @param array $options オプション
	 */
	public function __construct($px, $options){
		$this->px = $px;
		$this->options = (object) $options;

		// リクエストされたパスがサイトマップに定義されていなかったら、
		// ブログ記事である可能性があるため、ブログマップをロードしておく。
		// (`set_page_info()` でカレントページを登録するため)
		$request_file_path = $this->px->req()->get_request_file_path();
		$current_page_info = $this->px->site()->get_current_page_info();
		if( $request_file_path != ($current_page_info['path'] ?? null) ){
			$this->load_blog_page_list();
		}
	}

	/**
	 * ブログページを読み込む
	 */
	public function load_blog_page_list(){
		static $is_loaded = false;
		if( $is_loaded ){
			return true;
		}
		$is_loaded = true;

		$realpath_homedir = $this->px->get_realpath_homedir();
		$realpath_blogmap_basedir = $realpath_homedir.'blogs/';
		$realpath_blogmap_cache_dir = $realpath_homedir.'_sys/ram/caches/blogs/';
		$realpath_sitemap_cache_dir = $this->px->get_realpath_homedir().'_sys/ram/caches/sitemaps/';

		$csv_file_list = $this->px->fs()->ls($realpath_blogmap_basedir);
		$this->blogmap_array = array();
		$this->article_list = array();

		foreach($csv_file_list as $csv_filename){
			$blog_id = preg_replace('/\..*$/i', '', $csv_filename);
			$blog_options = ($this->options->blogs->{$blog_id} ?? (object) array());

			$realpath_blog_csv = $realpath_blogmap_basedir.$blog_id.'.csv';
			clearstatcache();
			if( !is_file($realpath_blog_csv) ){
				// CSVファイルは存在しない
				continue;
			}

			$this->blogmap_array[$blog_id] = array();
			$this->article_list[$blog_id] = array();
			$blogmap_page_originated_csv = array();

			$i = 0;
			clearstatcache();
			while( @is_file( $realpath_sitemap_cache_dir.'making_sitemap_cache.lock.txt' ) ){
				if( @filemtime( $realpath_sitemap_cache_dir.'making_sitemap_cache.lock.txt' ) < time()-(60*60) ){
					// 60分以上更新された形跡がなければ、
					// ロックを解除して再生成を試みる。
					$this->px->fs()->rm( $realpath_sitemap_cache_dir.'making_sitemap_cache.lock.txt' );
					break;
				}

				$i ++;
				if( $i > 60 ){
					// 他のプロセスがサイトマップキャッシュを作成中。
					$this->px->error('Sitemap cache generating is now in progress. This page has been incompletely generated.');

					//  古いブログマップキャッシュが存在する場合、ロードする。
					$this->blogmap_array[$blog_id] = ( $this->px->fs()->is_file($realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/blogmap.array') ? @include($realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/blogmap.array') : array() );
					$this->article_list[$blog_id] = ( $this->px->fs()->is_file($realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/article_list.array') ? @include($realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/article_list.array'): array() );

					clearstatcache();
					continue 2;
				}
				sleep(1);
				clearstatcache();
			}

			clearstatcache();

			if( $this->is_blogmap_cache( $blog_id ) ){
				// キャッシュが有効
				$this->blogmap_array[$blog_id] = include($realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/blogmap.array');
				$this->article_list[$blog_id] = include($realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/article_list.array');
				continue;
			}

			// サイトマップキャッシュ作成中のアプリケーションロックファイルを作成
			$lockfile_src = '';
			$lockfile_src .= 'ProcessID='.getmypid()."\r\n";
			$lockfile_src .= @date( 'Y-m-d H:i:s' , time() )."\r\n";
			$this->px->fs()->save_file( $realpath_sitemap_cache_dir.'making_sitemap_cache.lock.txt' , $lockfile_src );
			unset( $lockfile_src );


			$blog_page_list_csv = $this->px->fs()->read_csv($realpath_blog_csv);
			$tmp_blogmap_definition = array();
			foreach ($blog_page_list_csv as $row_number=>$row) {
				set_time_limit(30); // タイマー延命

				$tmp_array = array();
				if( preg_match( '/^(?:\*)/is' , $row[0] ) ){
					if( $row_number > 0 ){
						// アスタリスク始まりの場合はコメント行とみなす。
						continue;
					}
					// アスタリスク始まりでも、0行目の場合は、定義行とみなす。
					// 定義行とみなす条件: 0行目で、かつA列の値がアスタリスク始まりであること。
					// ※アスタリスクで始まらない列は定義行として認めず、無視し、スキップする。
					$is_definition_row = false;
					foreach($row as $cell_value){
						if( preg_match( '/^(?:\*)/is' , $cell_value ) ){
							$is_definition_row = true;
							break;
						}
					}
					if( !$is_definition_row ){
						continue;
					}
					$tmp_blogmap_definition = array();
					$tmp_col_id = 'A';
					foreach($row as $tmp_col_number=>$cell_value){
						$col_name = trim( preg_replace('/^\*/si', '', $cell_value) );
						if( $col_name == $cell_value ){
							// アスタリスクで始まらない列は定義行として認めず、無視する。
							$tmp_col_id++;
							continue;
						}
						$tmp_blogmap_definition[$col_name] = array(
							'num'=>$tmp_col_number,
							'col'=>$tmp_col_id++,
							'key'=>$col_name,
							'name'=>$col_name,
						);
					}
					unset($is_definition_row);
					unset($cell_value);
					unset($col_name);
					continue;
				}

				foreach ($tmp_blogmap_definition as $defrow) {
					$tmp_array[$defrow['key']] = $row[$defrow['num']] ?? null;
				}

				// 前後の空白文字を削除する
				foreach(array('title', 'filename', 'release_date', 'update_date', 'article_summary', 'article_keywords') as $tmpDefKey){
					if( array_key_exists($tmpDefKey, $tmp_array) && is_string($tmp_array[$tmpDefKey]) ){
						$tmp_array[$tmpDefKey] = trim($tmp_array[$tmpDefKey]);
					}
				}

				// --------------------------------------
				// ブログマップ項目を補完する
				$tmp_array['path'] = $tmp_array['path'] ?? '';
				if( preg_match('/\/$/', $tmp_array['path']) ){
					$tmp_array['path'] .= $this->px->conf()->directory_index[0] ?? 'index.html';
				}
				$tmp_array['content'] = $tmp_array['content'] ?? $tmp_array['path'];
				$tmp_array['logical_path'] = $blog_options->logical_path ?? '';
				$tmp_array['list_flg'] = 0;
				$tmp_array['category_top_flg'] = 0;

				$this->blogmap_array[$blog_id][$tmp_array['path']] = $tmp_array;
				array_push($this->article_list[$blog_id], $tmp_array);

				$blogmap_page_originated_csv[$tmp_array['path']] = array(
					'blog_id' => $blog_id,
					'basename'=>$csv_filename,
					'row'=>$row_number,
				);
			}

			// 並び替え
			if( $blog_options->orderby ?? null ){
				$sort_orderby = $blog_options->orderby;
				$sort_scending = strtolower($blog_options->scending ?? '');
				usort($this->article_list[$blog_id], function ($a, $b) use ($sort_orderby, $sort_scending){
					if( !isset($a[$sort_orderby]) || !isset($b[$sort_orderby]) ){
						return 0;
					}
					if( $a[$sort_orderby] === $b[$sort_orderby] ){
						return 0;
					}
					if( $a[$sort_orderby] > $b[$sort_orderby] ){
						return ($sort_scending == 'asc' ? 1 : -1);
					}elseif($a[$sort_orderby] < $b[$sort_orderby]){
						return ($sort_scending == 'asc' ? -1 : 1);
					}
					return 0;
				});
			}

			// キャッシュを保存
			$this->px->fs()->mkdir( $realpath_blogmap_cache_dir );
			$this->px->fs()->mkdir( $realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/' );
			$this->px->fs()->save_file( $realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/blogmap.array', self::data2phpsrc($this->blogmap_array[$blog_id]) );
			$this->px->fs()->save_file( $realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/article_list.array', self::data2phpsrc($this->article_list[$blog_id]) );
			$this->px->fs()->save_file( $realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/blogmap_page_originated_csv.array', self::data2phpsrc($blogmap_page_originated_csv) );
			$this->px->fs()->save_file( $realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/csv_md5.txt', md5_file($realpath_blog_csv) );

			// サイトマップキャッシュ作成中のアプリケーションロックを解除
			$this->px->fs()->rm( $realpath_sitemap_cache_dir.'making_sitemap_cache.lock.txt' );

			set_time_limit(30); // タイマーリセット
		}


		// --------------------------------------
		// サイトマップに登録
		$request_file_path = $this->px->req()->get_request_file_path();
		foreach($this->blogmap_array as $blog_id => $blogmap){

			if( isset($blogmap[$request_file_path]) ){
				$this->px->site()->set_page_info(
					$request_file_path,
					$blogmap[$request_file_path]
				);
			}

		}

		return true;
	}

	/**
	 * ブログマップキャッシュが読み込み可能か調べる。
	 *
	 * @param string $blog_id ブログID
	 * @return bool 読み込み可能な場合に `true`、読み込みできない場合に `false` を返します。
	 */
	private function is_blogmap_cache( $blog_id ){
		$realpath_blogmap_cache_dir = $this->px->get_realpath_homedir().'_sys/ram/caches/blogmaps/';
		$realpath_blogmap_dir = $this->px->get_realpath_homedir().'blogmaps/';
		if(
			!is_file($realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'csv_md5.txt') ||
			!is_file($realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'blogmap.array') ||
			!is_file($realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'article_list.array')
		){
			return false;
		}

		if( file_get_contents($realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/csv_md5.txt') !== md5_file($realpath_blog_csv) ){
			return false;
		}

		$blogmap_csvs = $this->px->fs()->ls( $realpath_blogmap_dir );
		if( !is_array($blogmap_csvs) ){
			$blogmap_csvs = array();
		}
		foreach( $blogmap_csvs as $filename ){
			if( !preg_match('/^'.preg_quote($blog_id,'/').'\.csv$/i', $filename) ){
				// 対象ブログのCSVファイル以外は検査しない
				continue;
			}
			if( $this->px->fs()->is_newer_a_than_b( $realpath_blogmap_dir.$filename, $realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'blogmap.array' ) ){
				return false;
			}
		}
		return true;
	}

	/**
	 * ブログの一覧を取得する
	 */
	public function get_blog_list(){
		$this->load_blog_page_list();

		$realpath_homedir = $this->px->get_realpath_homedir();
		$realpath_blogmap_basedir = $realpath_homedir.'blogs/';
		$csv_file_list = $this->px->fs()->ls($realpath_blogmap_basedir);
		$blog_list = array();

		foreach($csv_file_list as $csv_filename){
			if( !preg_match('/\.csv$/i', $csv_filename) ){
				continue;
			}
			$blog_id = preg_replace('/\.csv$/i', '', $csv_filename);
			$blog_info = array(
				"blog_id" => $blog_id,
				"blog_name" => $blog_id,
			);
			array_push($blog_list, $blog_info);
		}
		return $blog_list;
	}

	/**
	 * 記事の一覧を取得する
	 */
	public function get_article_list($blog_id){
		$this->load_blog_page_list();

		return $this->article_list[$blog_id];
	}

	/**
	 * 記事情報を取得する
	 */
	public function get_article_info( $path ){
		$this->load_blog_page_list();

		$path = $this->normalize_article_path($path);
		$originated_csv = $this->get_page_originated_csv( $path );
		if( !$originated_csv ){
			return false;
		}
		return (object) array(
			"blog_id" => $originated_csv->blog_id,
			"article_info" => $this->blogmap_array[$originated_csv->blog_id][$path],
			"originated_csv" => $originated_csv,
		);
	}

	/**
	 * 記事が既存か調べる
	 */
	public function is_article_exists( $path ){
		$this->load_blog_page_list();

		$path = $this->normalize_article_path($path);
		$blog_list = $this->get_blog_list();
		foreach( $blog_list as $blog_info ){
			$blog_info = (object) $blog_info;
			$blogmap_array = $this->blogmap_array[$blog_info->blog_id] ?? null;
			if( isset($blogmap_array[$path]) ){
				return true;
			}
		}
		return false;
	}

	/**
	 * ページパスから、そのページ情報が定義されたCSVのファイル名と行番号を得る
	 *
	 * @param string $path 取得するページのパス
	 * @return array ファイル名(`basename`) と 行番号(`row`) を格納する連想配列。
	 * または、`$path` が見つけられない場合に `null` を、失敗した場合(サイトマップキャッシュが作成されていない、など)に `false` を返します。
	 */
	public function get_page_originated_csv( $path ){
		$this->load_blog_page_list();

		$path = $this->normalize_article_path($path);
		$realpath_homedir = $this->px->get_realpath_homedir();
		$realpath_blogmap_basedir = $realpath_homedir.'blogs/';
		$realpath_blogmap_cache_dir = $realpath_homedir.'_sys/ram/caches/blogs/';

		$blog_list = $this->get_blog_list();
		foreach( $blog_list as $index=>$blog_info ){
			$blog_id = $blog_info['blog_id'];
			if( !is_file($realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/'.'blogmap_page_originated_csv.array') ){
				continue;
			}
			$blogmap_page_originated_csv = include($realpath_blogmap_cache_dir.'blog_'.urlencode($blog_id).'/'.'blogmap_page_originated_csv.array') ?? null;
			$article_info = $this->blogmap_array[$blog_id][$path] ?? null;
			if( !isset($article_info['path']) || !is_array($blogmap_page_originated_csv) || !array_key_exists( $article_info['path'], $blogmap_page_originated_csv ) ){
				continue;
			}
			$rtn = $blogmap_page_originated_csv[$article_info['path']];
			return (object) $rtn;
		}
		return false;
	}

	/**
	 * 記事パス文字列の正規化
	 */
	public function normalize_article_path( $path ){
		$path = preg_replace('/^\/*/', '/', $path);
		$path = preg_replace('/\/+$/', '/'.$this->px->get_directory_index_primary(), $path);
		return $path;
	}

	/**
	 * ブログ記事の一覧を生成する
	 */
	public function mk_list_page( $params ){
		$this->load_blog_page_list();

		$params = (object) $params;
		if( !strlen($params->blog_id??'') ){
			return "";
		}
		$listPage = new listPage($this->px, $params->blog_id, $this->article_list[$params->blog_id], $this->options);
		return $listPage->mk_list_page( $params );
	}

	/**
	 * RSSフィードを生成する
	 */
	public function generate_feeds( $params ){
		$this->load_blog_page_list();

		$params = (object) $params;
		if( !strlen($params->blog_id??'') ){
			return false;
		}
		$obj_rss = new feeds($this->px, $params, $this->article_list[$params->blog_id]);
		return $obj_rss->update_rss_file();
	}

	/**
	 * オプションを取得する
	 */
	public function get_options(){
		return $this->options;
	}

	/**
	 * 変数をPHPのソースコードに変換する。
	 *
	 * `include()` に対してそのままの値を返す形になるよう変換する。
	 *
	 * @param mixed $value 値
	 * @param array $options オプション (`self::data2text()`にバイパスされます。`self::data2text()`の項目を参照してください)
	 * @return string `include()` に対して値 `$value` を返すPHPコード
	 */
	private static function data2phpsrc( $value = null , $options = array() ){
		$rtn = '';
		$rtn .= '<'.'?php'."\n";
		$rtn .= '	/'.'* '.@mb_internal_encoding().' *'.'/'."\n";
		$rtn .= '	return '.var_export( $value, true ).';'."\n";
		$rtn .= '?'.'>';
		return	$rtn;
	}
}
