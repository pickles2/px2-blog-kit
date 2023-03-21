<?php
namespace pickles2\px2BlogKit;
class customConsole {


    /** $px */
    private $px;

    /** $options */
    private $options;

    /** $cceAgent */
    private $cceAgent;

    /**
     * 登録処理
     */
    static public function register( $options = null ){
        return __CLASS__.'('.( json_encode($options) ).')';
    }

    /**
     * コンストラクタ
     * @param object $px Pickles 2 オブジェクト
     * @param object $options 設定オプション
     * @param object $cceAgent 管理画面拡張エージェントオブジェクト
     */
    public function __construct($px, $options, $cceAgent){
        $this->px = $px;
        $this->options = $options;
        $this->cceAgent = $cceAgent;
    }

    /**
     * 管理機能名を取得する
     */
    public function get_label(){
        if( $this->px->lang() == 'ja' ){
            return 'ブログ管理';
        }
        return 'Blog';
    }

    /**
     * フロントエンド資材の格納ディレクトリを取得する
     */
    public function get_client_resource_base_dir(){
        return __DIR__.'/../customConsole/frontend/';
    }

    /**
     * 管理画面にロードするフロント資材のファイル名を取得する
     */
    public function get_client_resource_list(){
        $rtn = array();
        $rtn['css'] = array();
        array_push($rtn['css'], 'blogKit.css');
        $rtn['js'] = array();
        array_push($rtn['js'], 'blogKit.js');
        return $rtn;
    }

    /**
     * 管理画面を初期化するためのJavaScript関数名を取得する
     */
    public function get_client_initialize_function(){
        return 'window.pickles2BlogKitCustomConsoleExtension';
    }

    /**
     * General Purpose Interface (汎用API)
     */
    public function gpi($request){
        switch($request->command){
            case 'test-gpi-call':
                return 'Test GPI Call Successful.';
        }
        return false;
    }
}
