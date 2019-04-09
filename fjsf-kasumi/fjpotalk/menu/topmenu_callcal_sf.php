<?php
function PreMakeWkDataSF( $conn, $selDesk, $wSelclientcode, $wSelendusercode ) {

global $ENV_MODE;
global $Const_COMPANYCODE;
global $Const_HQ_NAME;
global $Const_DB_SCHEMA;
global $MOJI_ORG;
global $MOJI_NEW;

global $user;
global $fromY, $fromM, $fromD, $toY, $toM, $toD;



		//日別件数のカウント→wdeskhistory2day
		//時間帯別件数のカウント→salesforce_wdeskhistory3timefj


		//Salesforce上は、実際の時間の9時間前で記録されている為
		$wFromDate9 = $fromY . "/" . $fromM . "/" . $fromD . " 00:00:00";
		$wFromDate9 = date("Y-m-d H:i:s",strtotime($wFromDate9 . "-9 hour"));

		$wTo9 = $toY . "/" . $toM . "/" . $toD . " 00:00:00";
		$wTo9 = date("Y-m-d H:i:s",strtotime($wTo9 . "+1 day"));
		$wTo9 = date("Y-m-d H:i:s",strtotime($wTo9 . "-9 hour"));

		$sql = "SELECT * From " . $Const_DB_SCHEMA . "case";
		$sql = $sql . " WHERE (" . $Const_DB_SCHEMA . "case.createddate>='" . $wFromDate9 . "'";
		$sql = $sql . " AND    " . $Const_DB_SCHEMA . "case.createddate<='" . $wTo9 . "')";
		$sql = $sql . "   AND (" . $Const_DB_SCHEMA . "case.hq_name__c='" . $Const_HQ_NAME . "')";//MYCONST
		$sql = $sql . " ORDER BY " . $Const_DB_SCHEMA  . "case.casenumber";
		if($ENV_MODE == 1){
			$sql = mb_convert_encoding( $sql, $MOJI_ORG, $MOJI_NEW ); //ここは日本語が混じっているのでUTF-8へ
		}
		$result = $conn->prepare($sql);
		$result->execute();
		while ($rs = $result->fetch(PDO::FETCH_ASSOC))
		{

			//日別件数の更新
			$createddate = date("Y-m-d H:i:s",strtotime($rs["createddate"] . "+9 hour")); //ここで9時間足す
			$createdateYMD = substr($createddate,0,10);
			$createdateYMD2 = str_replace("-", "", $createdateYMD);//ｽﾗｯｼｭを外す

			$sql2 = "UPDATE " . $Const_DB_SCHEMA . "wdeskhistory2day SET";
			$sql2 = $sql2 ." sf_in_cnt=sf_in_cnt+1";
			$sql2 = $sql2 ." WHERE userid='". $Const_COMPANYCODE . $user."'";
			$sql2 = $sql2 ." AND   hiduke=". $createdateYMD2;
			$result2 = $conn->query($sql2);
			$result2 = null;


			//時間帯別件数の更新(小計、合計は計算不要)
			$createdateH = substr($createddate,11,2);
			$createdateH +=0;

			$sql2 = "UPDATE " . $Const_DB_SCHEMA . "wdeskhistory3timefj SET";
			$sql2 = $sql2 ." sf_in_cnt=sf_in_cnt+1";
			$sql2 = $sql2 ." WHERE userid='". $Const_COMPANYCODE . $user."'";
			$sql2 = $sql2 ." AND   time=". $createdateH;
			$result2 = $conn->query($sql2);
			$result2 = null;

		}
		$result = null;




}
//////////////////////////////////////////
//<説明>
//  過去全ての未完了件数を取得する(期間指定関係なし)
//////////////////////////////////////////
function Mikanryo($conn) {

global $ENV_MODE;
global $Const_HQ_NAME;
global $Const_DB_SCHEMA;
global $MOJI_ORG;
global $MOJI_NEW;

	$mikensu = 0;

	$sql = "SELECT Count(" . $Const_DB_SCHEMA . "case.id) AS id_cnt FROM " . $Const_DB_SCHEMA . "case";
	$sql = $sql . " WHERE ((" . $Const_DB_SCHEMA . "case.closeddate) Is Null)";
	$sql = $sql . " AND   ( " . $Const_DB_SCHEMA . "case.hq_name__c='" . $Const_HQ_NAME . "')";
	if($ENV_MODE == 1){
		$sql = mb_convert_encoding( $sql, $MOJI_ORG, $MOJI_NEW ); //ここは日本語が混じっているのでUTF-8へ
	}
	$result = $conn->prepare($sql);
	$result->execute();
	while ($rs = $result->fetch(PDO::FETCH_ASSOC))
	{
		$mikensu = $rs["id_cnt"];
	}
	$result = null;

	return $mikensu;


}
?>
