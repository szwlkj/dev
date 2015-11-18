<?php namespace WLkj\dev;

use App\Pay;
use App\Commodity_stock;

class Dev{


    //============================================================================
    // 获取微信token
    public function getWxToken()
    {
    	return file_get_contents(env('CACHE').'/api/cache/wx-token');
    }
    
    
    //============================================================================
    // 获取微信 ticket
    public function getWxTicket($token='')
    {
    	return file_get_contents(env('CACHE').'/api/cache/wx-ticket/'.$token);
    }
    
    //============================================================================
    // 获取微信js配置信息
    public function getWxJsData($token='')
    {
        $wx_data	= [ 'noncestr'      => str_random(15),
                        'jsapi_ticket'  => Dev::getWxTicket($token),
                        'timestamp'     => time(),
                        'url'           => \URL::to($_SERVER['REQUEST_URI'])];
        ksort($wx_data);
        $temp = [];
        foreach($wx_data as $k=>$value) $temp[] = "{$k}={$value}";
        $wx_data['signature'] = sha1(implode('&',$temp));
    	return $wx_data;
    }
    
    //============================================================================
    // 生成Get请求的Url
    // 参数：baseUrl - 基本URL
    //		 params  - 参数数组
    public function makeUrl($baseUrl,$params)
    {
		$i      = 0;
		foreach($params as $key=>$val){
			if( $i == 0)  	$baseUrl .= "?{$key}={$val}";
			else            $baseUrl .= "&{$key}={$val}";
			$i++;
		}
		return $baseUrl;
    }
        
	// 显示用户的等级描述
	public function showUserDesc($val)
	{
		switch ( $val ) {
            case 0:
                $val = '青铜会员';
                break;
            case 1:
                $val = '白银会员';
                break;
            case 2:
                $val = '黄金会员';
                break;
            case 3:
                $val = '铂金会员';
                break;
            case 4:
                $val = '钻石会员';
                break;
            case 5:
                $val = '最强王者';
                break;
        }

        return $val;
	}

	
	//==================================================================================================================================================================
	// 支付宝-退款回调函数
	// 返回数组
	public function alipayCancelNotify()
	{	
		require_once("apliy/alipay.config.php");
		require_once("apliy/lib/alipay_notify.class.php");	
				
		$ret = ['code'=> -1 ];				
		// 计算得出通知验证结果
		$alipayNotify = new \AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
		if($verify_result) {//验证成功
			$batch_no 			= $_POST['batch_no'];
			$success_num 		= $_POST['success_num'];
			$result_details 	= $_POST['result_details'];
			$ret = ['code'=> 0 , 'bak_id' => $batch_no ];
		}
		return $ret;
	}
	
	//==================================================================================================================================================================
	// 支付宝-即时到账支付回调函数
	public function alipayNotify()
	{		
		require_once("apliy/alipay.config.php");
		require_once("apliy/lib/alipay_notify.class.php");	
		$ret = [ 'code' => -1, 'state' => '0' ];
		
		// 计算得出通知验证结果
		$alipayNotify = new \AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
		if($verify_result) {//验证成功
			$out_trade_no = $_REQUEST['out_trade_no'];		// 商户订单号
			$trade_no     = $_REQUEST['trade_no'];			// 支付宝交易号
			$trade_status = $_REQUEST['trade_status'];		// 交易状态

			if($_POST['trade_status'] 		== 'TRADE_SUCCESS') {			// 支付宝即时到账买家已付款
				$sta="6";			
			}else if($_POST['trade_status'] == 'WAIT_BUYER_PAY') {    		// 该判断表示买家已在支付宝交易管理中产生了交易记录，但没有付款
				$sta="2";
			}else if($_POST['trade_status'] == 'WAIT_SELLER_SEND_GOODS') {	// 该判断表示买家已在支付宝交易管理中产生了交易记录且付款成功，但卖家没有发货
				$sta="4";
			}else if($_POST['trade_status'] == 'WAIT_BUYER_CONFIRM_GOODS') {// 该判断表示卖家已经发了货，但买家还没有做确认收货的操作
				$sta="5";
			}else if($_POST['trade_status'] == 'TRADE_FINISHED') {			// 该判断表示买家已经确认收货，这笔交易完成
				$sta="7";
			}else {			//其他状态判断
				$sta="13";
			}
			$ret = [ 'code'=> 0 , 'state' => $sta , 'other_id'=> $trade_no ];
		}
		return $ret;
	}
	
	//==================================================================================================================================================================
	// 微信支付-回调函数
	public function wxNotify()
	{	
		// 1.微信异步通知
		require_once ("WxPay/notify.php");
		$notify = new \PayNotifyCallBack();
		$notify->Handle(false);
		return '';
	}
	
	
	//==================================================================================================================================================================
	// 百度钱包-退款回调函数
	// 返回数组
	public function baiduCancelNotify()
	{	
		if (!defined("BFB_SDK_ROOT")) define("BFB_SDK_ROOT", dirname(__FILE__) . DIRECTORY_SEPARATOR.'Baidu'.DIRECTORY_SEPARATOR);
		require_once(BFB_SDK_ROOT . 'bdpay_sdk.php');				
		
		$ret 		= ['code'=> -1 ];				
		$bdpay_sdk 	= new \bdpay_sdk();
				
		if (true === $bdpay_sdk->check_bfb_refund_result_notify()) {
			$ret = [ 'code' => 0, 'state' => 12];
		}


		// 向百度钱包发起回执
		$bdpay_sdk->notify_bfb();
		return $ret;
	}
	
	//==================================================================================================================================================================
    // 百度钱包回调函数
    // 返回: 数组
    public function baiduNotify()
    {
		if (!defined("BFB_SDK_ROOT")) define("BFB_SDK_ROOT", dirname(__FILE__) . DIRECTORY_SEPARATOR.'Baidu'.DIRECTORY_SEPARATOR);
		require_once(BFB_SDK_ROOT . 'bfb_sdk.php');		
		
		$ret = [ 'code' => -1, 'state' => '0' ];
				
		$bfb_sdk = new \bfb_sdk();
		$bfb_sdk->log(sprintf('get the notify from baifubao, the request is [%s]', print_r($_GET, true)));

		if (true === $bfb_sdk->check_bfb_pay_result_notify()){
			$ret = [ 'code' => 0, 'state' => 6 ];
		}
		
		// 向百付宝发起回执
		$bfb_sdk->notify_bfb();
		return $ret;
    }
    
	//==================================================================================================================================================================
	// 支付宝-即时到账支付
	// 返回：支付参数
	public function alipayPay($pay)
	{	
		// 1.获取订单信息
		$home	= env('HOME','http://www.94song.com');
			
		// 2. 导入支付宝的库
		require_once("apliy/alipay.config.php");
		require_once("apliy/lib/alipay_core.function.php");
		require_once("apliy/lib/alipay_md5.function.php");
			
		// 3. 从数据获取订单号
		$order_url = $home;
		$desc = empty($pay->desc)?"94送订单支付":$pay->desc;

		/**************************请求参数**************************/
        $payment_type		= "1";										 		// 支付类型
        $notify_url 		= "{$home}/api/pay/alipay/".$pay->id;				// 服务器异步通知页面路径
		$return_url 		= "{$home}";										// 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数
        $out_trade_no 		= $pay->id;					  						// 商户订单号
        $subject			= $desc;											// 订单名称
        $total_fee 			= $pay->amt;										// 付款金额
        $body 				= "支付94送订单";									// 订单描述
        $show_url 			= $order_url;										// 商品展示地址
        $anti_phishing_key 	= "";												// 防钓鱼时间戳
        $exter_invoke_ip 	= "";												// 非局域网的外网IP地址，如：221.0.0.1
		/************************************************************/
		
		//构造要请求的参数数组，无需改动
		$parameter = [
				"service" 			=> "create_direct_pay_by_user",
				"partner" 			=> trim($alipay_config['partner']),
				"seller_email" 		=> trim($alipay_config['seller_email']),
				"payment_type"		=> $payment_type,
				"notify_url"		=> $notify_url,
				"return_url"		=> $return_url,
				"out_trade_no"		=> $out_trade_no,
				"subject"			=> $subject,
				"total_fee"			=> $total_fee,
				"body"				=> $body,
				"show_url"			=> $show_url,
				"anti_phishing_key"	=> $anti_phishing_key,
				"exter_invoke_ip"	=> $exter_invoke_ip,
				"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))];

		//建立请求			
		$para_filter 			= paraFilter($parameter);			// 除去待签名参数数组中的空值和签名参数
		$para_sort 				= argSort($para_filter); 			// 对待签名参数数组排序
		$prestr 				= createLinkstring($para_sort);		// 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$mysign 				= md5Sign($prestr, $alipay_config['key']);
		$para_sort['sign'] 		= $mysign;
		$para_sort['sign_type'] = 'MD5';
		return $para_sort;
	}
	
	//==================================================================================================================================================================
	// 支付宝-退款
	// 返回：退款参数
	public function alipayCancel($pay_id,$other_id,$amt)
	{	
		// 1.获取订单信息
		$home	= env('HOME');
			
		// 2. 导入支付宝的库
		require_once("apliy/alipay.config.php");
		require_once("apliy/lib/alipay_core.function.php");
		require_once("apliy/lib/alipay_md5.function.php");
		require_once("apliy/lib/alipay_submit.class.php");
		
		/**************************请求参数**************************/			
   	 	$notify_url 	= "{$home}/api/pay/alipay-cancel/".$pay_id;				// 服务器异步通知页面路径
    	$seller_email 	= "www94song@qq.com";									// 卖家支付宝帐户
    	$refund_date 	= date('Y-m-d H:i:s',time());							// 退款当天日期
    	$batch_no 		= date('Ymd',time()).'000'.$pay_id;						// 批次号
    	$batch_num 		= 1;													// 退款笔数
    	$detail_data 	= $other_id."^".$amt."^协商退款";
		/************************************************************/
		
		//构造要请求的参数数组，无需改动
		$parameter = [
				"service" 		=> "refund_fastpay_by_platform_pwd",
				"partner" 		=> trim($alipay_config['partner']),
				"notify_url"	=> $notify_url,
				"seller_email"	=> $seller_email,
				"refund_date"	=> $refund_date,
				"batch_no"		=> $batch_no,
				"batch_num"		=> $batch_num,
				"detail_data"	=> $detail_data,
				"_input_charset"=> trim(strtolower($alipay_config['input_charset']))];

		$para_filter 			= paraFilter($parameter);			// 除去待签名参数数组中的空值和签名参数
		$para_sort 				= argSort($para_filter); 			// 对待签名参数数组排序
		$prestr 				= createLinkstring($para_sort);		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$mysign 				= md5Sign($prestr, $alipay_config['key']);
		$para_sort['sign'] 		= $mysign;
		$para_sort['sign_type'] = 'MD5';
		return $para_sort;			
	}

	//==================================================================================================================================================================
	// 微信-扫码支付
	// 返回：支付URL
	public function wxPay($pay)
	{
		require_once("WxPay/lib/WxPay.NativePay.php");
		$desc = empty($pay->desc)?"94送微信支付":$pay->desc;
		$notify = new \NativePay();
		$url1 = $notify->GetPrePayUrl("123456789");
		$input = new \WxPayUnifiedOrder();
		$input->SetBody($desc);
		$input->SetAttach("支付订单");
		$input->SetOut_trade_no($pay->id);							// 订单号
		$input->SetTotal_fee($pay->amt * 100);						// 金额（单位为分）
		$input->SetTime_start(date("YmdHis"));							
		$input->SetTime_expire(date("YmdHis", time() + 7200));
		$input->SetGoods_tag("订单");
		$input->SetNotify_url(env('home')."/api/pay/wx/".$pay->id);
		$input->SetTrade_type("NATIVE");
		$input->SetProduct_id("123456789");
		$result = $notify->GetPayUrl($input);
		return  empty($result["code_url"])?'':$result["code_url"];
	}

	//==================================================================================================================================================================
	// 微信-退款	
	// 返回数组
	public function wxCancel($pay_id,$other_id,$amt)
	{	
		$ret= ['code' => -1 ];
		// 1.获取订单信息
		$home	= env('HOME','http://www.94song.com');
		require_once "WxPay/lib/WxPay.Api.php";
		$input 			= new \WxPayRefund();
		$input->SetTransaction_id($other_id);
		$input->SetTotal_fee($amt * 100);
		$input->SetRefund_fee($amt * 100);
		
    	//$input->SetOut_refund_no(\WxPayConfig::MCHID.date("YmdHis"));
    	$input->SetOut_refund_no($pay_id);
    	$input->SetOp_user_id(\WxPayConfig::MCHID);
    	$data = \WxPayApi::refund($input);
    	if( $data['result_code'] == "SUCCESS" && $data['return_code'] == "SUCCESS"){
    		$ret 			= $data;
    		$ret['code'] 	= 0;
		}
		return $ret;
	}
	
	//============================================================================
    // 百度钱包支付接口
    // 返回：支付URL
    public function payByBaidu($amt,$order_id,$good_name='购买商品')
    {
    	if (!defined("BFB_SDK_ROOT")) define("BFB_SDK_ROOT", dirname(__FILE__) . DIRECTORY_SEPARATOR.'Baidu'.DIRECTORY_SEPARATOR);
    	
		require_once(BFB_SDK_ROOT . 'bfb_pay.cfg.php');
		require_once(BFB_SDK_ROOT . 'bfb_sdk.php');
		$ret				= '';
		$bfb_sdk 			= new \bfb_sdk();
		$order_create_time 	= date("YmdHis");
		$expire_time 		= date('YmdHis', strtotime('+2 day'));
		$home				= env('HOME');

		// 字符编码转换，百付宝默认的编码是GBK，商户网页的编码如果不是，请转码。涉及到中文的字段请参见接口文档
		$good_name 			= iconv("UTF-8", "GBK", urldecode($good_name));
		$params 			= [
				'service_code' 		=> \sp_conf::BFB_PAY_INTERFACE_SERVICE_ID,
				'sp_no' 			=> \sp_conf::SP_NO,
				'order_create_time' => $order_create_time,
				'order_no' 			=> 'JCBTEST'.$order_id,
				'goods_category' 	=> '',
				'goods_name' 		=> $good_name,
				'goods_desc' 		=> '',
				'goods_url' 		=> '',
				'unit_amount' 		=> '',
				'unit_count' 		=> '',
				'transport_amount' 	=> '',
				'total_amount' 		=> intval($amt * 100),
				'currency' 			=> \sp_conf::BFB_INTERFACE_CURRENTCY,
				'buyer_sp_username' => '',
				'return_url' 		=> $home.'/api/pay/baidu/'.$order_id,
				'page_url' 			=> $home,
				'pay_type' 			=> 2,
				'bank_no' 			=> '',
				'expire_time' 		=> $expire_time,
				'input_charset' 	=> \sp_conf::BFB_INTERFACE_ENCODING,
				'version' 			=> \sp_conf::BFB_INTERFACE_VERSION,
				'sign_method' 		=> \sp_conf::SIGN_METHOD_MD5,
				'extra'				=> ''
		];
		$order_url = $bfb_sdk->create_baifubao_pay_order_url($params,\sp_conf::BFB_PAY_DIRECT_NO_LOGIN_URL);
		if(false === $order_url){
			$bfb_sdk->log('create the url for baifubao pay interface failed');
		}else {
			$bfb_sdk->log(sprintf('create the url for baifubao pay interface success, [URL: %s]', $order_url));
			$ret = $order_url;
		}
		return $ret;
    }
    
    //==================================================================================================================================================================
	// 百度钱包-退款	
	// 返回数组
	public function baiduCancel($pay_id,$amt)
    {
		$home				= env('HOME');
		$return_url 		= $home."/api/pay/baidu-cancel/".$pay_id; 					// 服务器异步通知退款结果地址:
		$sp_refund_no 		= date("YmdHis"). sprintf ( '%06d', rand(0, 999999));		
		$cashback_time		= date("YmdHis");		
    	$params = [
                'service_code'      => 2,                   // 服务编号,取值为2
                'input_charset'     => 1,                   // 参数字符编码集,1-GBK
                'sign_method'       => 1,                   // 签名方式: 1 - MD5
                'output_type'       => 1,                   // 响应数据的格式，1-XML
                'output_charset'    => 1,                   // 响应数据的编码，1-GBK
                'return_url'        => $return_url,         //
                'return_method'		=> 1,					// 后台通知请求方式: 1 - GET
                'version'           => 2,                 	// 版本号，填写2
                'sp_no'             => '1000132863',        // 商户ID
                'order_no'          => 'JCBTEST'.$pay_id,  	// 交易单号
                'cashback_amount'   => intval($amt * 100),  // 退款金额
                'cashback_time'     => $cashback_time,    	// 退款请求时间
                'currency'          => 1,
                'sp_refund_no'      => $sp_refund_no,
                'refund_type'		=> 2];					// 退款类型: 1为退至钱包余额，2为原路退回
		$temp 			= $params;
		ksort($temp);
		$temp['key'] 	= "uayjAizcPaDrULjMGKifWNdQU7eC9zfC";
		$arr_temp 		= [];
		foreach ($temp as $key => $val){
			$arr_temp [] = $key . '=' . $val;
		}
		$params['sign']		= md5(implode('&', $arr_temp));
		return $params;
	}
	
	//==================================================================================================================================================================
	// 调整图片大小
	public function imgResize($file,$x,$y)
	{
		
		$img = \Image::make($_SERVER['DOCUMENT_ROOT'].$file);
		$targetPath = $_SERVER['DOCUMENT_ROOT']."/p/merch/";
		do{
			$main_name	= date('ymd_His'.'_'.rand(1000,9999));
			$new_name	= $main_name . '.jpg';
			$targetFile = rtrim($targetPath,'/') . '/' . $new_name;
		}while(file_exists($targetFile));
		$img->resize($x,$y);
		$img->save($targetFile);
		return "/p/merch/$new_name";
	}
	
	public function showUrl($url,$errorCorrectionLevel='M',$matrixPointSize=3)
	{
		//引入phpqrcode库文件
		include_once('phpqrcode/phpqrcode.php'); 
		$file	 	= "/temp/".date('ymd_His'.'_'.rand(1000,9999)). ".png";
		$file_name	= $_SERVER['DOCUMENT_ROOT'].$file;
		\QRcode::png($url, $file_name, $errorCorrectionLevel, $matrixPointSize, 2); //创建一个二维码文件 
		return $file;
	}
	
	public function dump($value){
		echo '<pre>';
		var_dump($value);
		echo '</pre>';
	}
	
	public function dumpArray($arrs){
		echo "<table>";
		foreach ($arrs as $k=>$value) {
			echo "<tr><td>{$k}</td><td>{$value}</td></tr>";
			// echo "{$k}={$value}<br>";
		} 
		echo "</table>";
	}
	
	
	public function showAmt($amt)
	{
		return number_format($amt, 2,".","");
	}
	
	public function showAmtEx($amt)
	{  
		$num=number_format($amt / 10000,1,".","");
		$num=round($num,1);
		return "{$num}万";
	}	

	public function id($id)
	{
		return "$id";
	}
	
	public function id_bak($id)
	{
		return "?id=$id";
	}
	
	
	public function getId($id)
	{
		if( $id == "1" ) return $_GET['id'];
		$new_id  = $id;
		return $new_id;
	}
	
	
	public function pic($pre,$name,$value)
	{
		$host = env('RES_IMG');
		$desc = $pre."_".$name;
		
		
		$timestamp 	= time();
		$token		= md5('unique_salt' . $timestamp);
		
		
		$vals = <<<EOF
	<input id='{$desc}_input'  name='{$name}' 	type='hidden' value='$value' >
    <img   id='{$desc}_img'    src ='{$host}{$value}'>
    <input class='up_file' id='{$desc}_upload' name='{$name}' 	type='file' multiple=false>
            
            
	<script type="text/javascript">
	    $(function() {
	        $('#{$desc}_upload').uploadify({
	            'formData'			: {
	                'timestamp' 	: '{$timestamp}',
	                'token'     	: '{$token}'
	            },
	            'debug'	   			: false,
	            'auto'	   			: true,
	            'buttonText'		: '选择图片',
	            'swf'       		: '/lib/up/uploadify.swf',
	            'fileTypeDesc'		: '支持的格式：',
	            'fileTypeExts'		: '*.jpg;*.jpge;*.gif;*.png',
	            'fileSizeLimit' 	: '2MB',
	            'uploader'    		: '/lib/up/merch.php',
	            'hideButton'		:true,
	            'multi'				:false,
	            'onUploadSuccess'	: function(file,data,response){
	                $("#{$desc}_img").attr("src", data);
	                $("#{$desc}_input").attr("value", data);
	            }
	        });
	    });
	</script>
EOF;
		return $vals;
	}
	
	public function video($name,$value="")
	{
		$host = env('RES_IMG');
		$desc = $name;
		
		$timestamp 	= time();
		$token		= md5('unique_salt' . $timestamp);
		$vals = <<<EOF
	<input id='{$desc}_input'  name='{$name}' 	type='test' value='$value' >
	<input id='{$desc}_upload' name='{$name}' 	type='file' multiple=false>
	<script type="text/javascript">
	    $(function() {
	        $('#{$desc}_upload').uploadify({
	            'formData'			: {
	                'timestamp' 	: '{$timestamp}',
	                'token'     	: '{$token}'
	            },
	            'debug'	   			: false,
	            'auto'	   			: true,
	            'buttonText'		: '选择视频',
	            'swf'       		: '/lib/up/uploadify.swf',
	            'fileTypeDesc'		: '支持的格式：',
	            'fileTypeExts'		: '*.jpg;*.jpge;*.gif;*.png',
	            'fileSizeLimit' 	: '10MB',
	            'uploader'    		: '/lib/up/video.php',
	            'hideButton'		:true,
	            'multi'				:false,
	            'onUploadSuccess'	: function(file,data,response){
	                $("#{$desc}_img").attr("src", data);
	                $("#{$desc}_input").attr("value", data);
	            }
	        });
	    });
	</script>
EOF;
		return $vals;
	}
	
	
	public function  getUserCart($user_id)
	{		
		
        $sql = "SELECT u.id, u.count, u.standard1, u.standard2, m.id AS m_id, m.ww_id, m.title AS m_title, c.id AS c_id, c.pic, c.title, c.price, c.price_now, d.price AS d_price, d.old_price FROM 94s_user_cart AS u "
             . "LEFT JOIN 94S_commodity1 AS c ON u.commodity_id = c.id "
             . "LEFT JOIN 94s_commodity_detail AS d ON u.commodity_id = d.commodity_id AND u.standard1 = d.detail1 AND u.standard2 = d.detail2 "
             . "LEFT JOIN 94s_merch AS m ON c.merch_id = m.id "
             . "WHERE u.user_id = {$user_id}";

        $result = \DB::select($sql);
        $data = [];
        foreach ($result as $item) {
            $data[$item->m_id]['m_title'] = $item->m_title;
            $data[$item->m_id]['ww_id'] = $item->ww_id;
            $data[$item->m_id]['m_id'] = $item->m_id;

            $temp = [];
            $stock = Commodity_stock::where('commodity_id', $item->c_id)->where('merch_id', $item->m_id)->select('stock')->orderBy('id', 'ASC')->get();
            $temp['standard1'] = '';
            $temp['standard2'] = '';
            foreach ($stock as $key => $value) {
                $key = $key + 1;
                $temp['standard' . $key] = $value->stock;
            }


            $temp['id'] = $item->id;
            $temp['title'] = $item->title;
            $temp['pic'] = $item->pic;
            $temp['c_id'] = $item->c_id;
            if ( empty($item->d_price) ) {
                $temp['price'] = $item->price_now;
                $temp['old_price'] = $item->price;
            } else {
                $temp['price'] = $item->d_price;
                $temp['old_price'] = $item->old_price;
            }
            $temp['standard1_detail'] = $item->standard1;
            $temp['standard2_detail'] = $item->standard2;

            $temp['count'] = $item->count;
            $temp['total_price'] = $temp['price'] * $temp['count'];

            $data[$item->m_id]['child'][] = $temp;
        }
        return $data;
	}
}
