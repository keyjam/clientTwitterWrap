<?php
// abrahamさんのOauthライブラリ読み込み
require_once('twitteroauth/twitteroauth.php');

// 環境設定情報読み込み
require_once('conf.php');

/******************************************************
 Oauth認証開始
*******************************************************/
try {

	$objOauth = new TwitterOAuth($conf['consKey'],$conf['consSecret'],$conf['acsToken'],$conf['acsTokenSecret']);

} catch (Services_Twitter_Exception $e) {

	echo $e->getMessage();

}

/******************************************************
 ここからリプライ返信処理
*******************************************************/

// フォロワーの情報を取得
$res = $objOauth->OAuthRequest('http://api.twitter.com/1/statuses/friends_timeline.json','GET',array());

$data = json_decode($res,true);

// 友人のtweetの古い順にする
$data = array_reverse($data);

// 取得するtweet時間帯を指定
$date = strtotime("now")-10*60;

// リプライに対する返信処理
foreach ( $data as $key ) {

	if ($date < strtotime($key['created_at'])) {

		shout($objOauth,$conf['username'],$key['text'],$key['user']['screen_name'],$key['user']['name'],$key['user']['id']);

	}

}

/******************************************************
 ここから定期投稿処理
*******************************************************/

if ( !postMessage($objOauth,$conf) ) {
	postMessage($objOauth,$conf);
}

/******************************************************
 ここから関数定義
*******************************************************/

/**
* ツイート投稿
* @param obj Oauth
* @param array 基本設定値
* @return bool
**/
function postMessage($obj,$conf) {

	$status = getMessage($conf['csv']);

	// 投稿実施
	$res = $obj->OAuthRequest('http://api.twitter.com/1/statuses/update.json','POST',array('status'=>$status));
	$res = @json_decode($res,true);

	if (isset($res['error'])) {
		return false;
	}
	return true;
}

/**
* ツイート返信
* @param object Oauth obj
* @param String 自分のユーザネーム
* @param String tweet内容
* @param screen_name リプライをくれたユーザ名
* @param name リプライをくれたユーザ名
* @param id tweer_id
* @return none
**/
function shout($obj, $uname, $text, $screen_name, $name, $id) {
	// 自分のツイートの場合処理を中止
	if ($uname == $screen_name) {
		return;
	}

	// 自分へのツイートの場合
	if (preg_match("/\@$uname/", $text)) {
		if (preg_match("/RT/",$text)) {
			return;
		} elseif(preg_match("/うーん/",$text)) {
		$tw = array("こんちわ","こん","こんこん");
		$twCount = count($tw)-1;
		$twSelect= mt_rand(0,$twCount);
		}
	}

	// 特定のtweetがあった場合
	if (preg_match("/リーダー/",$text)) {		
		$tw = array("おれに惚れまくりだな","おれのことだな");
		$twCount = count($tw)-1;
		$twSelect= mt_rand(0,$twCount);
	}
	
	// 返信tweet投稿
	$status = '@'.$screen_name.' '.$tw[$twSelect];
	$res = $obj->OAuthRequest('http://api.twitter.com/1/statuses/update.json','POST',array('status'=>$status));
	if (!$res) {
		return;
	}
}

/**
* 特定のつぶやき時にフォローする
* @param obj Oaurh
* @param String ユーザscree_name
* @return
* TODO:実装検討
**/
//function createFriend ($obj,$screen_name) {
//}

/**
* ツイート投稿データの取得
* @param file csvファイル
* @return data 配列
**/
function getMessage($file) {

        // CSV取得
        $handle = @file($file);
        for ($i=0; $i<count($handle);$i++){
                $dataAry[]   = explode(",",rtrim($handle[$i]));
                $mbDataAry[] = array_map("sjis2utf",$dataAry[$i]);
	}
	$parCount= mt_rand(0, count($mbDataAry)-1);
	$chiCount= mt_rand(0, count($mbDataAry[$parCount])-1);
	$tw = $mbDataAry[$parCount][$chiCount];
	return $tw;
}

/**
* UTF-8に変換
* @param array csv要素
* @return array
**/
function sjis2utf($ary) {
        return mb_convert_encoding($ary,"UTF-8","ASCII,JIS,UTF-8,EUC-JP,SJIS");
}

?>
