<?php
namespace pickles2\px2BlogKit;
class register {

	/**
	 * ブログを定義する
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

}
