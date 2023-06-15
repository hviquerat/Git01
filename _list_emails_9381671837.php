<html>
<head>
<style>
body { font-family: Arial; font-size: 11px; padding: 20px; }
table {font-size:11px; border-collapse: collapse;}
td { border:1px solid #ccc ; padding: 3px; vertical-align:top; }
tr:hover { background: #eee; }
</style>
</head>
<body>
<?php
        error_reporting(-1);
        ini_set('display_errors',1);

	require('wp-config.php');

        $psa = mysqli_connect('localhost', 'sdis_cron', 'cron2017**', 'psa');
        $sdi = mysqli_connect('localhost', 'sdis_cron', 'cron2017**', 'admin_sdisglandserine');
	
	
	
	$groups = array('680dapplus','680ari','680dapz','680dps','680serine','chefinter','chlourd','em','emserine','formateur' ,'codir','administration','mat','sac','mutation');




	$map = [];
        $res = $sdi->query('SELECT * FROM '.$table_prefix.'users u');
        while($row = $res->fetch_assoc()){
        	$map[$row['user_login']] = $row['display_name'];
        }


	$rs = $psa->query('SELECT m.* FROM mail m JOIN domains d ON m.dom_id=d.id AND d.name="sdisglandserine.ch"');
	echo '<table>';
	echo '<tr><th>Addresses</th><th>Aliases</th><th>Redirections</th><th>Mailbox</th></tr>';

	while($row = $rs->fetch_assoc()){

		echo '<tr>';
			echo '<td style="background:'.(in_array($row['mail_name'],$groups)?'#e0b4ed':'white').';">'.$row['mail_name'].'@sdisglandserine.ch</td>';
			echo '<td>';
				$aliases = $psa->query('SELECT * FROM mail_aliases WHERE mn_id="'.$row['id'].'"');
				while($al = $aliases->fetch_assoc()){
					echo $al['alias']."<br />";
				}
			echo '</td>';
			echo '<td>';
					if($row['mail_group'] == 'true'){
                                $redir = $psa->query('SELECT * FROM mail_redir WHERE mn_id="'.$row['id'].'"');
                                while($re = $redir->fetch_assoc()){
                                        echo $re['address'];
										if(preg_match("/^[0-9]+$/", str_replace('@sdisglandserine.ch','',$re['address'])))
											echo "&nbsp;&nbsp;&nbsp;&nbsp;(".$map[str_replace('@sdisglandserine.ch','',$re['address'])].")";
										echo "<br />";
								}
                    }
			echo '</td>';
			echo '<td style="text-align:center;">';
				if($row['postbox'] == 'true'){
					echo "X";
				}
			echo '</td>';
		echo '</tr>';
	}
	echo '</table>';

?>
</body>
</html>
