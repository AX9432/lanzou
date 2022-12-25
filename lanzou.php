<?php


header('Content-Type:application/json; charset=utf-8');
//默认UA
$UserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36';
$url = isset($_POST['url']) ? $_POST['url'] : "";
$pwd = isset($_POST['pwd']) ? $_POST['pwd'] : "";
$type = isset($_POST['type']) ? $_POST['type'] : "";
if (empty($url)) {
    die(
    json_encode(
        array(
            'code' => 400,
            'msg' => '请输入需要解析的蓝奏云网盘地址'
        ),480)
    );
}
//一个简单的链接处理
$url='https://www.lanzoux.com/'.explode('.com/',$url)['1'];
$softInfo = MloocCurlGet($url);
if (strstr($softInfo, "文件取消分享了") != false) {
    die(
    json_encode(
        array(
            'code' => 400,
            'msg' => '文件取消分享了'
        ),480)
    );
}
preg_match('~style="font-size: 30px;text-align: center;padding: 56px 0px 20px 0px;">(.*?)<\/div>~', $softInfo, $softName);
if(!isset($softName[1])){
	preg_match('~<div class="n_box_3fn".*?>(.*?)</div>~', $softInfo, $softName);
}
preg_match('/<meta name="description" content="(.*?)"/', $softInfo, $softFilesize);
if(!isset($softName[1])){
	preg_match('~var filename = \'(.*?)\';~', $softInfo, $softName);
}

if (strstr($softInfo, "手机Safari可在线安装") != false) {
  	if(strstr($softInfo, "n_file_infos") != false){
      	$ipaInfo = MloocCurlGet($url, 'Mozilla/5.0 (iPhone; CPU iPhone OS 10_3_1 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/10.0 Mobile/14E304 Safari/602.1');
    	preg_match('~href="(.*?)" target="_blank" class="appa"~', $ipaInfo, $ipaDownUrl);
    }else{
    	preg_match('~com/(\w+)~', $url, $lanzouId);
        if (!isset($lanzouId[1])) {
            die(
            json_encode(
                array(
                    'code' => 400,
                    'msg' => '解析失败，获取不到文件ID'
                ),480)
            );
        }
        $lanzouId = $lanzouId[1];
        $ipaInfo = MloocCurlGet("https://www.lanzoux.com/tp/" . $lanzouId, 'Mozilla/5.0 (iPhone; CPU iPhone OS 10_3_1 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/10.0 Mobile/14E304 Safari/602.1');
        preg_match('~href="(.*?)" id="plist"~', $ipaInfo, $ipaDownUrl);
        //preg_match('/(?<!<!--)<iframe.+?src="(.+?)".+?<\/iframe>/i', $ipaInfo, $ipaDownUrl);
    }
    
    $ipaDownUrl = isset($ipaDownUrl[1]) ? $ipaDownUrl[1] : "";
    if ($type != "down") {
        die(
        json_encode(
            array(
                'code' => 200,
                'msg' => '直链解析成功！',
                'name' => isset($softName[1]) ? $softName[1] : "",
                'filesize' => isset($softFilesize[1]) ? str_replace(['文件大小：','|'], '',$softFilesize[1]) : "",
                'downurl' => $ipaDownUrl ,
                'text' => [
                'msg' => '此接口只支持蓝奏云网盘解析'
                ,'copyright'  => '五行资源博客wep.vipyshy.com'
                ,'time'=>'当前解析时间为：'.date('Y-m-d H:i:s',time())]
            ),480)
        );
    } else {
        header("Location:$ipaDownUrl");
        die;
    }
}
if(strstr($softInfo, "function down_p(){") != false){
	if(empty($pwd)){
		die(
		json_encode(
			array(
				'code' => 400,
				'msg' => '请输入分享密码'
			),480)
		);
	}
	preg_match("~action=(.*?)&sign=(.*?)&p='\+(.*?),~", $softInfo, $segment);
	$post_data = array(
		"action" => $segment[1],
		"sign" => $segment[2],
		"p" => $pwd
	);
	$softInfo = MloocCurlPost($post_data, "https://www.lanzoux.com/ajaxm.php", $url);
	$softName[1] = json_decode($softInfo,true)['inf'];
}else{
	//preg_match("~\n<iframe.*?name=\"[\s\S]*?\"\ssrc=\"\/(.*?)\"~", $softInfo, $link);
    preg_match('/(?<!<!--)<iframe.+?src="(.+?)".+?<\/iframe>/i', $softInfo, $link);
	$ifurl = "https://www.lanzoux.com/" . $link[1];
	$softInfo = MloocCurlGet($ifurl);
	preg_match_all("~pdownload = '(.*?)'~", $softInfo, $segment);
	if(empty($segment[1][0])){
		preg_match_all("~ispostdowns = '(.*?)'~", $softInfo, $segment);
	}
	if(empty($segment[1][0])){
		preg_match_all("~'sign':'(.*?)'~", $softInfo, $segment);
	}
	$post_data = array(
		"action" => 'downprocess',
		"signs"=>"?ctdf",
		"sign" => $segment[1][0],
	);
	$softInfo = MloocCurlPost($post_data, "https://www.lanzoux.com/ajaxm.php", $ifurl);
}

$softInfo = json_decode($softInfo, true);
if ($softInfo['zt'] != 1) {
    die(
    json_encode(
        array(
            'code' => 400,
            'msg' => $softInfo['inf']
        ),480)
    );
}

$downUrl1 = $softInfo['dom'] . '/file/' . $softInfo['url'];
//解析最终直链地址
//$downUrl2 = MloocCurlHead($downUrl1,"http://developer.store.pujirc.com",$UserAgent,"down_ip=1; expires=Sat, 16-Nov-2019 11:42:54 GMT; path=/; domain=.baidupan.com");

if($downUrl2 == ""){
	$downUrl = $downUrl1;
}else{
	$downUrl = $downUrl2;
}
if ($type != "down") {
    die(
    json_encode(
            array(
                'code' => 200,
                'msg' => '直链解析成功！',
                'name' => isset($softName[1]) ? $softName[1] : "",
                'filesize' => isset($softFilesize[1]) ? str_replace(['文件大小：','|'], '',$softFilesize[1]) : "",
                'downurl' => $downUrl ,
                'text' => [
                'msg' => '此接口只支持蓝奏云网盘解析'
                ,'copyright'  => '五行资源博客wep.vipyshy.com'
                ,'time'=>'当前解析时间为：'.date('Y-m-d H:i:s',time())]
            ),480)
    );
} else {
    header("Location:$downUrl");
    die;
}
function MloocCurlGetDownUrl($url)
{
    $header = get_headers($url,1);
    if(isset($header['Location'])){
		return $header['Location'];
	}
	return "";
}

function MloocCurlGet($url = '', $UserAgent = '')
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    if ($UserAgent != "") {
        curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.Rand_IP(), 'CLIENT-IP:'.Rand_IP()));
    #关闭SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    #返回数据不直接显示
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function MloocCurlPost($post_data = '', $url = '', $ifurl = '', $UserAgent = '')
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    if ($ifurl != '') {
        curl_setopt($curl, CURLOPT_REFERER, $ifurl);
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.Rand_IP(), 'CLIENT-IP:'.Rand_IP()));
    #关闭SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    #返回数据不直接显示
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

//直链解析函数
function MloocCurlHead($url,$guise,$UserAgent,$cookie){
$headers = array(
	'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
	'Accept-Encoding: gzip, deflate',
	'Accept-Language: zh-CN,zh;q=0.9',
	'Cache-Control: no-cache',
	'Connection: keep-alive',
	'Pragma: no-cache',
	'Upgrade-Insecure-Requests: 1',
	'User-Agent: '.$UserAgent
);
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HTTPHEADER,$headers);
curl_setopt($curl, CURLOPT_REFERER, $guise);
curl_setopt($curl, CURLOPT_COOKIE , $cookie);
curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
curl_setopt($curl, CURLOPT_NOBODY, 0);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
$data = curl_exec($curl);
$url=curl_getinfo($curl);
curl_close($curl);
return $url["redirect_url"];
}

function Rand_IP(){

    $ip2id = round(rand(600000, 2550000) / 10000);
    $ip3id = round(rand(600000, 2550000) / 10000);
    $ip4id = round(rand(600000, 2550000) / 10000);
    $arr_1 = array("218","218","66","66","218","218","60","60","202","204","66","66","66","59","61","60","222","221","66","59","60","60","66","218","218","62","63","64","66","66","122","211");
    $randarr= mt_rand(0,count($arr_1)-1);
    $ip1id = $arr_1[$randarr];
    return $ip1id.".".$ip2id.".".$ip3id.".".$ip4id;
}
?>
