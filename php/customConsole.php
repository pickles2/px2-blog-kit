<?php
namespace pickles2\px2BlogKit;
class customConsole {


    /** $px */
    private $px;

    /** $json */
    private $json;

    /** $cceAgent */
    private $cceAgent;

    /**
     * コンストラクタ
     */
    public function __construct($px, $json, $cceAgent){
        $this->px = $px;
        $this->json = $json;
        $this->cceAgent = $cceAgent;
    }

    /**
     * 管理機能名を取得する
     */
    public function get_label(){
        return 'サンプル機能拡張';
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
                $this->cceAgent->async(array(
                    'type'=>'gpi',
                    'request' => array(
                        'command' => 'test-async',
                    ),
                ));
                return 'Test GPI Call Successful.';

            case 'test-async':
                $this->cceAgent->broadcast(array(
                    'message'=>'Hello Broadcast Message !',
                ));
                return 'Test GPI Call Successful.';
        }
        return false;
    }
}
