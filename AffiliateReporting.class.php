<?php 

class AffiliateReporting
{
	
	public function __construct()
	{
		$this->HostName			=	DB_HOST;
		$this->UserName			=	DB_USER;
		$this->Password			=	DB_PASSWORD;
		$this->DatabaseName		=	DB_NAME;
		
		$this->db = new MySQLi(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
		
		if (mysqli_connect_errno())
		{
			printf("Connect failed: %s\n", mysqli_connect_error());
			exit();
		}
	}
	
	
	
	public function getGeneralReportData($affiliateId, $offerIds = array(), $subIds = array(), $datePeriod = 'daily', $startDate = null, $endDate = null)
	{
		$data = array();
		
		$data2 =array();
		
		$campaignIdArr = array();
		
		$subIdArr = array();
		
		$aggClicks = 0;
		
		$aggLeads = 0;
		
		$aggRevenue = 0;
		
		$whereDate = $this->getWhereDateFilter($datePeriod, 'date_credited', 'l', $startDate, $endDate);
		
		$whereOfferIds = $this->getWhereOfferIdFilter($offerIds, 'l');
		
		$whereSubIds = $this->getWhereSubIdFilter($subIds, 'l');
		
		$query = "SELECT DISTINCT l.campaign_id 
				FROM er_leads l 
				INNER JOIN er_campaigns c ON l.campaign_id=c.id 
				WHERE l.affiliate_id = {$affiliateId} AND (l.status='Payable'|| l.status='Paid') {$whereDate} {$whereOfferIds} {$whereSubIds} GROUP BY l.click_time ASC";

		$result = $this->db->prepare($query);
		
		$result->execute();
		
		$result->bind_result($campaignId);
		
		$whereDate2 = $this->getWhereDateFilter($datePeriod, 'click_time', 'l');
		
		while ($result->fetch())
		{			
			$campaignIdArr[$campaignId] = $campaignId;
		}
				
		$result->free_result();
		
		$whereDate3 = $this->getWhereDateFilter($datePeriod, 'click_time', 'l');
		
		if(count($campaignIdArr) > 0)
		{
			foreach($campaignIdArr as $id)
			{
			
				$query = "select 
					 l.campaign_id 'id'
					 ,c.name 'name'
					 ,IFNULL(IMP,0) 'impressions'
					 ,COUNT(DISTINCT ip_address) 'clicks'
					 ,sum(CASE WHEN l.status IN ('Paid', 'Payable') THEN 1 ELSE 0 END) 'leads'
					 ,sum(CASE WHEN l.status IN ('Paid', 'Payable') THEN l.cpa ELSE 0 END) 'amount'
					 ,sum(CASE WHEN l.status IN ('Paid', 'Payable') THEN 1 ELSE 0 END)/COUNT(DISTINCT ip_address) * 100 'conversion'
					 ,sum(CASE WHEN l.status IN ('Paid', 'Payable') THEN l.cpa ELSE 0 END)/COUNT(DISTINCT ip_address) 'epc'
					 from er_leads l 
					 inner join er_campaigns c on l.campaign_id = c.id  
					 left join (select count(id)'IMP', campaign_id, affiliate_id from er_impressions ) ji on l.campaign_id=ji.campaign_id and l.affiliate_id=ji.affiliate_id
					 WHERE  l.affiliate_id= {$affiliateId} and l.campaign_id = {$id} {$whereDate2} {$whereSubIds} {$whereOfferIds}";
			
				$result = $this->db->query($query, MYSQLI_USE_RESULT );
			
				while($row = $result->fetch_assoc())
				{
					if($row['clicks'] > 0 ) 
					{
						$aggClicks += $row['clicks'];
						
						$aggLeads += $row['leads'];
						
						$aggRevenue += $row['amount'];
						
						$data[$row['id']] = array(
							'id' => $row['id'],
							'name' => $row['name'],
							'impressions' => $row['impressions'],
							'clicks' => $row['clicks'],
							'leads' => $row['leads'],
							'conv' => @round($row['conversion'],2) . '%',
							'epc' => $row['epc'],
							'revenue' => $row['amount']
						);	
					}
				}
				
				$result->free_result();									
			}
			foreach($data as $id => $v)
			{
				$query = "SELECT l.sub_id 'sub1', l.sub_id2 'sub2', l.sub_id3 'sub3'
							,count(distinct ip_address) 'clicks'
							,sum(case when l.status in ('Payable', 'Paid') then 1 else 0 end) AS 'leads' 
							,SUM(case when l.status in ('Payable', 'Paid') then cpa else 0 end) AS 'revenue'
							,sum(case when l.status in ('Payable', 'Paid') then 1 else 0 end)/count(DISTINCT ip_address) 'conversion'
							,SUM(case when l.status in ('Payable', 'Paid') then cpa else 0 end)/count(DISTINCT ip_address) 'epc'
							FROM er_leads l WHERE l.affiliate_id = {$affiliateId} and l.campaign_id = {$id} {$whereOfferIds} {$whereDate3} {$whereSubIds} GROUP BY l.ip_address";	
					
				$result = $this->db->query($query, MYSQLI_USE_RESULT);
					
				//$result->bind_result($sub1, $sub2, $sub3, $subClicks, $subLeads, $subAmount, $subConversion, $subEpc);	
				
				while($row = $result->fetch_assoc())
				{
					$subData[]= array(
						'id' => $row['sub1'],
						'id2' => $row['sub2'],
						'id3' => $row['sub3'],
						'impressions' => 0, 
						'clicks' => $row['clicks'],
						'leads' => $row['leads'],
						'conv' => $row['conversion'],
						'epc' => $row['epc'],
						'revenue' => $row['revenue']
					);					
				}
				
				$result->free_result();
				
				$data[$id]['subdata'] = $subData;
				
				unset($subData);
			}
			
			foreach($data as $i => $v){
				$data2[] = $v;	
			}
			
			$cr = round(@($aggLeads / $aggClicks) * 100, 2) . '%';
			$aggregates = array();
			$aggregates['cr'] = $cr;
			$records = array(
				'Rows' => $data2,
				'aggregates' => $aggregates
			);
			
			return $records;
		}		
	}
	
	public function getClickReportData($affiliateId, $offerIds = array(), $subIds = array(), $datePeriod = 'daily')
	{		
		
		$where = $this->getWhereDateFilter($datePeriod, 'click_time', 'l');
		
		$where .= $this->getWhereOfferIdFilter($offerIds, 'l');
		
		$where .= $this->getWhereSubIdFilter($subIds, 'l');
		
		$query = "SELECT 
			l.id 'campaign_id'
			,l.sub_id 'sub_id'
			,l.sub_id2 'sub_id2'
			,l.sub_id3 'sub_id3'
			,c.name 'campaign_name'
			,l.ip_address 'ip_address'
			,l.status 'status'
			,l.click_time  'click_time'
			FROM er_leads l, er_campaigns c WHERE l.affiliate_id = {$affiliateId} AND l.campaign_id = c.id {$where} ORDER BY l.click_time DESC LIMIT ?, ?  ";	
		
		$result = $this->db->query($query,MYSQLI_USE_RESULT);
						
		//$result->bind_result($campaignId, $subId, $subId2, $subId3, $campaignName, $ipAddress, $campaignStatus, $clickTime);
		if($result)
		{
			while ($row = $result->fetch_assoc())
			{				
				$returnData[] = array(
					'campaign_id' => $row['campaign_id'],
					'campaign_name' =>  $row['campaign_name'],
					'sub_id' =>  $row['sub_id'],
					'sub_id2' =>  $row['sub_id2'],
					'sub_id3' =>  $row['sub_id3'],
					'ip_address' =>  $row['ip_address'],
					'status' => $row['status'],
					'click_time' => $row['click_time']
				);
			}
		}
		
		$result->close();		
		
		return $returnData;
	}
	
	public function getWhereDateFilter($datePeriod = 'daily', $dateField, $tablePrefix = '', $startDate = null, $endDate = null)
	{
		if($tablePrefix != '')
		{
			$tablePrefix .= '.';	
		}
		$where = '';
		
		switch($datePeriod){
            case "PSD":
                $date1="and date({$tablePrefix}date_credited)>'".date('Y-m-d', strtotime("-7 days",strtotime(DATEONLY)))."'";
                $date2="and date({$tablePrefix}viewdate)>'".date('Y-m-d', strtotime("-7 days",strtotime(DATEONLY)))."'";
				$date3="and date({$tablePrefix}click_time)>'".date('Y-m-d', strtotime("-7 days",strtotime(DATEONLY)))."'"; 
				$date4="and (date({$tablePrefix}click_time)>'".date('Y-m-d', strtotime("-7 days",strtotime(DATEONLY)))."' OR date({$tablePrefix}date_credited)>'".date('Y-m-d', strtotime("-7 days",strtotime(DATEONLY)))."')"; 
        		break;
            case "MTD":
                $date1="and month({$tablePrefix}date_credited)='".MONTH."' and year({$tablePrefix}date_credited)='".YEAR."'";
                $date2="and month({$tablePrefix}viewdate)='".MONTH."' and year(viewdate)='".YEAR."'";
				$date3="and month({$tablePrefix}click_time)='".MONTH."' and year({$tablePrefix}click_time)='".YEAR."'";
				$date4="and (month({$tablePrefix}click_time)='".MONTH."' and year({$tablePrefix}click_time)='".YEAR."' OR month({$tablePrefix}date_credited)='".MONTH."' and year({$tablePrefix}date_credited)='".YEAR."')";
                break;
            case "YTD":
                $date1="and year({$tablePrefix}date_credited)='".YEAR."'";
                $date2="and year({$tablePrefix}viewdate)='".YEAR."'";
				$date3="and year({$tablePrefix}click_time)='".YEAR."'";
				$date4="and (year({$tablePrefix}click_time)='".YEAR."' OR year({$tablePrefix}date_credited)='".YEAR."')";
                break;
            case "lastyear":
                $year=YEAR-1;
                $date1="and year({$tablePrefix}date_credited)='".$year."'";
                $date2="and year({$tablePrefix}viewdate)='".$year."'";
				$date3="and year({$tablePrefix}click_time)='".$year."'";
				$date4="and (year({$tablePrefix}click_time)='".$year."' OR year({$tablePrefix}date_credited)='".$year."')";
                break;
            case "daily":
                $date1="and date({$tablePrefix}date_credited)='".DATEONLY."'";
                $date2="and date({$tablePrefix}viewdate)='".DATEONLY."'";
				$date3="and date({$tablePrefix}click_time)='".DATEONLY."'";
				$date4="and (date({$tablePrefix}click_time)='".DATEONLY."' OR date({$tablePrefix}date_credited)='".DATEONLY."')";
                break;
            case "yesterday":
                $date1="and date({$tablePrefix}date_credited)='".date('Y-m-d', strtotime("-1 day",strtotime(DATEONLY)))."'";
                $date2="and date({$tablePrefix}viewdate)='".date('Y-m-d', strtotime("-1 day",strtotime(DATEONLY)))."'";
				$date3="and date({$tablePrefix}click_time)='".date('Y-m-d', strtotime("-1 day",strtotime(DATEONLY)))."'";
				$date4="and (date({$tablePrefix}click_time)='".date('Y-m-d', strtotime("-1 day",strtotime(DATEONLY)))."' OR date({$tablePrefix}date_credited)='".date('Y-m-d', strtotime("-1 day",strtotime(DATEONLY)))."')";
                break;
            case "custom":
                $date1="and date({$tablePrefix}date_credited) between '".date("Y-m-d",strtotime($startDate))."' and '".date("Y-m-d",strtotime($endDate))."'";
                $date2="and date({$tablePrefix}viewdate) between '".date("Y-m-d",strtotime($startDate))."' and '".date("Y-m-d",strtotime($endDate))."'";
				$date3="and date({$tablePrefix}click_time) between '".date("Y-m-d",strtotime($startDate))."' and '".date("Y-m-d",strtotime($endDate))."'";
				$date4="and (date({$tablePrefix}click_time) between '".date("Y-m-d",strtotime($startDate))."' and '".date("Y-m-d",strtotime($endDate))."' OR date({$tablePrefix}date_credited) between '".date("Y-m-d",strtotime($startDate))."' and '".date("Y-m-d",strtotime($endDate))."')";
                break;
			default:
			$date1 = "";
			$date2 = "";
			$date3 = "";
			$date4 = "";
			break;
		}	
		
		switch($dateField)
		{
			case 'date_credited':
				return  $date1;
				break;
			case 'viewdate':
				return $date2;
				break;
			case 'click_time':
				return $date3;
				break;
			case 'click_time-date_credited':
				return $date4;
				break;
			default:
				return '';
				break;						
		}
		
		return $where;	
	}	
	public function getWhereSubIdFilter($subIds = array(), $tablePrefix = '')
	{
		if($tablePrefix != '')
		{
			$tablePrefix .= '.';	
		}
		$where = '';
		//ARRAY
		if(isset($subIds) && count($subIds) > 1) {
			
			$subin = '';
			
            foreach($subIds as $sub) {
				
				if($sub != '')
				{
                	$subin .= "'" . $sub . "',";
				}
            }
			
            $subin = substr($subin,0,-1);
			
		    if(!empty($subin)) {
                $where .= "AND ({$tablePrefix}sub_id IN ($subin) OR {$tablePrefix}sub_id2 IN ($subin) OR {$tablePrefix}sub_id3 IN ($subin)) ";
            }
			
			
        } else if(isset($subIds) && count($subIds) == 1) {
            
			    $where .= "AND ({$tablePrefix}sub_id LIKE '%" . $subIds[0] . "%' OR {$tablePrefix}sub_id2 LIKE '%" . $subIds[0] . "%' OR {$tablePrefix}sub_id3 LIKE '%" . $subIds[0] . "%') ";
       
	    }	
		
		
		
			
		return $where;
	}
	
	public function getWhereOfferIdFilter($offerIds = array(), $tablePrefix = '')
	{
		if($tablePrefix != '')
		{
			$tablePrefix .= '.';	
		}
		
		$where = '';
		
		if(isset($offerIds) && count($offerIds) > 0) {
			
			$offerIn = '';
			
            foreach($offerIds as $offer) 
			{
				if($offer != '')
				{
                	$offerIn .= "'" . $offer . "',";
				}
            }
			
            $offerIn = substr($offerIn,0,-1);
			
            if(!empty($offerIn)) {
				
                $where .= "AND {$tablePrefix}campaign_id IN ($offerIn) ";
            }
        }
		
		return $where;	
	}
}