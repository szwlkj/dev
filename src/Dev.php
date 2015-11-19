<?php namespace WLkj\Dev;

use App\Pay;
use App\Commodity_stock;

class Dev{

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
       
	//============================================================================
    // 打印变量的信息
	public function dump($value){
		echo '<pre>';
		var_dump($value);
		echo '</pre>';
	}
	
	//============================================================================
    // 打印数组的信息
	public function dumpArray($arrs){
		echo "<table>";
		foreach ($arrs as $k=>$value) {
			echo "<tr><td>{$k}</td><td>{$value}</td></tr>";
			// echo "{$k}={$value}<br>";
		} 
		echo "</table>";
	}
	
	//============================================================================
    // 显示金额	
	public function showAmt($amt)
	{
		return number_format($amt, 2,".","");
	}
	
}
