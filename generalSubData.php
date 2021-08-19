<?php
require_once ('main-includes/passdbase/includepath.inc.php');

$affiliate = new Affiliate();
$report = new AffiliateReporting();
$affiliate->checkLogin();
$affiliateID = $_SESSION['affid'];
$datePeriod = isset($_POST['period'])?$_POST['period']:'';
$startDate = isset($_POST['start_date'])?$_POST['start_date']:'';
$endDate = isset($_POST['end_date'])?$_POST['end_date']:'';
$id = isset($_POST['id'])?$_POST['id']: die;

if( isset($_POST['campaignid']) && $_POST['campaignid'][0] != ''){
		$campaignID = $_POST['campaignid'];
}else{
		$campaignID = array();
}

if( isset($_POST['subid']) && $_POST['subid'][0] != ''){
		$subIDs = $_POST['subid'];
}else{
		$subIDs = array();
}
if( !isset($datePeriod) || $datePeriod === '' || $datePeriod === NULL ){
		$datePeriod = 'daily';
}


$whereDate = $report->getWhereDateFilter($datePeriod, 'date_credited', 'b1', $startDate, $endDate);
$whereDate2 = $report->getWhereDateFilter($datePeriod, 'viewdate', '',$startDate, $endDate);
$whereDate3 = $report->getWhereDateFilter($datePeriod, 'click_time-date_credited', 'a', $startDate, $endDate);

$query = "SELECT a.campaign_id AS cid,d.name AS name, a.sub_id AS id1, a.sub_id2 AS id2, a.sub_id3 AS id3
				,COUNT( DISTINCT(a.ip_address)) AS 'clicks'
				,ifnull(leads,0) AS 'leads'
				,ifnull(revenue,0) / COUNT(DISTINCT(a.ip_address)) 'epc'
				,ifnull(leads,0) / COUNT(DISTINCT(a.ip_address)) 'conv'	
				,ifnull(revenue,0) AS 'rev'
				,ifnull(c.imp,0) AS imp
			FROM er_leads a
				LEFT JOIN (
					SELECT b1.campaign_id, b1.sub_id, b1.sub_id2, b1.sub_id3
						,count(b1.id) AS 'leads'
						,SUM(b1.cpa) AS 'revenue'
					FROM er_leads b1
					WHERE b1.affiliate_id = {$affiliateID} {$whereDate}
					
					AND (b1.status = 'Payable' OR b1.status ='Paid')
					GROUP BY b1.campaign_id, b1.sub_id, b1.sub_id2, b1.sub_id3
				)b ON a.campaign_id = b.campaign_id AND a.sub_id=b.sub_id AND a.sub_id2=b.sub_id2 AND a.sub_id3=b.sub_id3
				LEFT JOIN (
					SELECT campaign_id, COUNT(id) as imp FROM er_impressions WHERE affiliate_id = {$affiliateID} {$whereDate2}
				)c on a.campaign_id = c.campaign_id
				INNER JOIN (
					SELECT id, name FROM er_campaigns 
				)d on a.campaign_id = d.id
			
			WHERE a.affiliate_id = {$affiliateID} and a.campaign_id = {$id} {$whereDate3} 
			
			GROUP BY a.campaign_id, a.sub_id, a.sub_id2, a.sub_id3
			order by a.campaign_id DESC, clicks Desc, a.id";
	
$result = $report->db->query($query);

while($row = $result->fetch_assoc()){
	$arrSubIds[]=array(
		'clicks' => $row['clicks'],
		'leads' => $row['leads'],
		'revenue' => $row['rev'],
		'impressions' => $row['imp'],
		'conv' => number_format($row['conv'] * 100),
		'epc' => $row['epc'],
		'id' => $row['id1'],
		'id2' => $row['id2'],
		'id3' => $row['id3']
		);
}

$data['subdata'] = $arrSubIds;

echo json_encode($data);