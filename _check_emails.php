<?php
	header('Content-Type: text/html; charset=iso-8859-1');
	error_reporting(-1);

	require('wp-config.php');
	
	ini_set('display_errors',1);
	$output = "";
	$sendmail = false;
	$psa = mysqli_connect('localhost', 'sdis_cron', 'cron2017**', 'psa');
	$sdi = mysqli_connect('localhost', 'sdis_cron', 'cron2017**', 'admin_sdisglandserine');

	$psa->set_charset("utf8");
	$sdi->set_charset("utf8");

	$nonUsersMailAddress = array("680dapplus","680ari","680dapz","680dps","680gland","680serine","680xyz","admindap","ari_excuse","chefinter","chlourd","concours","conseil","codir","cf","commissionfeu","com_feu","commission_feu","em","emserine","formateur","info.alarme","instruction","it","manifestation","mat","matdap","multimedia","muncipal_bassins","municipal_coinsins","municipal_begnins","municipal_burtigny","municipal_gland","municipal_levaud","municipal_vich","of_jour","of_nuit","ofmat","operationnel","pager","plan","postmaster","prosdis","sms","sortie","sport","subsistance","surveillance","technique","vehicule","webmaster","daisy.hamel","nicolas.barbay","solde","soldes","info","sdis","sac","administration","ingrid.menoud","mutation","epi");

	$res = $sdi->query('
		SELECT u.*, um.meta_value
		FROM '.$table_prefix.'users u
		JOIN '.$table_prefix.'usermeta um ON u.ID=um.user_id AND um.meta_key="ecadisData"
		WHERE user_email!=""
		AND user_login NOT IN ("13374","13376","19571","15750","41205","20652","999964");
	');
	$output .= "-- Email to create --\n\n";
	while($row = $res->fetch_assoc()){
		//$data = @preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $row['meta_value']);
		$data = $row['meta_value'];
		$um = unserialize($data);
		$rs = $psa->query('select count(*) as c from mail_redir WHERE address="'.$um['e_mail'].'"')->fetch_assoc()['c'];
		if($rs == 0 && $um['statut'] != "DM"){
			$output .= $row['display_name']."\t\t".$row['user_login']."\t\t".$um['e_mail']."\n";
			$sendmail = true;	
		}
		
	}
	

echo "<pre>";
    $allPleskMailBox = [];
	$rs = $psa->query('SELECT m.* FROM mail m JOIN domains d ON m.dom_id=d.id AND d.name="sdisglandserine.ch" AND m.userId = 0');
	while($rst = $rs->fetch_assoc()){
		if(!in_array($rst['mail_name'],$nonUsersMailAddress)){
			$allPleskMailBox[$rst['id']] = $rst['mail_name'];	
		}
	}
	$res = $sdi->query('
		SELECT u.*, um.meta_value
		FROM '.$table_prefix.'users u
		JOIN '.$table_prefix.'usermeta um ON u.ID=um.user_id AND um.meta_key="ecadisData"
		WHERE user_email!="" 
		AND user_login NOT IN ("13374","13376","19571","15750","41205","999964");
	
	');

	
	while($row = $res->fetch_assoc()){ 
		//$data = @preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $row['meta_value']);
		$data = $row['meta_value'];
		$um = unserialize($data); 
		$rq = $psa->query('SELECT * FROM mail_redir WHERE address ="'.$um['e_mail'].'"');
		$unseta = false;
		while($rqt = $rq->fetch_assoc()){
			unset($allPleskMailBox[$rqt['mn_id']]);
			$unseta = true;
		}
		if(!$unseta && $um['statut'] != "DM"){
			echo "No mailbox found with ".$um['e_mail']." (Status = ".$um['statut'].") as redir\n";
		}
	}
	if(count($allPleskMailBox) > 0){
		$output .= "\n\n-- Mailbox exists but nothing found in WP. Check WP and delete\n\n";
		$output .= print_r($allPleskMailBox,1);
		$sendmail = true;
	}


	$allRedirToSdisAddress = [];
	$rq = $psa->query('SELECT * FROM mail_redir WHERE address LIKE "%@sdisglandserine%"');
	while($rqt = $rq->fetch_assoc()){
		$allRedirToSdisAddress[] = $rqt['address'];
	}

	$allAlias = [];
	$rq = $psa->query('select * from mail_aliases ma JOIN mail m ON m.id=ma.mn_id JOIN domains d ON d.id=m.dom_id AND d.`name`="sdisglandserine.ch"');
	while($rqt = $rq->fetch_assoc()){
		$allAlias[] = $rqt['alias']."@sdisglandserine.ch";
	}

	$allMail = [];
	$rq = $psa->query('select * from mail m JOIN domains d ON d.id=m.dom_id AND d.`name`="sdisglandserine.ch"');
	while($rqt = $rq->fetch_assoc()){
		$allMail[] = $rqt['mail_name']."@sdisglandserine.ch";
	}
	
	$output .= "\n\n";
	foreach($allRedirToSdisAddress as $addr){
		if(!in_array($addr, $allAlias) && ! in_array($addr, $allMail)){
			$output .= "Redir to inexistant email : $addr (search in <a href='https://www.sdisglandserine.ch/_list_emails_9381671837.php'>List emails</a> if exists and delete if not\n";	
			$sendmail = true;
		}
	}



	
 

	$groups = array('680dapplus','680ari','680dapz','680dps','680serine','chefinter','chlourd','em','emserine','formateur' ,'codir','administration','mat','sac','mutation','conseil');
	$nips_in_mail_groups = array();
	foreach($groups as $g){
		$rs = $psa->query('SELECT m.* FROM mail m JOIN domains d ON m.dom_id=d.id AND d.name="sdisglandserine.ch" AND m.mail_name="'.$g.'"');
		$rst = $rs->fetch_assoc();
		$nips_in_mail_groups[$g] = array();
		$redir = $psa->query('SELECT * FROM mail_redir WHERE mn_id="'.$rst['id'].'"');
        	while($re = $redir->fetch_assoc()){
			$addr = str_replace('@sdisglandserine.ch','',$re['address']);
			if(is_numeric($addr))
				$nips_in_mail_groups[$g][] = $addr;
        }
	}

	$nips_in_wp_groups = array();
	foreach($groups as $g){
		$nips_in_wp_groups[$g] = array();
	}
	$names = [];
	$res = $sdi->query('SELECT * FROM '.$table_prefix.'users WHERE user_email!=""');
	while($row = $res->fetch_assoc()){
		$usermetaecadis = $sdi->query('SELECT * FROM '.$table_prefix.'usermeta WHERE user_id='.$row['ID'].' AND meta_key="ecadisData"');
		$a = $usermetaecadis->fetch_assoc();
		//$data = @preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $a['meta_value']);
		$data = $a['meta_value'];
		$m = unserialize($data);
		$names[$m['code_int']] = $m['prenom']." ".$m['nom']." (".$m['code_int'].")";
		
		if( $m['hiera'] > 0 && $m['statut'] == 'A' && $m['org_com2'] == 'T') 							$nips_in_wp_groups['680ari'][] = $m['code_int'];
		if( $m['hiera'] > 0 && $m['statut'] == 'A' && $m['org_cmp1'] == 'DAPZ') 						$nips_in_wp_groups['680dapz'][] = $m['code_int'];
		if( $m['hiera'] > 0 && $m['statut'] == 'A' && $m['incorp_cr'] == 'T') 							$nips_in_wp_groups['680dps'][] = $m['code_int'];
		if( $m['hiera'] > 0 && $m['statut'] == 'A' && $m['org_cmp1'] == 'DAPY') 						$nips_in_wp_groups['680serine'][] = $m['code_int'];
		if( $m['hiera'] > 0 && $m['statut'] == 'A' && ($m['org_off'] == 'T' || $m['org_em'] == 'T')) 	$nips_in_wp_groups['chefinter'][] = $m['code_int'];
		if( $m['hiera'] > 0 && $m['statut'] == 'A' && ($m['org_insp'] != '' || $m['org_em'] == 'T')) 	$nips_in_wp_groups['chlourd'][] = $m['code_int'];
		if( $m['hiera'] > 0 && $m['statut'] == 'A' && $m['org_em'] == 'T') 								$nips_in_wp_groups['em'][] = $m['code_int'];
		if( $m['hiera'] > 0 && $m['statut'] == 'A' && $m['org_sama'] == 'T') 							$nips_in_wp_groups['emserine'][] = $m['code_int'];
		if( $m['hiera'] > 0 && $m['statut'] == 'A' && $m['org_grpb'] == 'Oui') 							$nips_in_wp_groups['formateur'][] = $m['code_int'];
		if( 					   					  $m['org_mun'] == 'T')								$nips_in_wp_groups['codir'][] = $m['code_int'];
		if( 					   					  $m['org_adm'] == 'T')								$nips_in_wp_groups['administration'][] = $m['code_int'];
		if( $m['hiera'] > 0 && $m['statut'] == 'A' && $m['org_com1'] == 'T')							$nips_in_wp_groups['mat'][] = $m['code_int'];
		if( $m['hiera'] > 0 && $m['statut'] == 'A' && $m['org_ami'] == 'T')								$nips_in_wp_groups['sac'][] = $m['code_int'];
		if( 										  $m['org_comf'] == 'T')							$nips_in_wp_groups['mutation'][] = $m['code_int'];
		if( 				   $m['statut'] == 'A' && $m['tri'] == 'Oui')								$nips_in_wp_groups['680dapplus'][] = $m['code_int'];
		if(	$m['n_tag'] == 'C' && ( $m['statut'] == 'A' || $m['statut'] == 'CI'))						$nips_in_wp_groups['conseil'][] = $m['code_int'];

	
	}

	$output .= "\n\n--------- A faire -------\n\n";

#	echo "<pre>";
#	print_r($nips_in_wp_groups['680serine']);
#	print_r($nips_in_mail_groups['680serine']);
#	print_r(array_diff($nips_in_mail_groups['680serine'],$nips_in_wp_groups['680serine']));

	foreach($groups as $g){
		$diff = array_diff( $nips_in_mail_groups[$g], $nips_in_wp_groups[$g]);
		foreach($diff as $nip){	$output .= "Retirer ".(isset($names[$nip])? $names[$nip] : $nip)." du groupe ".$g."\n"; $sendmail = true; }
		$output .= "\n";
		$diff = array_diff( $nips_in_wp_groups[$g], $nips_in_mail_groups[$g]);
		$add = "";
		foreach($diff as $nip){$add .= $nip."@sdisglandserine.ch\n";	$output .= "Ajouter ".(isset($names[$nip])? $names[$nip] : $nip)." au groupe ".$g."\n"; $sendmail = true; }
		$output .= "\n\n";
		$output .= $add;
		$output .= "\n\n";
		
	}



	if($output != ""){
		echo "<pre>";
		print_r($output);
		if($sendmail)
			mail('vincent.barbay@gmail.com', 'SDIS Gland Serine - emails', $output);
	}
	
	
