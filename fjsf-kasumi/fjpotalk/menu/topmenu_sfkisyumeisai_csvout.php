﻿<?php
//**********************************************************************
//【処理】一覧CSV出力(POST先の処理) (クライアントPCに直接保存する)
//**********************************************************************

	session_start();

	include "../common/MYDB.php";
	include "../common/MYCONST.php";
	include "../common/fjcall_comfunc.php";

	$this_pg = "topmenu_sfkisyumeisai_csvout.php";

	$companycode = $_SESSION["companycode_call"];//会社コード
	$companyname = $_SESSION["companyname_call"];
	$selClient = $_SESSION["selclient_call"];
	$selDesk = $_SESSION["seldesk_call"];
	$selEndUser = $_SESSION["selenduser_call"];
	$user = $_SESSION['userid_call'];
	$name = $_SESSION['name_call'];
	$level = $_SESSION['levelid_call'];

	//親ﾌｫｰﾑでの選択情報
	$selymd1 = $_GET["selymd1"];
	$selymd2 = $_GET["selymd2"];
	$selmode = $_GET["selmode"];

	//日付指定時
	$fromY = substr( $selymd1, 0, 4 );
	$fromM = sprintf("%02d", substr( $selymd1,4, 2 ));
	$fromD = sprintf("%02d", substr(  $selymd1,6, 2 ));
	$toY = substr( $selymd2,0, 4 );
	$toM = sprintf("%02d", substr( $selymd2,4, 2 ));
	$toD = sprintf("%02d", substr(  $selymd2,6, 2 ));


	//ﾍｯﾀﾞｰ情報
	header("Content-Type: application/octet-stream");
	$outdate = date("Ymd_His");
	$outdate = $outdate."_totallist";
	header("Content-Disposition: attachment; filename=$outdate.csv");

	//ヘッダの作成
	$csvHead = "\"" . "No." . "\",\"";
	$csvHead = $csvHead .  "日付" . "\",\"";
	$csvHead = $csvHead .  "時刻" . "\",\"";
	$csvHead = $csvHead .  "店舗名" . "\",\"";
	$csvHead = $csvHead .  "インシデントNo." . "\",\"";
	$csvHead = $csvHead .  "内容" . "\",\"";
	$csvHead = $csvHead .  "機種" . "\",\"";
	$csvHead = $csvHead .  "ステータス" . "\",\"";
	$csvHead = $csvHead .  "完了理由";
	$csvHead = $csvHead . "\"\n";

	//ヘッダの出力
	print $csvHead;

	//DBｵｰﾌﾟﾝ
	$conn = db_connect();

	//データ出力
	$wRecCnt = 0;

	//Salesforce上は、実際の時間の9時間前で記録されている為
	$wFromDate9 = $fromY . "/" . $fromM . "/" . $fromD . " 00:00:00";
	$wFromDate9 = date("Y-m-d H:i:s",strtotime($wFromDate9 . "-9 hour"));

	$wTo9 = $toY . "/" . $toM . "/" . $toD . " 00:00:00";
	$wTo9 = date("Y-m-d H:i:s",strtotime($wTo9 . "+1 day"));
	$wTo9 = date("Y-m-d H:i:s",strtotime($wTo9 . "-9 hour"));

	$selname = $selmode;

	$sql = "SELECT * From " . $Const_DB_SCHEMA . "case";
	$sql = $sql . " WHERE (" . $Const_DB_SCHEMA . "case.createddate>='" . $wFromDate9 . "'";
	$sql = $sql . " AND    " . $Const_DB_SCHEMA . "case.createddate<='" . $wTo9 . "')";
	$sql = $sql . "   AND (" . $Const_DB_SCHEMA . "case.hq_name__c='" . $Const_HQ_NAME . "')";//MYCONST
	$sql = $sql . "   AND (" . $Const_DB_SCHEMA . "case.inquirycategory2__c='" . $selname . "')";//機種
	if( $sortflg == 1 ){
		$sql = $sql . " ORDER BY " . $Const_DB_SCHEMA  . "case.createddate"; //日時
	}elseif( $sortflg == 2 ){
		$sql = $sql . " ORDER BY " . $Const_DB_SCHEMA  . "case.shopname__c"; //店舗名
	}elseif( $sortflg == 3 ){
		$sql = $sql . " ORDER BY convert_to(" . $Const_DB_SCHEMA  . "case.inquirycategory3__c,'UTF-8')"; //内容
	}elseif( $sortflg == 4 ){
		$sql = $sql . " ORDER BY convert_to(" . $Const_DB_SCHEMA  . "case.inquirycategory2__c,'UTF-8')"; //機種
	}elseif( $sortflg == 5 ){
		$sql = $sql . " ORDER BY convert_to(" . $Const_DB_SCHEMA  . "case.status,'UTF-8')"; //ステータス
	}elseif( $sortflg == 6 ){
		$sql = $sql . " ORDER BY convert_to(" . $Const_DB_SCHEMA  . "case.closereson__c,'UTF-8')"; //完了理由
	}else{
		$sql = $sql . " ORDER BY " . $Const_DB_SCHEMA  . "case.casenumber"; //インシデントNO
	}
	if($ENV_MODE == 1){
		$sql = mb_convert_encoding( $sql, $MOJI_ORG, $MOJI_NEW ); //ここは日本語が混じっているのでUTF-8へ
	}
	$result = $conn->prepare($sql);
	$result->execute();
	while ($rs = $result->fetch(PDO::FETCH_ASSOC))
	{
		$wRecCnt = $wRecCnt + 1;


		$createddate = date("Y-m-d H:i:s",strtotime($rs["createddate"] . "+9 hour")); //ここで9時間足す
		$createdateYMD = substr($createddate,0,10);
		$createdateHNS = substr($createddate,11);

		if($ENV_MODE == 1){
			$storename = mb_convert_encoding( $rs['shopname__c'], $MOJI_NEW,$MOJI_ORG); //文字コード変換;
			$naiyou = mb_convert_encoding( $rs['inquirycategory3__c'], $MOJI_NEW,$MOJI_ORG); //文字コード変換;
			$kisyu = mb_convert_encoding( $rs['inquirycategory2__c'], $MOJI_NEW,$MOJI_ORG); //文字コード変換;
			$status =mb_convert_encoding( $rs['status'], $MOJI_NEW,$MOJI_ORG); //文字コード変換;
			$taiou =mb_convert_encoding( $rs['closereson__c'], $MOJI_NEW,$MOJI_ORG); //文字コード変換;
		}else{
			$storename = $rs['shopname__c'];
			$naiyou = $rs['inquirycategory3__c'];
			$kisyu = $rs['inquirycategory2__c'];
			$status =$rs['status'];
			$taiou =$rs['closereson__c'];
		}


		$contents = "\"" . $wRecCnt . "\",\"" 
                         . $createdateYMD . "\",\"" 
                         . $createdateHNS . "\",\"" 
                         . $storename . "\",\"" 
                         . $rs["casenumber"] . "\",\"" 
                         . $naiyou . "\",\"" 
                         . $kisyu . "\",\"" 
                         . $status . "\",\"" 
                         . $taiou . "\"\n";
		print $contents;

	}
	$rs = null;
	$result = null;
	$conn = null;

?>
