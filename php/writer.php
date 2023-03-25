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

}
