<?php
namespace pickles2\px2BlogKit;
class blog {

	private $px;
	private $options;
	private $article_list = array();

	/**
	 * コンストラクタ
	 * @param object $px PxFWコアオブジェクト
	 * @param array $options オプション
	 */
	public function __construct($px, $options){
		$this->px = $px;
		$this->options = (object) $options;

		$this->load_blog_page_list();
	}

	/**
	 * ブログページを読み込む
	 */
	private function load_blog_page_list(){
		$path_blog_page_list_cache_dir = $this->px->get_realpath_homedir().'_sys/ram/caches/blogs/';
		$realpath_homedir = $this->px->get_realpath_homedir();
		$realpath_blog_basedir = $realpath_homedir.'blogs/';

		$csv_file_list = $this->px->fs()->ls($realpath_blog_basedir);
		$blogmap_array = array();

		foreach($csv_file_list as $csv_filename){
			$blog_id = preg_replace('/\..*$/i', '', $csv_filename);
			$blog_options = ($this->options->blogs->{$blog_id} ?? (object) array());

			$realpath_blog_csv = $realpath_blog_basedir.$blog_id.'.csv';
			if( !is_file($realpath_blog_csv) ){
				// CSVファイルは存在しない
				continue;
			}

			$blogmap_array[$blog_id] = array();
			$this->article_list[$blog_id] = array();

			if( is_file($path_blog_page_list_cache_dir.'blog_'.$blog_id.'/csv_md5.txt') && file_get_contents($path_blog_page_list_cache_dir.'blog_'.$blog_id.'/csv_md5.txt') === md5_file($realpath_blog_csv) ){
				// キャッシュが有効
				$blogmap_array[$blog_id] = include($path_blog_page_list_cache_dir.'blog_'.$blog_id.'/blogmap.array');
				$this->article_list[$blog_id] = include($path_blog_page_list_cache_dir.'blog_'.$blog_id.'/article_list.array');
				continue;
			}

			$blog_page_list_csv = $this->px->fs()->read_csv($realpath_blog_csv);
			$tmp_sitemap_definition = array();
			$sitemap_definition_keys = array();
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
					$tmp_sitemap_definition = array();
					$tmp_col_id = 'A';
					foreach($row as $tmp_col_number=>$cell_value){
						$col_name = trim( preg_replace('/^\*/si', '', $cell_value) );
						if( $col_name == $cell_value ){
							// アスタリスクで始まらない列は定義行として認めず、無視する。
							$tmp_col_id++;
							continue;
						}
						$tmp_sitemap_definition[$col_name] = array(
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

				foreach ($tmp_sitemap_definition as $defrow) {
					$tmp_array[$defrow['key']] = $row[$defrow['num']] ?? null;
					if( array_search( $defrow['key'], $sitemap_definition_keys ) === false && preg_match('/^[a-zA-Z0-9\_]+$/si', $defrow['key']) && !preg_match('/^\_\_\_\_/si', $defrow['key']) ){
						array_push($sitemap_definition_keys, $defrow['key']);
						$sitemap_definition[$defrow['key']] = array(
							'label' => null,
							'type' => null,
						);
					}
				}

				// 前後の空白文字を削除する
				foreach(array('title', 'filename', 'release_date', 'update_date', 'article_summary', 'article_keywords') as $tmpDefKey){
					if( array_key_exists($tmpDefKey, $tmp_array) && is_string($tmp_array[$tmpDefKey]) ){
						$tmp_array[$tmpDefKey] = trim($tmp_array[$tmpDefKey]);
					}
				}

				// --------------------------------------
				// サイトマップ項目を補完する
				$tmp_array['path'] = $tmp_array['path'] ?? '';
				if( preg_match('/\/$/', $tmp_array['path']) ){
					$tmp_array['path'] .= $this->px->conf()->directory_index[0] ?? 'index.html';
				}
				$tmp_array['content'] = $tmp_array['content'] ?? $tmp_array['path'];
				$tmp_array['logical_path'] = $blog_options->logical_path;
				$tmp_array['list_flg'] = 0;
				$tmp_array['category_top_flg'] = 0;

				$blogmap_array[$blog_id][$tmp_array['path']] = $tmp_array;
				array_push($this->article_list[$blog_id], $tmp_array);
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
			$this->px->fs()->mkdir( $path_blog_page_list_cache_dir );
			$this->px->fs()->mkdir( $path_blog_page_list_cache_dir.'blog_'.$blog_id.'/' );
			$this->px->fs()->save_file( $path_blog_page_list_cache_dir.'blog_'.$blog_id.'/blogmap.array', self::data2phpsrc($blogmap_array[$blog_id]) );
			$this->px->fs()->save_file( $path_blog_page_list_cache_dir.'blog_'.$blog_id.'/article_list.array', self::data2phpsrc($this->article_list[$blog_id]) );
			$this->px->fs()->save_file( $path_blog_page_list_cache_dir.'blog_'.$blog_id.'/csv_md5.txt', md5_file($realpath_blog_csv) );
			set_time_limit(30); // タイマーリセット
		}


		// --------------------------------------
		// サイトマップに登録
		$request_file_path = $this->px->req()->get_request_file_path();
		foreach($blogmap_array as $blog_id => $blogmap){

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
	 * ブログ記事の一覧を生成する
	 */
	public function mk_list_page( $params ){
		$params = (object) $params;
		$listPage = new listPage($this->px, $params->blog_id, $this->article_list[$params->blog_id], $this->options);
		return $listPage->mk_list_page( $params );
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
