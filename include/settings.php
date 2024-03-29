<?php
date_default_timezone_set('America/New_York');
$totalMembers = 0;
$VIPMembers = 0;
$SVIPMembers = 0;

$loginCookie = $_COOKIE['LoginID'];
$passwordCookie = $_COOKIE['Password'];
$userID = -1;
$userIP = $_SERVER['REMOTE_ADDR'];

if(isset($_SERVER["HTTP_CF_CONNECTING_IP"])){
	$userIP = $_SERVER["HTTP_CF_CONNECTING_IP"];
}


$maxIPLogcount = 3;

$subscriptionExpires = date('Y-m-d H:i:s');
$userLastLogin = date('Y-m-d H:i:s');
$date = date('Y-m-d H:i:s');
$subscriptionString = "Expired";
$defaultWorld = "Scania";
$subscriptionValue = 0;
$adminStatus = 0;
$flagged = "";
$maint = 0;
$refreshEnabled = 0;

$lastFreeOwlRefresh = date('Y-m-d H:i:s');
$lastFreeOwlRefresh2 = date('Y-m-d H:i:s');

$con = mysql_connect($host['host'], $host['userName'], $host['passWord']);
if (!$con)
{
  	echo "";
}
else
{
	$conn2 = mysql_select_db($host['databaseName'], $con); 
	if($conn2)
	{	
		$query = mysql_query("SELECT * FROM administrative") or die(mysql_error());
		$data = mysql_fetch_array($query);
		$lastRefresh = strtotime($data['lastOwlRefresh']);

		$refreshEnabled = $data['enableRefresh'];
		$lastFreeOwlRefresh = $data['lastOwlRefresh'];
		$date = date('Y-m-d H:i:s');
		
		$qry1 = mysql_query("SELECT COUNT(*) AS Total, 
					SUM(IF(Admin = '0' AND Rank <= '1', 1, 0)) AS Members,
					SUM(IF(Expires >= '$date' AND Expires != '0000-00-00 00:00:00', 1, 0)) AS Paid,
					SUM(IF(Expires = '0000-00-00 00:00:00' AND Admin = '0' AND Rank <= '1', 1, 0)) AS Perm
 		FROM accounts");
		$qry= mysql_fetch_array($qry1);
		$totalMembers = $qry[Members];
		$VIPMembers = $qry[Paid];
		$SVIPMembers = $qry[Perm];

		//$query1 = mysql_query("SELECT * FROM accounts WHERE Admin = '0'");
		//$totalMembers = mysql_num_rows($query1);
		//$query2 = mysql_query("SELECT * FROM accounts WHERE (Expires >= '$date' AND Expires != '0000-00-00 00:00:00') AND Admin = '0'");
		//$VIPMembers = mysql_num_rows($query2);
		//$query3 = mysql_query("SELECT * FROM accounts WHERE Expires = '0000-00-00 00:00:00' AND Admin = '0' AND Rank <= '1'");
		//$SVIPMembers = mysql_num_rows($query3);

		if(isset($loginCookie))
		{
			$query = mysql_query("SELECT * FROM accounts WHERE LoginID = '$loginCookie'") or die(mysql_error());
			$data = mysql_fetch_array($query);
			if($data['MD5Password'] != $passwordCookie)
			{
				setcookie("LoginID", '', time()-3600, '/');
				setcookie("Password", '', time()-3600, '/');
				echo "<meta http-equiv='refresh' content='=0;index.php' />";
			}	
			$subscriptionExpires = $data['Expires'];
			$userLastLogin = $data['lastLogin'];
			$adminStatus = $data['Admin'];
			$defaultWorld = $data['World'];
			$userID = $data['ID'];
			
			//$query = mysql_query("SELECT *, COUNT(DISTINCT IP) as num FROM LoginLogs WHERE LoginID = '$loginCookie' AND Verify = '2'") or die(mysql_error());
			//$data = mysql_fetch_array($query);
			//if($data['num'] >= $maxIPLogcount)
			//{
				//$flagged = "<center><font color='red'>Your account has been flagged by the system for account sharing! This will be or is currently undergoing review by the system administrator. When the review has completed, this message will disappear.</font></center><br><br>";

			//}
			if($subscriptionExpires == "0000-00-00 00:00:00")
			{		
				$subscriptionValue = 1;
				$subscriptionString = "<font color='green'>Lifetime Subscription</font>";	
			}
			else
			{
				if($subscriptionExpires > $date)
				{
					$subscriptionValue = 1;
					$timeDifference = time_elapsed_string($subscriptionExpires, true);
					$subscriptionString = "<font color='green'>Expires in $timeDifference</font>";
					
					$now = strtotime(date('Y-m-d H:i:s'));
					$lastFreeOwlRefresh2 = strtotime(date("Y-m-d H:i:s", strtotime('+7 hours', $lastRefresh)));
					if($lastFreeOwlRefresh2 < $now)
					{
						$p = isset($_GET['p']) ? $_GET['p'] : "";
						if($p != "fmEcho" && $p != "fmEchoFree" && $refreshEnabled == 1){
							echo "REFRESHED";
							mysql_query("UPDATE administrative SET lastOwlRefresh = '$date'");
							$src = "FM/FMExport/";
							$dst = "FM/FMExport2/";
							recursiveDelete($dst);
							recurse_copy($src, $dst);
							header("Location: /?p=$p");
						}
					}
				}
				else
				{
					$subscriptionValue = 0;
					$subscriptionString = "<font color='red'>Expired</font>";
				}
			}
		}else{
			$loginCookie = "Guest";
		}
	}
	else
	{
		echo "";
	}
}


function recursiveDelete($str){
        if(is_file($str)){
            return @unlink($str);
        }
        elseif(is_dir($str)){
            $scan = glob(rtrim($str,'/').'/*');
            foreach($scan as $index=>$path){
                recursiveDelete($path);
            }
            return @rmdir($str);
        }
    }


function recurse_copy($src,$dst) 
{
    
	$dir = opendir($src); 
    
	@mkdir($dst); 
    
	while(false !== ( $file = readdir($dir)) ) 
	{ 
        
		if (( $file != '.' ) && ( $file != '..' )) 
		{ 
            
			if ( is_dir($src . '/' . $file) ) 
			{ 
                
				recurse_copy($src . '/' . $file,$dst . '/' . $file); 
            
			} 
            
			else 
			{ 
                
				copy($src . '/' . $file,$dst . '/' . $file); 
            
			} 
        
		} 
    
	} 
    
	closedir($dir); 

}



function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . '.' : '';
}


?>