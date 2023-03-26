<?php
namespace pickles2\px2BlogKit;
class writer {

	private $px;
	private $blog;
	private $options;
	private $article_list = array();

	/**
	 * コンストラクタ
	 * @param object $px PxFWコアオブジェクト
	 * @param object $blog $blog オブジェクト
	 * @param array $options オプション
	 */
	public function __construct($px, $blog, $options){
		$this->px = $px;
		$this->blog = $blog;
		$this->options = (object) $options;
	}

	/**
	 * 新しいブログを作成する
	 */
	public function create_new_blog( $blog_id ){
		$rtn = (object) array(
			"result" => true,
			"message" => null,
			"errors" => (object) array(),
		);

		if( !strlen( $blog_id ?? '' ) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = 'ブログIDを指定してください。';
			return $rtn;
		}
		if( !preg_match('/^[a-zA-Z0-9\_\-]+$/', $blog_id) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = 'ブログIDは、半角英数字、アンダースコア、ハイフンを使って構成してください。';
			return $rtn;
		}

		$realpath_homedir = $this->px->get_realpath_homedir();
		$realpath_blog_basedir = $realpath_homedir.'blogs/';
		$realpath_blog_csv = $realpath_blog_basedir.$blog_id.'.csv';

		if( $this->px->fs()->is_file( $realpath_blog_csv ) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = 'すでに存在します。';
			return $rtn;
		}

		$csv = array(
			array(
				'* title',
				'* path',
				'* release_date',
				'* update_date',
				'* article_summary',
				'* article_keywords',
			),
		);

		$this->px->fs()->save_file( $realpath_blog_csv, $this->px->fs()->mk_csv( $csv ) );

		return $rtn;
	}

	/**
	 * ブログを削除する
	 */
	public function delete_blog( $blog_id ){
		$rtn = (object) array(
			"result" => true,
			"message" => null,
			"errors" => (object) array(),
		);

		if( !strlen( $blog_id ?? '' ) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = 'ブログIDを指定してください。';
			return $rtn;
		}
		if( !preg_match('/^[a-zA-Z0-9\_\-]+$/', $blog_id) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = 'ブログIDは、半角英数字、アンダースコア、ハイフンを使って構成してください。';
			return $rtn;
		}

		$realpath_homedir = $this->px->get_realpath_homedir();
		$realpath_blog_basedir = $realpath_homedir.'blogs/';
		$realpath_blog_csv = $realpath_blog_basedir.$blog_id.'.csv';

		if( !$this->px->fs()->is_file( $realpath_blog_csv ) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = '存在しません。';
			return $rtn;
		}

		$this->px->fs()->rm( $realpath_blog_csv );

		return $rtn;
	}

	/**
	 * 新しい記事を作成する
	 */
	public function create_new_article( $blog_id, $fields ){
		$fields = (object) $fields;
		$rtn = (object) array(
			"result" => true,
			"message" => null,
			"errors" => (object) array(),
		);

		if( !strlen( $blog_id ?? '' ) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = 'ブログIDを指定してください。';
			return $rtn;
		}
		if( !preg_match('/^[a-zA-Z0-9\_\-]+$/', $blog_id) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = 'ブログIDは、半角英数字、アンダースコア、ハイフンを使って構成してください。';
			return $rtn;
		}

		$realpath_homedir = $this->px->get_realpath_homedir();
		$realpath_blog_basedir = $realpath_homedir.'blogs/';
		$realpath_blog_csv = $realpath_blog_basedir.$blog_id.'.csv';

		if( !$this->px->fs()->is_file( $realpath_blog_csv ) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = '指定のブログは存在しません。';
			return $rtn;
		}

		$fields->path = $this->blog->normalize_article_path($fields->path ?? '');

		$blogmap_definition = $this->get_blogmap_definition( $blog_id );
		$csv_row = array();
		foreach( $blogmap_definition as $blogmap_definitionRow ){
			array_push( $csv_row, $fields->{$blogmap_definitionRow->key} ?? '' );
		}

		$csv = $this->px->fs()->read_csv( $realpath_blog_csv );
		array_push( $csv, $csv_row );

		$csv = $this->sort_csv($csv, $blogmap_definition);

		$this->px->fs()->save_file( $realpath_blog_csv, $this->px->fs()->mk_csv( $csv ) );

		return $rtn;
	}

	/**
	 * 記事を更新する
	 */
	public function update_article( $blog_id, $path, $fields ){
		$fields = (object) $fields;
		$rtn = (object) array(
			"result" => true,
			"message" => null,
			"errors" => (object) array(),
		);

		if( !strlen( $blog_id ?? '' ) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = 'ブログIDを指定してください。';
			return $rtn;
		}
		if( !preg_match('/^[a-zA-Z0-9\_\-]+$/', $blog_id) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = 'ブログIDは、半角英数字、アンダースコア、ハイフンを使って構成してください。';
			return $rtn;
		}

		$realpath_homedir = $this->px->get_realpath_homedir();
		$realpath_blog_basedir = $realpath_homedir.'blogs/';
		$realpath_blog_csv = $realpath_blog_basedir.$blog_id.'.csv';

		if( !$this->px->fs()->is_file( $realpath_blog_csv ) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = '指定のブログは存在しません。';
			return $rtn;
		}

		$path = $this->blog->normalize_article_path($path ?? '');
		$fields->path = $this->blog->normalize_article_path($fields->path ?? '');

		$blogmap_definition = $this->get_blogmap_definition( $blog_id );
		$article_info = $this->blog->get_article_info($path);
		$csv_row = array();
		foreach( $blogmap_definition as $blogmap_definitionRow ){
			array_push( $csv_row, $fields->{$blogmap_definitionRow->key} ?? '' );
		}

		$csv = $this->px->fs()->read_csv( $realpath_blog_csv );
		$csv[$article_info->originated_csv->row] = $csv_row;

		$csv = $this->sort_csv($csv, $blogmap_definition);

		$this->px->fs()->save_file( $realpath_blog_csv, $this->px->fs()->mk_csv( $csv ) );

		return $rtn;
	}

	/**
	 * 記事を削除する
	 */
	public function delete_article( $blog_id, $path ){
		$path = $this->blog->normalize_article_path($path);
		$rtn = (object) array(
			"result" => true,
			"message" => null,
			"errors" => (object) array(),
		);

		if( !strlen( $blog_id ?? '' ) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = 'ブログIDを指定してください。';
			return $rtn;
		}
		if( !preg_match('/^[a-zA-Z0-9\_\-]+$/', $blog_id) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = 'ブログIDは、半角英数字、アンダースコア、ハイフンを使って構成してください。';
			return $rtn;
		}

		$realpath_homedir = $this->px->get_realpath_homedir();
		$realpath_blog_basedir = $realpath_homedir.'blogs/';
		$realpath_blog_csv = $realpath_blog_basedir.$blog_id.'.csv';

		if( !$this->px->fs()->is_file( $realpath_blog_csv ) ){
			$rtn->result = false;
			$rtn->message = '入力内容を確認してください。';
			$rtn->errors->blog_id = '指定のブログは存在しません。';
			return $rtn;
		}

		$article_info = $this->blog->get_article_info($path);
		$csv = $this->px->fs()->read_csv( $realpath_blog_csv );

		unset( $csv[$article_info->originated_csv->row] );

		$this->px->fs()->save_file( $realpath_blog_csv, $this->px->fs()->mk_csv( $csv ) );

		return $rtn;
	}

	/**
	 * ブログマップ定義を取得する
	 */
	public function get_blogmap_definition( $blog_id ){
		$realpath_homedir = $this->px->get_realpath_homedir();
		$realpath_blog_basedir = $realpath_homedir.'blogs/';
		$realpath_blog_csv = $realpath_blog_basedir.$blog_id.'.csv';

		$csv = $this->px->fs()->read_csv( $realpath_blog_csv );
		$blogmap_definition = $this->parse_blogmap_definition( $csv );

		$rtn = array();
		foreach($blogmap_definition as $blogmap_definition_key){
			array_push( $rtn, (object) array(
				"label" => $blogmap_definition_key, // TODO: 仮実装
				"type" => "text", // TODO: 仮実装
				"key" => $blogmap_definition_key,
			) );
		}
		return $rtn;
	}

	/**
	 * ブログマップに定義行が含まれるか調べる
	 */
	private function has_blogmap_definition( $csv ){
		if( !is_array($csv) || !count($csv) || !isset($csv[0]) ){
			return false;
		}

		$row = $csv[0];
		if( !is_array($row) || !count($row) || !isset($row[0]) ){
			return false;
		}
		if( !preg_match('/^\*/', $row[0]) ){
			return false;
		}
		return true;
	}

	/**
	 * ブログマップ定義を解析する
	 */
	private function parse_blogmap_definition( $csv ){
		if( !$this->has_blogmap_definition( $csv ) ){
			return $this->get_default_blogmap_definition();
		}

		$row = $csv[0];
		$rtn = array();
		foreach($row as $col){
			$def = preg_replace('/^\*\s*/', '', $col);
			array_push( $rtn, $def );
		}

		return $rtn;
	}

	/**
	 * デフォルトのブログマップ定義を取得する
	 */
	private function get_default_blogmap_definition(){
		$blogmap_definition = array(
			'title',
			'path',
			'release_date',
			'update_date',
			'article_summary',
			'article_keywords',
		);
		return $blogmap_definition;
	}

	/**
	 * ブログマップCSVをソートする
	 */
	private function sort_csv( $csv, $blogmap_definition ){
		$sort_orderby = 0;
		$sort_scending = "desc";
		foreach( $blogmap_definition as $index=>$row ){
			if($row->key == "update_date"){
				$sort_orderby = $index;
				break;
			}
		}

		$definition_row = null;
		if( $this->has_blogmap_definition($csv) ){
			$definition_row = array_shift($csv);
		}

		usort($csv, function ($a, $b) use ($sort_orderby, $sort_scending){
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

		if( $definition_row ){
			array_unshift($csv, $definition_row);
		}
		return $csv;
	}
}
