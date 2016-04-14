<?php
if(!empty($_SERVER['HTTP_CLIENT_IP'])){
	$myip = $_SERVER['HTTP_CLIENT_IP'];
}else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
	$myip = $_SERVER['HTTP_X_FORWARDED_FOR'];
}else{
	$myip= $_SERVER['REMOTE_ADDR'];
}

if ($_POST["ip"] == "") {
	if (preg_match("/140.118.\S*/",$myip))
		$ip = $myip;
}else
	$ip = $_POST["ip"];
?>
<!DOCTYPE html>
<html>
	<head>
		<title>  NTUST Flowstatistical  </title>
		<script src="src/jquery.min.js"></script>
		<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
		<style>
			.input-group {  
							margin:0px auto;
							width:360px;}
			#jqmeter-container { position:relative;
						margin:0px auto;
						height:40px;		
						width:960px; }
			.texttotal {position:relative;
						text-align:center;}
			.page-header{text-align:center;}
		</style>
		<script src="src/jqmeter.min.js"></script>
		
	</head>
	<body>
		<div class="page-header">
			<h1>118 網路流量統計</h1>
		</div>
		<form action="flow.php" method="post">
			<div class="input-group input-group-lg">
				<span class="input-group-addon">IP:</span>			
				<input type="text" class="form-control" name="ip" value="<?php echo $ip;?>">
				<span class="input-group-btn">
        		<button class="btn btn-default" type="submit">Submit!</button>
      			</span>
			</div>
		</form>

<?php

function my_curl($url,$post)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST,1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST,  'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	$result = curl_exec($ch);
	curl_close ($ch);
	return $result;
}


$url = 'http://network.ntust.edu.tw/flowstatistical.aspx';
$contents = file_get_contents($url);

preg_match_all('#<input[^>]*>#i', $contents, $match);

preg_match("/value=\"(\S*)\" /",$match[0][0], $viewstate_t);
preg_match("/value=\"(\S*)\" /",$match[0][1], $viewstategenerator_t);
preg_match("/value=\"(\S*)\" /",$match[0][2], $eventvaildation_t);

$viewstate = $viewstate_t[1];
$viewstategenerator = $viewstategenerator_t[1];
$eventcaildation = $eventvaildation_t[1];

$postBody = array(
	'__VIEWSTATE' => $viewstate,
	'__VIEWSTATEGENERATOR' => $viewstategenerator,
	'__EVENTVALIDATION' => $eventcaildation,
	'ctl00$ContentPlaceHolder1$txtip' => $ip,
	'ctl00$ContentPlaceHolder1$dlmonth' => date(n, time()),
	'ctl00$ContentPlaceHolder1$dlday' => date(j, time()),
	'ctl00$ContentPlaceHolder1$dlcunit' => '1048576'
);

$getBody = my_curl($url, $postBody);

preg_match_all("#(\d?).?(\d+)\s\(M\)#i", $getBody, $flowt);
for ($i = 0; $i < 435; $i++) {
	if ($i % 3 == 2) {
		$table_t[] = $flowt[1][$i]*1000+$flowt[2][$i];
	}
	else if($i % 3 == 0) {
		$table_d[] = $flowt[1][$i]*1000+$flowt[2][$i];
	}
	else {	
		$table_u[] = $flowt[1][$i]*1000+$flowt[2][$i];
	}
}
$maxFlow = max($table_t);
$maxFlow_d = max($table_d);
$maxFlow_u = max($table_u);

$flowPercent = ($maxFlow/5096*100 <= 1)?ceil($maxFlow / 5096 * 100):floor($maxFlow / 5096 * 100);

if ($flowPercent < 20) {
	$stat = "'#7FFF00'";
}
else if ($flowPercent < 70) {
	$stat = "'#00BFFF'";
}
else if ($flowPercent < 90) {
	$stat = "'#FFD700'";
}
else {	
	$stat = "'#EE0000'";
}
?>
		<div id="jqmeter-container"></div>
		<script>
		$('#jqmeter-container').jQMeter({
			goal:'100',
			raised:'<?php echo $flowPercent;?>',
			orientation:'horizontal',
			barColor:<?php echo $stat;?>,
    		width:'960px',
			height:'50px'
		});
		</script>

		<div class="texttotal">
		<h2>總計：<?php echo $maxFlow;?> MB</h2>
		<p>下載：<?php echo $maxFlow_d;?> MB</p>
		<p>上傳：<?php echo $maxFlow_u;?> MB</p>
		</div>
	</body>
</html>
