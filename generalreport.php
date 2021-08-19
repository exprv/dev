<?php
require_once ('main-includes/passdbase/includepath.inc.php');

$affiliate = new Affiliate();
$report = new AffiliateReporting();
$affiliate->checkLogin();
$records = array();
$data = array();
$data2 = array();
$subData = array();
$aggClicks = 0;
$aggLeads = 0;
$aggRevenue = 0;
$arrCampaigns = array();
$total_rows = 0;
$affiliateID = $_SESSION['affid'];
$datePeriod = isset($_POST['period'])?$_POST['period']:'';
$startDate = isset($_POST['start_date'])?$_POST['start_date']:'';
$endDate = isset($_POST['end_date'])?$_POST['end_date']:'';

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
$pagenum = $_POST['pagenum'];

$pagesize = $_POST['pagesize'];

$start = $pagenum * $pagesize;

$whereDate = $report->getWhereDateFilter($datePeriod, 'click_time-date_credited', 'l', $startDate, $endDate);

$whereSubIDs = $report->getWhereSubIdFilter($subIDs, 'l', $startDate, $endDate);

$whereOfferIDs = $report->getWhereOfferIdFilter($campaignID, 'l', $startDate, $endDate);

$whereDate2 = $report->getWhereDateFilter($datePeriod, 'click_time', '', $startDate, $endDate);

$whereSubIDs2 = $report->getWhereSubIdFilter($subIDs, '', $startDate, $endDate);

$whereOfferIDs2 = $report->getWhereOfferIdFilter($campaignID, '', $startDate, $endDate);

$whereDate3 = $report->getWhereDateFilter($datePeriod, 'viewdate', '',$startDate, $endDate);

$whereDateN = $report->getWhereDateFilter($datePeriod, 'date_credited', 'lc', $startDate, $endDate);
$whereOfferIDsN = $report->getWhereOfferIdFilter($campaignID, 'lc', $startDate, $endDate);

$query = "
SELECT SQL_CALC_FOUND_ROWS  distinct l.campaign_id 'cid'
,(SELECT count(lc.id)'leads'
FROM er_leads as lc 
WHERE lc.affiliate_id = {$affiliateID} AND lc.status in ('Payable', 'Paid') {$whereDateN} {$whereOfferIDsN} AND lc.campaign_id=l.campaign_id ) 'leads'
,(SELECT ifnull(count(lc.id)/B.clicks*100,0)'conv'
FROM er_leads as lc 
WHERE lc.affiliate_id = {$affiliateID} AND lc.status in ('Payable', 'Paid') {$whereDateN} {$whereOfferIDsN} AND lc.campaign_id=l.campaign_id) 'conv'
,(SELECT ifnull(sum(lc.cpa)/B.clicks,0)'epc'
FROM er_leads as lc
WHERE lc.affiliate_id = {$affiliateID} AND lc.status in ('Payable', 'Paid') {$whereDateN} {$whereOfferIDsN} AND lc.campaign_id=l.campaign_id) 'epc'
,(SELECT ifnull(sum(lc.cpa),0)'rev'
FROM er_leads as lc
WHERE lc.affiliate_id = {$affiliateID} AND lc.status in ('Payable', 'Paid') {$whereDateN} {$whereOfferIDsN} AND lc.campaign_id=l.campaign_id) 'rev'
,(SELECT COUNT(id) FROM er_impressions WHERE campaign_id=l.campaign_id AND affiliate_id=l.affiliate_id {$whereDate3} LIMIT 1)'imp'
,ifnull(B.clicks,0) 'clicks'
,c.name 'name' 
FROM er_leads as l
INNER JOIN er_campaigns as c on l.campaign_id = c.id
LEFT JOIN (SELECT sum(cnt) clicks, campaign_id from
( select count(DISTINCT(ip_address)) AS cnt , campaign_id FROM er_leads WHERE affiliate_id={$affiliateID} {$whereDate2} {$whereOfferIDs2} {$whereSubIDs2} GROUP BY campaign_id, ip_address)d
group by campaign_id) as B on l.campaign_id=B.campaign_id
WHERE affiliate_id = {$affiliateID} AND l.status in ('Payable', 'Paid','Pending') {$whereDate} {$whereOfferIDs} {$whereSubIDs}
group by l.campaign_id ";

$result = $report->db->query($query);

$TClicks = 0;
$TImps = 0;
$TLeads = 0;
$TRev = 0;

while($row = $result->fetch_assoc()){
	$TClicks += intval($row['clicks']);
	$TImps += intval($row['imp']);
	$TLeads += intval($row['leads']);
	$TRev += floatval($row['rev']);
	$arrCampaigns[]=array(
		'id' => $row['cid'],
		'clicks' => $row['clicks'],
		'leads' => $row['leads'],
		'revenue' => $row['rev'],
		'impressions' => $row['imp'],
		'conv' => number_format($row['conv'], 2) . '%',
		'epc' => $row['epc'],
		'name' => $row['name']
		);
}

if(count($TLeads)>0 && count($TClicks)>0){
    $TConv = $TLeads / $TClicks * 100;
}else { $TConv = 0; }

if(count($TRev)>0 && count($TClicks)>0){
    $TEpc = $TRev / $TClicks;
}else { $TEpc = 0; }

$counter= $report->db->query("SELECT FOUND_ROWS() as counter"); 
$counter=$counter->fetch_array(); 
$total_rows = $counter[0];
$result->close();
$aggregates = array();
$aggregates['cr'] = number_format($TConv,2) . '%';
$aggregates['ec'] = number_format($TEpc,2);
$records = array(
	'Rows' => $arrCampaigns,
	'aggregates' => $aggregates,
	'TotalRows' => $total_rows
);		
echo json_encode($records);