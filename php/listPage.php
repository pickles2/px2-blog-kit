<?php
namespace pickles2\px2BlogKit;
class listPage {

	private $px;
	private $options;
	private $blog_id;
	private $current_blog_article_list;
	private $current_pager_num = 1;
	private $path_default_thumb_image;

	/**
	 * コンストラクタ
	 * @param object $px PxFWコアオブジェクト
	 * @param string $blog_id ブログID
	 * @param array $current_blog_article_list ブログ記事リスト
	 * @param array $options オプション
	 */
	public function __construct($px, $blog_id, $current_blog_article_list, $options){
		$this->px = $px;
		$this->blog_id = $blog_id;
		$this->current_blog_article_list = $current_blog_article_list;
		$this->options = (object) $options;

		$this->path_default_thumb_image = 'data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/../resources/images/noimage.png'));

		$this->parse_request();
	}

	/**
	 * ブログ記事の一覧を生成する
	 * @param object $params パラメータ
	 * @return string HTMLコード
	 */
	public function mk_list_page( $params ){
		$params = (object) $params;
		$rtn = '';

		$template = '';
		if( isset($params->template) && is_string($params->template) ){
			$template = $params->template;
		}else{
			$template = file_get_contents( __DIR__.'/../resources/templates/list.twig' );
			$stylesheet = '';
			$stylesheet .= '<style> /* Page List Generator */ ';
			$stylesheet .= file_get_contents( __DIR__.'/../resources/styles/pagelist.css' );
			$stylesheet .= '</style>'."\n";
			$this->px->bowl()->put($stylesheet, 'head');
		}

		$twigHelper = new helpers\twigHelper();
		$pager = $this->get_pager_info( $params );
		$list = $this->get_list( $params );

		if( $pager['total_page_count'] > 1 ){
			for( $idx = $pager['index_start']; $idx <= $pager['index_end']; $idx ++ ){
				if( $idx != $pager['current'] ){
					$this->px->add_relatedlink( $this->href_pager( $idx, $params ) );
				}
			}
		}

		foreach($list as $blog_article_info){
			$this->px->add_relatedlink( $blog_article_info['path'] );
		}

		$rtn .= $twigHelper->bind(
			$template,
			array(
				'lang' => $this->px->lang(),
				'pager' => $pager,
				'list' => $list,
			),
			array(
				'href_pager' => function( $page_num ) use ($params){
					return $this->href_pager( $page_num, $params );
				},
				'href' => function( $path ){
					return $this->px->href( $path );
				},
				'thumb' => function( $path ){
					return $this->get_article_thumb($path);
				},
			)
		);

		return $rtn;
	}

	/**
	 * リクエストの内容を解析する
	 * @return boolean true
	 */
	private function parse_request(){
		$path_param = $this->px->site()->get_path_param('');
		$path_param = preg_replace( '/'.$this->px->get_directory_index_preg_pattern().'$/', '', $path_param??'' );

		$paramlist = array();
		if( strlen($path_param ?? '') ){
			if( !preg_match('/^[1-9][0-9]*\/$/si', $path_param??'') ){
				return $this->page_notfound();
			}
			$paramlist = explode( '/', $path_param );
		}
		if( !($paramlist[0] ?? null) ){
			$paramlist[0] = 1;
		}
		$paramlist[0] = intval($paramlist[0]);

		$this->current_pager_num = $paramlist[0];
		return true;
	}

	/**
	 * ページャー情報を計算して答える。
	 * 
	 * `$options' に次の設定を渡すことができます。
	 * 
	 * <dl>
	 *   <dt>int $options['index_size']</dt>
	 *     <dd>インデックスの範囲</dd>
	 * </dl>
	 * 
	 * @param int $total_count 総件数
	 * @param int $current_page_num カレントページのページ番号
	 * @param int $display_per_page 1ページ当りの表示件数
	 * @param array $params オプション
	 * 
	 * @return array ページャー情報を格納した連想配列
	 */
	private function get_pager_info( $params = null ){
		$params = (object) $params;
		$total_count = count($this->current_blog_article_list);
		$current_page_num = $this->current_pager_num;
		$display_per_page = intval( $params->dpp ?? 1 );

		// 現在のページ番号
		$current_page_num = intval( $current_page_num );
		if( $current_page_num <= 0 ){ $current_page_num = 1; }

		// ページ当たりの表示件数
		$display_per_page = intval( $display_per_page );
		if( $display_per_page <= 1 ){ $display_per_page = 1; }

		// インデックスの範囲
		$index_size = 0;
		if( !is_null( $params->index_size ?? null ) ){
			$index_size = intval( $params->index_size );
		}
		if( $index_size < 1 ){
			$index_size = 5;
		}

		$RTN = array(
			'tc'=>$total_count,
			'dpp'=>$display_per_page,
			'current'=>$current_page_num,
			'total_page_count'=>null,
			'first'=>null,
			'prev'=>null,
			'next'=>null,
			'last'=>null,
			'limit'=>$display_per_page,
			'offset'=>0,
			'index_start'=>0,
			'index_end'=>0,
			'errors'=>array(),
		);

		// 総件数
		$total_count = intval( $total_count );
		if( $total_count <= 0 ){
			$RTN['total_page_count'] = 0;
			return $RTN;
		}

		if( $total_count%$display_per_page ){
			$RTN['total_page_count'] = intval($total_count/$display_per_page) + 1;
		}else{
			$RTN['total_page_count'] = intval($total_count/$display_per_page);
		}

		if( $RTN['total_page_count'] != $current_page_num ){
			$RTN['last'] = $RTN['total_page_count'];
		}
		if( 1 != $current_page_num ){
			$RTN['first'] = 1;
		}

		if( $RTN['total_page_count'] > $current_page_num ){
			$RTN['next'] = intval($current_page_num) + 1;
		}
		if( 1 < $current_page_num ){
			$RTN['prev'] = intval($current_page_num) - 1;
		}

		$RTN['offset'] = ($RTN['current']-1)*$RTN['dpp'];

		if( $current_page_num > $RTN['total_page_count'] ){
			array_push( $RTN['errors'] , 'Current page num ['.$current_page_num.'] is over the Total page count ['.$RTN['total_page_count'].'].' );
		}

		// インデックスの範囲
		// 	23:50 2007/08/29 Pickles Framework 0.1.8 追加
		$RTN['index_start'] = 1;
		$RTN['index_end'] = $RTN['total_page_count'];
		if( ( $index_size*2+1 ) >= $RTN['total_page_count'] ){
			// 範囲のふり幅全開にしたときに、
			// 総ページ数よりも多かったら、常に全部出す。
			$RTN['index_start'] = 1;
			$RTN['index_end'] = $RTN['total_page_count'];
		}elseif( ( $index_size < $RTN['current'] ) && ( $index_size < ( $RTN['total_page_count']-$RTN['current'] ) ) ){
			// 範囲のふり幅全開にしたときに、
			// すっぽり収まるようなら、前後に $index_size 分だけ出す。
			$RTN['index_start'] = $RTN['current']-$index_size;
			$RTN['index_end'] = $RTN['current']+$index_size;
		}elseif( $index_size >= $RTN['current'] ){
			// 前方が収まらない場合は、
			// あまった分を後方に回す
			$surplus = ( $index_size - $RTN['current'] + 1 );
			$RTN['index_start'] = 1;
			$RTN['index_end'] = $RTN['current']+$index_size+$surplus;
		}elseif( $index_size >= ( $RTN['total_page_count']-$RTN['current'] ) ){
			// 後方が収まらない場合は、
			// あまった分を前方に回す
			$surplus = ( $index_size - ($RTN['total_page_count']-$RTN['current']) );
			$RTN['index_start'] = $RTN['current']-$index_size-$surplus;
			$RTN['index_end'] = $RTN['total_page_count'];
		}

		return	$RTN;
	}

	/**
	 * リスト配列を取得する
	 */
	public function get_list($params){
		$pager_info = $this->get_pager_info($params);
		$rtn = array();
		for( $i = $pager_info['dpp']*($pager_info['current']-1); $i < $pager_info['dpp']*($pager_info['current']) && ($this->current_blog_article_list[$i] ?? null); $i++ ){
			array_push( $rtn, $this->current_blog_article_list[$i] );
		}
		return $rtn;
	}

	/**
	 * リスト配列を全件取得する
	 */
	public function get_list_all(){
		return $this->current_blog_article_list;
	}

	/**
	 * ページャーごとのURLを生成
	 */
	private function href_pager( $page_num, $params ){
		$params = (object) $params;
		$bind_param = $page_num.'/';
		if( $page_num == 1 ){
			$bind_param = '';
		}
		$current_page_info = $this->px->site()->get_current_page_info();
		if( isset($params->list_page_id) && is_string($params->list_page_id) && strlen($params->list_page_id) ){
			$current_page_info = $this->px->site()->get_page_info( $params->list_page_id );
		}
		$rtn = $this->px->href( $this->px->site()->bind_dynamic_path_param( $current_page_info['path'], array(''=>$bind_param) ) );
		return $rtn;
	}

	/**
	 * 記事本文から、サムネイルに使う画像を抽出する
	 *
	 * @param object $path ページのパスまたはページID
	 * @return string 画像のURL
	 */
	private function get_article_thumb( $path ){
		$path_thumb = $this->path_default_thumb_image;

		$path_content = $path;

		foreach( array_keys( get_object_vars( $this->px->conf()->funcs->processor ) ) as $tmp_ext ){
			if( $this->px->fs()->is_file( './'.$path_content.'.'.$tmp_ext ) ){
				$path_content = $path_content.'.'.$tmp_ext;
				break;
			}
		}

		if( !is_file('./'.$path_content) ){
			return $path_thumb;
		}

		$src_content = file_get_contents('./'.$path_content);


		// HTML属性を削除
		$tmp_path_thumb = null;
		require_once(__DIR__.'/simple_html_dom.php');
		$html = str_get_html(
			$src_content,
			true, // $lowercase
			true, // $forceTagsClosed
			DEFAULT_TARGET_CHARSET, // $target_charset
			false, // $stripRN
			DEFAULT_BR_TEXT, // $defaultBRText
			DEFAULT_SPAN_TEXT // $defaultSpanText
		);
		if( $html ){
			$ret = $html->find('img');
			foreach( $ret as $retRow ){
				$tmp_path_thumb = $retRow->src;
				break;
			}
		}

		if( preg_match('/^.*\$px\-\>path\_files\((\"|\')(.*?)(\1)\).*$/s', $tmp_path_thumb ?? '', $matched) ){
			$tmp_localpath_thumb = $matched[2];
			$tmp_path_thumb = $this->path_files($path_content, $tmp_localpath_thumb);
		}

		if( strlen($tmp_path_thumb ?? '') ){
			if( preg_match( '/^\//', $tmp_path_thumb??'' ) ){
				$path_thumb = $this->px->conf()->path_controot.$tmp_path_thumb;
			}else{
				$path_thumb = dirname($this->px->conf()->path_controot.$path_content).'/'.$tmp_path_thumb;
			}
			$path_thumb = $this->px->fs()->normalize_path( $this->px->fs()->get_realpath( $path_thumb ) );
		}

		return $path_thumb;
	}

	/**
	 * ローカルリソースディレクトリのパスを得る。
	 *
	 * @param string $localpath_resource ローカルリソースのパス
	 * @return string ローカルリソースの実際の絶対パス
	 */
	private function path_files( $path_content, $localpath_resource = null ){
		if( is_null($path_content) ){
			$path_content = $this->px->req()->get_request_file_path();
		}

		$rtn = '';
		if( is_callable($this->px->conf()->path_files) ){
			// コールバック関数が設定された場合
			$rtn = call_user_func($this->px->conf()->path_files, $this->px->fs()->normalize_path($path_content) );
		}elseif( is_string($this->px->conf()->path_files) && strpos(trim($this->px->conf()->path_files ?? ""), 'function') === 0 ){
			// function で始まる文字列が設定された場合
			$rtn = call_user_func(eval('return '.$this->px->conf()->path_files.';'), $this->px->fs()->normalize_path($path_content) );
		}else{
			$rtn = $this->px->conf()->path_files;
			$data = array(
				'dirname'=>$this->px->fs()->normalize_path(dirname($path_content)),
				'filename'=>basename($this->px->fs()->trim_extension($path_content)),
				'ext'=>strtolower($this->px->fs()->get_extension($path_content)),
			);
			$rtn = str_replace( '{$dirname}', $data['dirname'], $rtn );
			$rtn = str_replace( '{$filename}', $data['filename'], $rtn );
			$rtn = str_replace( '{$ext}', $data['ext'], $rtn );
		}

		$rtn = preg_replace( '/^\/*/', '/', $rtn );
		$rtn = preg_replace( '/\/*$/', '', $rtn ).'/';
		$rtn = $rtn.$localpath_resource;
		if( $this->px->fs()->is_dir('./'.$rtn) ){
			$rtn .= '/';
		}
		$rtn = $this->px->href( $rtn );
		$rtn = $this->px->fs()->normalize_path($rtn);
		$rtn = preg_replace( '/^\/+/', '/', $rtn );
		return $rtn;
	}

	/**
	 * NotFound画面
	 */
	private function page_notfound(){
		$this->px->set_status(404);// 404 NotFound
		return;
	}
}
