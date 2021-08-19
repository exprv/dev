<?php
class Affiliate
{
    public function __construct()
    {
        $this->db = new Database();
        $this->ip = new Ipp();
        $this->loader = new Loader();
        $this->camp = new Campaign();
        $this->stats = new Stats();
		$this->emailTemplate = new emailTemplate();
		
        if (!$this->db->dbConnect()) {
            echo "Error in Database Connection";
        }
    }

    public function checkLogin()
    {
        if ($_SESSION["usertype"] != "Affiliate") {
            echo '<script language="javascript">window.location="index.php?invalid=1";</script>';
            exit();
        }
    }

  
    public function rightContent()
    {
        $res = $this->db->getRecords("null", "er_admin", "id=" . $_SESSION["affman"], 1);
        switch ($res["im_type"]) {
            case "Skype":
                $imlink = 'skype:live:' . $res["skypeLive"].'?chat';
                break;
            case "AIM":
                $imlink = 'aim:goIM?screenname=' . $res["screen_name"];
                break;
            case "Yahoo":
                $imlink = 'ymsgr:sendim?' . $res["screen_name"];
                break;
        }
        $today = explode(",", $this->stats->affTodayStat(0, $_SESSION["affid"]));
    //var_dump($today);
		$monthly = explode(",", $this->stats->affMonthlyStat(0, $_SESSION["affid"]));
        $weekly = explode(",", $this->stats->affWeeklyStat(0, $_SESSION["affid"]));
        $total = explode(",", $this->stats->affTotalStat(0, $_SESSION["affid"]));
        $refbal = 0;
        $res2 = $this->referral();
        
		//var_dump($res2);
		
		if($res2 != NULL){
			foreach ($res2 as $val) {
				$res1 = $this->reftotBal($val["id"]);
				$refbal = $refbal + $res1["balance"];
			}
		} else {
			$refbal = 0;
			}
        
		
		$content = ' <div class="col-md-12 sidebarbg">
							<style>
							.bold {font-weight: bold;}
							.fontlayout {font-size: 13px;
							line-height: 32px;
							margin-top: -7px;}
							.manager_title {
								color: rgb(22, 173, 182);
								font-size: 21px;
								font-weight: bold;
								}
							.headerside {
								background-color:#196a94; margin: 5% 0 5% 0;
								}
							</style>
							<div class="headerside">
                                <h4>Account Manager</h4>
                            </div>
                                    <div class="col-md-4" style="padding: 0px;">
                                     <img align="left" src="' . PATH . 'main-images/am/' . $res["am_photo"] . '" width="100%" height="auto">
                                    </div>
                                    
									
									<div class="col-md-8 fontlayout" style="padding: 6px;">
											 <font class="manager_title">' . $res["firstname"] . ' ' . $res["lastname"] . '</font><br/>
											 <font class="bold">Phone:</font>
												' . $res["phone_number"] . '<br/>
											 <font class="bold">Email:</font>
												<a href="mailto:' . $res["email"] . '">' . $res["email"] . '</a><br/>
											   <font class="bold">' . $res["im_type"] . ':</font> <a href="' . $imlink . '">' . $res["screen_name"] . '</a>	
                                    </div>
									<!--div class="col-md-12" >
                                        <a href="' . $imlink . '" class="button gray col-md-12" style="color:#FFF; background-color: #00AFF0; webkit-border-radius: 10px;-moz-border-radius: 10px;border-radius: 10px;">
                                        <i class="fa fa-skype" aria-hidden="true"></i> Click here to add me on Skype
                                        </a>
                                    </div--->
									
									<br/>
                               <div class="row">
    							   <div class="col-md-12" style="padding: 11px 25px; text-align: center;">
        							   	<a href="RE-affiliateSupport.php" class="button gray col-md-12" style="color:#FFF">
        							   		<i class="fa fa-ticket" aria-hidden="true"></i> Contact Support <i class="fa fa-ticket" aria-hidden="true"></i>
        							    </a>
    							   </div>
							   </div>
							   </div>
						
                             
                     	<div class="col-md-12 sidebarbg">
                     	 <div class="headerside">
                          <h4>Account Vitals &amp; History</h4>
                          </div>
								<label>Earnings Statistics</label>
									<div class="col-md-12"><a href="RE-affiliateReport.php?rp=daily"><i class="fa fa-arrow-right" aria-hidden="true"></i> Daily Revenue: ' . '$' . number_format($today[3], 2) . '</a></div>
									<div class="col-md-12"><a href="RE-affiliateReport.php?rp=PSD"><i class="fa fa-arrow-right" aria-hidden="true"></i> Previous 7 Days Revenue: ' . '$' . number_format($weekly[3], 2) . '</a></div>
									<div class="col-md-12"><a href="RE-affiliateReport.php?rp=MTD"><i class="fa fa-arrow-right" aria-hidden="true"></i> Month-To-Date Revenue: ' . '$' . number_format($monthly[3], 2) . '</a></div>
									<div class="col-md-12"><a href="RE-affiliateReport.php?rp="><i class="fa fa-arrow-right" aria-hidden="true"></i> Lifetime Revenue: ' . '$' . number_format($total[3], 2) . '</a></div>
									<div class="col-md-12" style="color: #196a94;"><i class="fa fa-arrow-right" aria-hidden="true"></i> Lifetime Referral Revenue: $' . number_format($refbal, 2, '.', '') . '</div>
						  </div>
						
			
            	 <div class="col-md-12 sidebarbg">
                     <div class="headerside">
                          <h4>Recent Activity</h4>
                          </div>
                           <div class="col-md-12">
                          
						   
						   <a style="cursor:pointer;" id="toggle-revoked"><i class="fa fa-bars" aria-hidden="true"></i> Revoked offers</a>
							      <div id="toggle-revoked_sub" class="showsidebardrop">
								  <table class="basic-table"> 
									<tr>
										<th>ID</th>
										<th>Name</th>
                                            </tr>';
                                                    $res = $this->camp->affCampaign("Pulled", 5);
                                                    if (count($res) == 0) {
                                                        $content .= '<tr><td>No Record Found</td></tr>';
                                                    } else {
                                                        foreach ($res as $val) {
                                                            $content .= '<tr><td><a href="affCampaign.php?id=' . $val["id"] . '">' . $val["id"] . '</a></td></tr>
															<tr><td><a href="affCampaign.php?id=' . $val["id"] . '">' . $val["cname"] . '</a></td></tr>';
                                                        }
                                                    }
                                                    $content .= '</table></div></div>
						  
	<div class="col-md-12">
	<a style="cursor:pointer;" id="toggle-disbursed"> <i class="fa fa-bars" aria-hidden="true"></i> Disbursed payments</a>
	<div id="toggle-disbursed_sub"  class="showsidebardrop">
			<table class="basic-table"> 
			<tr>
				<th>Amount</th>
				<th>Date</th>
			</tr>';
        $res = $this->payment("Shipped", 5);
        if (count($res) == 0) {
            $content .= '<tr><td>No Record Found</td></tr>';
        } else {
            foreach ($res as $val) {
                $date = explode(" ", $this->loader->datetimereverse($val["sent_date"]));
                $content .= '<tr><td><a href="affiliateStatements_er.php?id=' . $val["id"] . '">$' . number_format($val['amount'], 2, '.', '') . '</a><td>
				<td>' . $date[0] . '</td>
							 ';
            }
        }
        $content .= '</table></div></div>
		
		<div class="col-md-12">
		<a style="cursor:pointer;" id="toggle-delayed"><i class="fa fa-bars" aria-hidden="true"></i> Pending payments</a>
       <div id="toggle-delayed_sub"  class="showsidebardrop">
		<table class="basic-table">
    <tr><th>Amount</th><th>Date</th></tr>';
        $res = $this->payment("In Review", 5);
        if (count($res) == 0) {
            $content .= '<tr><td style="background-color:rgb(246, 247, 248)" >No Record Found</td></tr>';
        } else {
            foreach ($res as $val) {
                $date = explode(" ", $this->loader->datetimereverse($val["time_placed"]));
                $content .= '
   <tr><td><a href="affiliateStatements_er.php?id=' . $val["id"] . '">$' . number_format($val['amount'], 2, '.', '') . '</a></td>
   	   <td>' . $date[0] . '</td></tr>';
            }
        }
        $content .= '</table></div>
		</div>
		
		<div class="col-md-12">
<a style="cursor:pointer;" id="toggle-cpa"><i class="fa fa-bars" aria-hidden="true"></i> CPA bump issued</a>
		
<div id="toggle-cpa_sub" class="showsidebardrop">	
<table class="basic-table">
    <tr>
      <th>Name</th>
      <th>CPA</th>
      <th>Bump</th>
     </tr>';
        $res = $this->camp->viewCPA(5,0);
        if (count($res) == 0) {
            $content .= '<tr><td  style="background-color:rgb(246, 247, 248)">No Record Found</td></tr>';
        } else {
            foreach ($res as $val) {
                $content .= '

<tr>
     <td><a href="RE-affiliateCampaign.php?id=' . $val["id"] . '">' . $val["cname"] . '</a></td>
     <td>$' . $val["cpa1"] . '</td>
     <td><strong>$' . $val["cpa2"] . '</strong></td></tr>';
            }
        }
        $content .= '</table></div></div>
		
		
		
		
		
		
		
		
		
<div class="col-md-12 margin-bottom-40" >
<a style="cursor:pointer;" id="toggle-express"><i class="fa fa-bars" aria-hidden="true"></i> New Express Support</a>

<div id="toggle-express_sub" class="showsidebardrop">
<table class="basic-table"> 
			<tr>
				<th>Subject</th>
				<th>Date</th>
			</tr>';
        $res = $this->newSupportTickets("no", 5);
        if (count($res) == 0) {
            $content .= ' <tr><td>No Record Found</td></tr>';
        } else {
            foreach ($res as $val) {
                $date = explode(" ", $this->loader->datetimereverse($val["message_date"]));
                $content .= '
		<tr><td>
		<a href="affiliateViewSupport_er.php?id=' . $val["id"] . '">' . $val["subject"] . '</a>
		</td>
		<td>' . $date[0] . '</td>';
            }
        }
        $content .= '</table></div></div></div>
		
		
		';
		
	
        return $content;
    }

 
    public function getAffiliateDetails($id)
    {
        return $this->db->getRecords("null", "er_affiliates", "id=" . $id, 1);
    }
	
	
	
	public function checkterms(){
			
	      $affCheck  = $this->db->getRecords("id", "er_affiliates", " `status` LIKE 'approved' AND DATE(registration_time) <= '2019-04-07' and id=".$_SESSION["affid"], 1);
			
		  if(count($affCheck)>0){
			  return $this->db->getRecords("id", "er_agree_terms", "affid=".$_SESSION["affid"], 1);
			} else {
				return 1;
		  }
	}
	
	
    public function doLogin($email, $password,$cityName,$countryName,$countryCode,$regionName,$zipCode,$latitude,$longitude)
    {
        /*if($email == 'erika@adgenics.com'){
            $email = 'erika@adgenicc.com';
        }else{
            $email = $email;
        }*/
        //Check if not Denied Account
		$res = $this->db->getRecords("null", "er_affiliates", "status!='Denied' and email='" . $email . "' and password='" . md5($password) . "'", 1);
        
		//Additional Login aff
		if (count($res) == 0) {
			
			
			//if any other login register
            $res1 = $this->db->getRecords("null", "er_additionallogin", "username='" . $email . "' and password='" . md5($password) . "'", 1);
		    if (count($res1) == 0) {
                echo "0";
            } else {
                $_SESSION["afffirstname"] = $res1["firstname"];
                $_SESSION["afflastname"] = $res1["lastname"];
                $_SESSION["affname"] = $res1["firstname"] . " " . $res1["lastname"];
                $_SESSION["affid"] = $res1["affiliateid"];
                $_SESSION["affemail"] = $res1["username"];
                $_SESSION["usertype"] = "Affiliate";
                $_SESSION["affman"] = $res1["aff"];
                $_SESSION["subaff"] = $res1["id"];
				
				//Check Payment section, if filled
                $res2 = $this->db->getRecords("id", "er_payment_details", "affiliate_id =" . $_SESSION["affid"], 1);
				
				
				//Affilaite Last Login
				$checkdata12 = $this->db->getRecords("*", "er_Affiliate_lastLogin", "affiliateID=".$res1['id'], 1);
				if(count($checkdata12)>0){
					$dbfieldslog12 = array(affiliateID, lastLogin);
                	$formfieldslog12 = array($res1["id"], DATE);
					$this->db->updateRecord("er_Affiliate_lastLogin", $dbfieldslog, $formfieldslog, "affiliateID=" . $res1["id"]);
					}
				else {
					$dbfieldslog12 = array(affiliateID, lastLogin);
                	$formfieldslog12 = array($res1["id"], DATE);
					$this->db->insertRecord("er_Affiliate_lastLogin", $dbfieldslog, $formfieldslog);
					}
					
					
				//GET IP INFO
				$resip = $this->ip->getCity($_SERVER['REMOTE_ADDR']);
				
                $dbfieldsLL = array(userid, ipaddress, city, country, logged_time, usertype, user,countryCode,regionName,zipCode,latitude,longitude);
                $formfieldsLL = array($res1["affiliateid"], $_SERVER['REMOTE_ADDR'], $cityName ,$countryName, DATE, "Affiliate", $res1["id"],$countryCode,$regionName,$zipCode,$latitude,$longitude);
                $this->db->insertRecord("er_logged", $dbfieldsLL, $formfieldsLL); 
				
			    //RES2 Confirm good user
                if ($res2["id"] == "") {
                    echo "2";
                } else {
                    echo "1";
                }
				
                
            }
        } else {
            if ($res["status"] == "Approved") {
                $_SESSION["afffirstname"] = $res["first_name"];
                $_SESSION["afflastname"] = $res["last_name"];
                $_SESSION["affname"] = $res["first_name"] . " " . $res["last_name"];
                $_SESSION["affid"] = $res["id"];
                $_SESSION["affemail"] = $res["email"];
                $_SESSION["usertype"] = "Affiliate";
                $_SESSION["affman"] = $res["aff"];
                $_SESSION["subaff"] = "Mainuser";
                $_SESSION["pixel"] = $res["allowPixelUpload"];
				
				//Check Payment Section if filled
                $res3 = $this->db->getRecords("id", "er_payment_details", "affiliate_id =" . $_SESSION["affid"], 1);
				
				//Get last logged in
				$checkdata = $this->db->getRecords("*", "er_Affiliate_lastLogin", "affiliateID=".$res['id'], 1);
				
				//
				if(count($checkdata)>0){
					$dbfieldslog = array(affiliateID, lastLogin);
                	$formfieldslog = array($res["id"], DATE);
					$this->db->updateRecord("er_Affiliate_lastLogin", $dbfieldslog, $formfieldslog, "affiliateID=" . $res["id"]);
					}
				else {
					$dbfieldslog = array(affiliateID, lastLogin);
                	$formfieldslog = array($res["id"], DATE);
					$this->db->insertRecord("er_Affiliate_lastLogin", $dbfieldslog, $formfieldslog);
					}
					
				//GET IP INFO
                $dbfieldsLL = array(userid, ipaddress, city, country, logged_time, usertype, user,countryCode,regionName,zipCode,latitude,longitude);

                $formfieldsLL = array($res["id"], $_SERVER['REMOTE_ADDR'], $cityName ,$countryName, DATE, "Affiliate", "Main user",$countryCode,$regionName,$zipCode,$latitude,$longitude);
                $this->db->insertRecord("er_logged", $dbfieldsLL, $formfieldsLL); 
				
				//RES3 ID
				if ($res3["id"] == "") {
					echo "2";
				} else {
					echo "1";
				}
				
            } else {
                $_SESSION["userid"] = $res["id"];
                $_SESSION["usertype"] = $res["status"];
				
				
				$checkdata34 = $this->db->getRecords("*", "er_Affiliate_lastLogin", "affiliateID=".$res['id'], 1);
				if(count($checkdata34)>0){
					$dbfieldslog34 = array(affiliateID, lastLogin);
                	$formfieldslog34 = array($res["id"], DATE);
					$this->db->updateRecord("er_Affiliate_lastLogin", $dbfieldslog, $formfieldslog, "affiliateID=" . $res["id"]);
					}
				else {
					$dbfieldslog34 = array(affiliateID, lastLogin);
                	$formfieldslog34  = array($res["id"], DATE);
					$this->db->insertRecord("er_Affiliate_lastLogin", $dbfieldslog, $formfieldslog);
					}
				
				
                if ($res["status"] == "Pending") {
                    echo "3";
                } else {
                    echo "4";
                }
            }
        }
    }

	 public function allowedtoLogin()
    {
        $res = $this->db->getRecords("allow_login_to_users", "er_admin", "id like '". $_SESSION["userid"]."' and allow_login_to_users IN ('1','3') ", 1);
        if (count($res["allow_login_to_users"]) > 0) {
                return true;
        } else {
           		return false;
        }
    }
	
    public function doadminLogin($affid)
    {
        $res = $this->db->getRecords("null", "er_affiliates", "status!='Denied' and id=" . $affid, 1);
        if ($res["status"] == "Approved") {
            $_SESSION["afffirstname"] = $res["first_name"];
            $_SESSION["afflastname"] = $res["last_name"];
            $_SESSION["affname"] = $res["first_name"] . " " . $res["last_name"];
            $_SESSION["affid"] = $res["id"];
            $_SESSION["affemail"] = $res["email"];
            $_SESSION["usertype"] = "Affiliate";
            $_SESSION["affman"] = $res["aff"];
            $_SESSION["subaff"] = "Mainuser";
            $_SESSION["pixel"] = $res["allowPixelUpload"];
            $res3 = $this->db->getRecords("id", "er_payment_details", "affiliate_id =" . $_SESSION["affid"], 1);

            if ($res3["id"] == "") {
                return "2";
            } else {
                return "1";
            }
        } else {
            $_SESSION["userid"] = $res["id"];
            $_SESSION["usertype"] = $res["status"];
            if ($res["status"] == "Pending") {
                return "3";
            } else {
                return "4";
            }
        }
    }

    public function forgotPassword($emaill)
    {
		$email = mysql_real_escape_string($emaill);
		
        $where = "email ='" . $email . "'";
        $res = $this->db->getRecords("null", "er_affiliates", $where, 1);
        if (empty($res)) {
            return 'Invalid E-Mail';
        } else {
            $phpmailer = new phpmailer();
			$affname = $res["first_name"] . " " . $res["last_name"];
            $allowable_characters = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
            $ps_len = strlen($allowable_characters);
            mt_srand((double)microtime() * 1000000);
            $pass = "";
            $length = 7;
            for ($i = 0; $i < $length; $i++) {
                $pass .= $allowable_characters[mt_rand(0, $ps_len - 1)];
            }
            $dbfields = array("password");
            $formfields = array(md5($pass));
            $this->db->updateRecord("er_affiliates", $dbfields, $formfields, "id=" . $res["id"]);
            $temp = $this->db->getRecords("*", "er_email_templates", "title='Affiliate Forgot Password'", 1);
			
            $emailcontent1 = $temp["message"];
            $emailcontent2 = str_replace('||name||', $res["first_name"], $emailcontent1);
            $emailcontent3 = str_replace('||password||', $pass, $emailcontent2);
            $emailmessageRes =  $this->emailTemplate->template($emailcontent3);			
			
			$phpmailer->IsHTML(true); 
			$phpmailer->setFrom($temp["from_email"], 'Express Revenue');
			$phpmailer->addAddress($email,$affname);
			$phpmailer->Subject = $temp["subject"];
			$phpmailer->Body = $emailmessageRes;
			//If email is text only -> $mail->AltBody = 'Statements have been generated';
			
			
			if(!$phpmailer->Send()) {
			echo 'Message could not be sent.';
			echo 'Mailer Error: ' . $mail->ErrorInfo;
			exit;
			}
			
            return 'ok';
            exit();
        }
    }

    public function changePassword($oldpassword, $newpassword, $confirmpassword)
    {
        if ($_SESSION["subaff"] == "Mainuser") {
            $passres = $this->db->getRecords("password", "er_affiliates", "password='" . md5($oldpassword) . "' and id=" . $_SESSION["affid"], 1);
           // var_dump($passres);
		    echo "<script>console.log( 'Debug Objects: 1' );</script>";
			
			if (count($passres) == 0) {
                $message = '<span class="error1">The current password you have entered does not match what Express Revenue currently has on file for your account.<br>Please try again or contact your Affiliate Manager for more info.</span>';
            } else {
                if ($newpassword != $confirmpassword) {
                    $message = '<span class="error1">New Password and Confirm Password does not match.</span>';
                } else {
                    $arr = array("password");
                    $arr1 = array(md5($newpassword));
                    $this->db->updateRecord("er_affiliates", $arr, $arr1, "id=" . $_SESSION["affid"]);

					$temp = $this->db->getRecords("*", "er_email_templates", "id='20' ", 1);
			
					$emailcontent1 = $temp["message"];
					$emailcontent2 = str_replace('||username||', $_SESSION["affemail"], $emailcontent1);
					$emailcontent3 = str_replace('||password||', $newpassword, $emailcontent2);
					$emailcontent4 = str_replace('||name||', $newpassword, $emailcontent3);
					$emailmessageRes =  $this->emailTemplate->template($emailcontent4);			
					
					$phpmailer = new phpmailer();
					$phpmailer->IsHTML(true); 
					$phpmailer->setFrom($temp["from_email"], 'Express Revenue');
					$phpmailer->addAddress($_SESSION["affemail"],'Affialite');
					$phpmailer->Subject = $temp["subject"];
					$phpmailer->Body = $emailmessageRes;
					
					if(!$phpmailer->Send()) {
					echo 'Message could not be sent.';
					echo 'Mailer Error: ' . $mail->ErrorInfo;
					exit;
					}
			
					$message = '<font color="#028C01">Password has been changed</font>';
					
                }
            }
            return $message;
        } else {
            $passres = $this->db->getRecords("password", "er_additionallogin", "password='" . md5($oldpassword) . "' and username='" . $_SESSION["affemail"] . "'", 1);
            if (count($passres) == 0) {
                $message = '<span class="error1">the current password you have entered does not match what Express Revenue currently has on file for your account.<br>Please try again or contact your Affiliate Manager for more info.</span>';
            } else {
                if (strcmp($newpassword, $confirmpassword) != 0) {
                    $message = '<span class="error1">New Password and Confirm Password does not match.</span>';
                } else {
                    $arr = array("password");
                    $arr1 = array(md5($newpassword));
                    $this->db->updateRecord("er_additionallogin", $arr, $arr1, "username='" . $_SESSION["affemail"] . "'");
                  
					$temp = $this->db->getRecords("*", "er_email_templates", "id='20' ", 1);
			
					$emailcontent1 = $temp["message"];
					$emailcontent2 = str_replace('||username||', $_SESSION["affemail"], $emailcontent1);
					$emailcontent3 = str_replace('||password||', $newpassword, $emailcontent2);
					$emailmessageRes =  $this->emailTemplate->template($emailcontent3);			
					
					$phpmailer = new phpmailer();
					$phpmailer->IsHTML(true); 
					$phpmailer->setFrom($temp["from_email"], 'Express Revenue');
					$phpmailer->addAddress($_SESSION["affemail"],$_SESSION["affname"]);
					$phpmailer->Subject = $temp["subject"];
					$phpmailer->Body = $emailmessageRes;
					
					if(!$phpmailer->Send()) {
					echo 'Message could not be sent.';
					echo 'Mailer Error: ' . $mail->ErrorInfo;
					exit;
					}
					
					
					$message = 'ok';
                }
            }
            return $message;
        }
    }

    public function viewAffman()
    {
        return $this->db->getRecords("null", "er_admin", "id=" . $_SESSION["affman"], 1);
    }

    public function getfield($field)
    {
        return $this->db->getRecords($field . " as field", "er_affiliates", "id=" . $_SESSION["affid"], 1);
    }

    public function updatefield($field)
    {
        $dbfield = array($field);
        $formfield = array($_POST["field"]);
        $this->db->updateRecord("er_affiliates", $dbfield, $formfield, "id=" . $_SESSION["affid"]);
        return "ok";
    }

    public function referral()
    {
        return $this->db->getRecords("null", "er_affiliates", "status='Approved' and referred_by=" . $_SESSION["affid"], 0);
    }

    public function reftotBal($affid)
    {
        return $this->db->getRecords("sum(amount) as balance", "er_referral_credits", "affiliate_id=" . $affid . " and referral_id=" . $_SESSION["affid"], 1);
    }

    public function insertSQL($sql)
    {
        if ($this->db->checkExists(userid, $_SESSION["affid"], "er_sql") == 0) {
            $dbfields = array(query, userid);
            $fields = array($sql, $_SESSION["affid"]);
            $this->db->insertRecord("er_sql", $dbfields, $fields);
        } else {
            $dbfields = array(query);
            $fields = array($sql);
            $this->db->updateRecord("er_sql", $dbfields, $fields, "userid=" . $_SESSION["affid"]);
        }
        return "ok";
    }

    public function getAMDetails($admin_id)
    {
        return $this->db->getRecords("*", "er_admin", "id = " . $admin_id . "", '');
    }

    public function getAffilateAM($aff_id)
    {
        return $this->db->getRecords("*", "er_affiliates", "id = " . $aff_id . "", '');
    }

    public function updateAffNotifications($notification_email, $notification_st)
    {

        $res = mysql_query("update er_affiliates set notification_email ='" . $notification_email . "',notification_st = '" . $notification_st . "' where id=" . $_SESSION["affid"] . "");
        return $res;
    }

    public function insertPayment($POST)
    {

        $res = mysql_query("update er_affiliates set payment_method ='" . $POST['payment_method'] . "' where id=" . $_SESSION["affid"] . "");


        if ($POST['payment_method'] == "wire") {

            $dbfields = array(affiliate_id, bank_name, branch, address, phone_number, account_name, account_no, bank_routing_no, SWIFT_code);
            $fields = array($_SESSION["affid"], $POST['bank_name'], $POST['branch'], $POST['address'], $POST['phone_number'], $POST['account_name'], $POST['account_no'], $POST['bank_routing_no'], $POST['SWIFT_code']);

            $this->db->deleteRecord("er_paypal", $affiliate_id, $_SESSION["affid"]);
            $this->db->deleteRecord("er_check", $affiliate_id, $_SESSION["affid"]);
            $this->db->deleteRecord("er_wire", $affiliate_id, $_SESSION["affid"]);

            $this->db->insertRecord("er_wire", $dbfields, $fields);
            $this->db->deleteRecord("er_paypal", $affiliate_id, $_SESSION["affid"]);
            $this->db->deleteRecord("er_check", $affiliate_id, $_SESSION["affid"]);

        }

        if ($POST['payment_method'] == "check") {
            $dbfields = array(affiliate_id, payto_check, payment_address);
            $fields = array($_SESSION["affid"], $POST['payto_check'], $POST['check_address']);

            $this->db->deleteRecord("er_paypal", $affiliate_id, $_SESSION["affid"]);
            $this->db->deleteRecord("er_check", $affiliate_id, $_SESSION["affid"]);
            $this->db->deleteRecord("er_wire", $affiliate_id, $_SESSION["affid"]);


            $this->db->insertRecord("er_check", $dbfields, $fields);

        }


        if ($POST['payment_method'] == "paypal") {
            $dbfields = array(affiliate_id, paypal_email, payment_address);
            $fields = array($_SESSION["affid"], $POST['paypal_email'], $POST['payment_address']);

            $this->db->deleteRecord("er_paypal", $affiliate_id, $_SESSION["affid"]);
            $this->db->deleteRecord("er_check", $affiliate_id, $_SESSION["affid"]);
            $this->db->deleteRecord("er_wire", $affiliate_id, $_SESSION["affid"]);


            $this->db->insertRecord("er_paypal", $dbfields, $fields);


        }


    }

    public function payment($status, $limit)
    {
        return $this->db->getRecords("*", "er_statements", "affiliate_id = " . $_SESSION["affid"] . " and status='" . $status . "' order by sent_date desc", $limit);

    }

    public function newSupportTickets($answered, $limit)
    {
        return $this->db->getRecords("*", "er_support", "affiliate_id = " . $_SESSION["affid"] . " and status='open' order by message_date desc", $limit);
    }

    public function getLogin($num)
    {
        return $this->db->getRecords("null", "er_logged", "userid=" . $_SESSION["affid"] . " and user='" . $_SESSION["subaff"] . "' and usertype='Affiliate' order by id desc", $num);
    }

    public function getLastPayment()
    {

        return $this->db->getRecords("*", "er_statements", "affiliate_id = " . $_SESSION["affid"] . " and status='shipped' order by sent_date desc", 1);


    }

    public function getAffappPayment()
    {

        return $this->db->getRecords("sum(amount) as tot", "er_statements", "affiliate_id = " . $_SESSION["affid"] . " and status='approved'", '');


    }

    public function getAffPayment()
    {

        return $this->db->getRecords("sum(amount) as tot", "er_statements", "affiliate_id = " . $_SESSION["affid"] . " and status='shipped'", '');


    }

    public function getPaymentdet()
    {

        return $this->db->getRecords("*", "er_statements", "affiliate_id = " . $_SESSION["affid"] . " and status='shipped'", '');


    }

    public function getPaymentdetail($id,$affid)
    {

        return $this->db->getRecords("*", "er_statements", "affiliate_id =".$affid." AND id =" . $id, 1);


    }

    public function getWireDetails()
    {

        return $this->db->getRecords("*", "er_wire", "affiliate_id = " . $_SESSION["affid"] . " ", '');


    }

    public function getPaypal()
    {

        return $this->db->getRecords("*", "er_paypal", "affiliate_id = " . $_SESSION["affid"] . " ", '');


    }

    public function getCheck()
    {

        return $this->db->getRecords("*", "er_check", "affiliate_id = " . $_SESSION["affid"] . "", '');


    }

    public function updatePaymentInfo($POST)
    {

        $dbfield = array(payment_method, schedule, ein);
        $formfield = array($POST['payment_method'], $POST['schedule'], $POST['ein']);
        $this->db->updateRecord("er_affiliates", $dbfield, $formfield, "id=" . $_SESSION["affid"]);


        if ($POST['payment_method'] == "Wire") {
            $dbfield = array(account_name, branch, bank_name, account_no, address, phone_number, bank_routing_no, SWIFT_code);
            $formfield = array($POST['pay_to'], $POST['branch'], $POST['bank_name'], $POST['account_no'], $POST['address'], $POST['phone_number'], $POST['bank_routing_no'], $POST['SWIFT_code']);
            return $this->db->updateRecord("er_wire", $dbfield, $formfield, "affiliate_id=" . $_SESSION["affid"]);
        }
        if ($POST['payment_method'] == "Check") {
            $dbfield = array(payto_check);
            $formfield = array($POST['pay_to']);
            return $this->db->updateRecord("er_check", $dbfield, $formfield, "affiliate_id=" . $_SESSION["affid"]);
        }
        if ($POST['payment_method'] == "Paypal") {
            $dbfield = array(paypal_email);
            $formfield = array($POST["paypal_email"]);
            return $this->db->updateRecord("er_paypal", $dbfield, $formfield, "affiliate_id=" . $_SESSION["affid"]);
        }


    }

    public function checkaffPass($password)
    {
        $res = $this->db->getRecords("password", "er_affiliates", "password='" . md5($password) . "' and id='" . $_SESSION['affid'] . "'", '1');
        if ($res["password"] != "") {
            return 'ok';
        } else {
            return '<span class="error1">Invalid password</span>';
        }
    }

    public function checkPassword()
    {

        $res = $this->db->getRecords("*", "er_affiliates", "id='" . $_SESSION['affid'] . "'", '');
        foreach ($res as $val) {
            $pass = $val['password'];

        }
        return $pass;

    }

    public function getPassword($password)
    {
        $res = $this->db->getRecords("null", "er_affiliates", "password='" . $password . "' and id=" . $_SESSION['affid'], 1);
        if ($res["password"] != "") {
            return 1;
        } else {
            return 0;
        }
    }

    public function getStatement($id)
    {
        return $this->db->getRecords("*", "er_statements", "id = " . $id . "", '');
    }

    public function updateStateStatus($id)
    {
        $field = array(status);
        $value = array('Pending');
        return $this->db->updateRecord("er_statements", $field, $value, "id=" . $id);
    }

    public function getTraffic($userid)
    {
        return $this->db->getRecords("null", "er_traffic_sources", "affiliate_id=" . $userid . " order by id desc", 0);
    }

    public function addTraffic($arr1, $arr2)
    {
        if ($this->db->checkExists(affiliate_id, $_SESSION["affid"], "er_traffic_sources") == 0) {
            $default = "Yes";
        } else {
            $default = "No";
        }
        array_push($arr1, "isdefault");
        array_push($arr2, $default);
        if ($this->db->checkExists(url, $_POST["url"], "er_traffic_sources") == 0) {
            $this->db->insertRecord("er_traffic_sources", $arr1, $arr2);
            return "1";
        } else {
            return "<span class='error1'>URL already exist</span>";
        }
    }

    public function updateTraffic($arr1, $arr2, $id)
    {
        if ($this->db->checkExists1(url, $_POST["url"], "er_traffic_sources", "id!=" . $_POST["id"]) == 0) {
            $this->db->updateRecord("er_traffic_sources", $arr1, $arr2, "id=" . $id);
            return "2";
        } else {
            return "<span class='error1'>URL already exist</span>";
        }
    }

    public function editTraffic($id)
    {
        return $this->db->getRecords("null", "er_traffic_sources", "id=" . $id, 1);
    }

    public function defTraffic($id)
    {
        $arr1 = array(isdefault);
        $arr2 = array("No");
        $this->db->updateRecord("er_traffic_sources", $arr1, $arr2, "affiliate_id=" . $_SESSION["affid"]);
        $arr3 = array(isdefault);
        $arr4 = array("Yes");
        $this->db->updateRecord("er_traffic_sources", $arr3, $arr4, "id=" . $id);
    }

    public function deleteTraffic($id)
    {
        $this->db->deleteRecord("er_traffic_sources", "id", $id);
    }

    public function updateProfile($arr, $arr2)
    {
        $this->db->updateRecord("er_affiliates", $arr, $arr2, "id=" . $_SESSION["affid"]);
    }

    public function addLogin($firstname, $lastname, $username, $title, $password, $affman)
    {
        if ($this->db->checkExists(username, $username, "er_additionallogin") == 0) {
            if ($this->db->checkExists(email, $username, "er_affiliates") == 0) {
                $arr = array(affiliateid, firstname, lastname, username, title, password, aff);
                $arr2 = array($_SESSION["affid"], $firstname, $lastname, $username, $title, $password, $affman);
                $this->db->insertRecord("er_additionallogin", $arr, $arr2);
                return 'ok';
            } else {
                return '<span class="error1">This e-mail address already exists in our system. Please enter a new e-mail.</span>';
            }
        } else {
            return '<span class="error1">This e-mail address already exists in our system. Please enter a new e-mail.</span>';
        }
    }

    public function getotherAccount($id)
    {
        return $this->db->getRecords("null", "er_additionallogin", "affiliateid=" . $id, 0);
    }

    public function viewNotification()
    {
        return $this->db->getRecords("null", "er_notifications", "null", 0);
    }

    public function checkNotification($id)
    {
        return $this->db->getRecords("null", "er_aff_note", "affid=" . $_SESSION["affid"] . " and noteid=" . $id, 1);
    }

    public function addNotification()
    {
        $this->db->deleteRecord("er_aff_note", "affid", $_SESSION["affid"]);
        for ($i = 0; $i < count($_POST["notify"]); $i++) {
            $arr = array(affid, noteid);
            $arr1 = array($_SESSION["affid"], $_POST["notify"][$i]);
            $this->db->insertRecord("er_aff_note", $arr, $arr1);
        }
    }

    public function checkaffExist($email)
    {
        if ($this->db->checkExists(email, $email, "er_affiliates") == 0) {
            if ($this->db->checkExists(username, $email, "er_additionallogin") == 0) {
                return 0;
            } else {
                return 1;
            }
        } else {
            return 1;
        }
    }
    public function register($data)
    {
		$category_interest = filter_var_array($data['category_interest'], FILTER_SANITIZE_STRING);	
		$category_i_m_p = mysql_real_escape_string($data['category_interest_method_promote']);
		$demail = mysql_real_escape_string($data['email']);
		$dpassword = mysql_real_escape_string($data['password']);
		$dfirst_name = mysql_real_escape_string($data['first_name']);
		$dlast_name = mysql_real_escape_string($data['last_name']);
		$dcompany = mysql_real_escape_string($data['company']);
		$dtitle = mysql_real_escape_string($data['title']);
		$daddress = mysql_real_escape_string($data['address']);
		$daddress2 = mysql_real_escape_string($data['address2']);
		$dcity = mysql_real_escape_string($data['city']);
		$dstate = mysql_real_escape_string($data['state']);
		$dzip = mysql_real_escape_string($data['zip']);
		$dcountry = mysql_real_escape_string($data['country']);
		$dphone = mysql_real_escape_string($data['phone']);
		$dphone2 = mysql_real_escape_string($data['phone2']);
		$dfax = mysql_real_escape_string($data['fax']);
		$dIMType = mysql_real_escape_string($data['IMType']);
		$dIMScreenName = mysql_real_escape_string($data['IMScreenName']);
		$dpreferredContactType = mysql_real_escape_string($data['preferredContactType']);
		$dpreferredContactTime = mysql_real_escape_string($data['preferredContactTime']);
		$dexperience = mysql_real_escape_string($data['experience']);
		$dcompany_info = mysql_real_escape_string($data['company_info']);
		$durl_you_own = mysql_real_escape_string($data['url_you_own']);
		$dplan_on_using_Express = mysql_real_escape_string($data['plan_on_using_Express']);
		$dlooking_to_Promote = mysql_real_escape_string($data['looking_to_Promote']);
		$dother_cpa_aff_networks_working_with = mysql_real_escape_string($data['other_cpa_aff_networks_working_with']);
		$dhear_about_us_aff = mysql_real_escape_string($data['hear_about_us_aff']);
		$drevenue_monthly_aff = mysql_real_escape_string($data['revenue_monthly_aff']);
		$dnotes = mysql_real_escape_string($data['notes']);

        $category_interest = implode(",", $category_interest);
		$category_interest_method_promote = implode(",", $category_i_m_p);
        if (isset($_COOKIE["er_refer"]) && $_COOKIE["er_refer"] != "") {
            $referer = $_COOKIE["er_refer"];
        } else {
            $referer = 0;
        }
		
        $arr = array('`status`', registration_time, registration_ip, email, '`password`', first_name, last_name, company, title_company, address, address2, city, state, zip, country, phone, phone2, fax, IMType, IMScreenName, preferredContactType, preferredContactTime, experience, category_interest, category_interest_method_promote, referred_by, company_info, url_you_own, plan_on_using_Express, looking_to_Promote, other_cpa_aff_networks_working_with, hear_about_us_aff, revenue_monthly_aff,  notes);
        $arr1 = array('Pending', DATE, $_SERVER['REMOTE_ADDR'], $demail, md5($dpassword), $dfirst_name, $dlast_name, $dcompany, $dtitle, $daddress, $daddress2, $dcity, $dstate, $dzip, $dcountry, $dphone, $dphone2,$dfax, $dIMType, $dIMScreenName, $dpreferredContactType, $dpreferredContactTime, $dexperience, $category_interest, $category_interest_method_promote, $referer, $dcompany_info,$durl_you_own, $dplan_on_using_Express,$dlooking_to_Promote, $dother_cpa_aff_networks_working_with, $dhear_about_us_aff, $drevenue_monthly_aff, $dnotes);
        $affid = $this->db->insertRecord("er_affiliates", $arr, $arr1);

		
		$notify = $this->db->getRecords("*", "er_notifications", "id!=note", 0);
		for ($i = 1; $i <= count($notify); $i++) {
			$arr = array(affid,noteid);
			$arr1 = array($affid,$i);
			$this->db->insertRecord("er_aff_note", $arr, $arr1);
		}

		
		$temp = $this->db->getRecords("*", "er_email_templates", "title='Affiliate registration pending'", 1);
        
		$from = $temp["from_email"];
        $subject = $temp["subject"];
        $emailcontent = $temp["message"];
		
        
		$emailcontent = str_replace('||name||', $dfirst_name, $emailcontent);
        $emailcontent = str_replace('||email||', $demail, $emailcontent);
        $emailcontent3 = str_replace('||password||', $dpassword, $emailcontent);
        $emailmessageRes =  $this->emailTemplate->template($emailcontent3);	       
	   
		$phpmailer = new phpmailer();
		
		$phpmailer->IsHTML(true); 
		$phpmailer->setFrom($from, 'Express Revenue');
		$phpmailer->addAddress($data["email"]);
		$phpmailer->Subject = $subject;
		$phpmailer->Body = $emailmessageRes;
		
		if(!$phpmailer->Send()) {
		echo 'Message could not be sent.';
		echo 'Mailer Error: ' . $mail->ErrorInfo;
		exit;
		}
	   
		
		
	    return 'ok';
    }

    public function checkEmail($name, $email, $code)
    {

        $site = "Express Revenue";
        $from = "Advertising@ExpressRevenue.com";
        $subject = "Express Revenue, Inc.  Advertiser information received";
        $message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>

<body>
<p>Hi ' . $name . ',</p>
<p>confirmation email. for checking purpose!</p>
<p>Your confirmation code is ' . $code . '.Take this code and paste in confirmation code for checking purpose.</p>
<p>Regards,<br />
  lakshmi<br />
  Senior  Advertiser Manager - Express Revenue, Inc.<br />
  1-877-4968  ext. 501<br />
  Advertising@ExpressRevenue.com</p>

</body>
</html>
';


        $headers = 'From: ' . $site . '<' . $from . ' >' . "\n";
        $headers .= 'Return-Path: ' . $site . "\n";
        $headers .= 'Reply-To:' . $site . '<' . $from . ' >' . "\n";

        $headers .= 'MIME-Version: 1.0' . "\n";
        $headers .= 'Content-Type: text/HTML; charset=ISO-8859-1' . "\n";
        $headers .= 'Content-Transfer-Encoding: 8bit' . "\n\n";
        echo $message;


        if (mail($email, $subject, $message, $headers))
            return $code;
        else
            return false;


    }

    /**
     * @param $affiliate_id
     * @param int $offer_id
     * @param string $subid
     * @param string $date_range
     * @return array
     *
     * Get the result of all clicks for given parameters
     */
    public function getAffiliateLeads($affiliate_id, $offer_id=array(), $subid=array(), $date_range='', $d=false) {
        $where = "l.affiliate_id = $affiliate_id AND l.campaign_id = c.id ";

        $offer_where = '';
        if(count($offer_id)>1) {
            // get all subids
            $offerin = '';
            foreach($offer_id as $offerSub) {
                $offerin .= "'" . $offerSub . "',";
            }
            $offerin = substr($offerin,0,-1);
            if(!empty($offerin)) {
                $offer_where = "AND l.campaign_id IN ($offerin) ";
            }
        }else{
            $offer_where = "AND l.campaign_id = $offer_id ";
        }
		
		
        // build out the subwhere search
        $subwhere = '';
        if(count($subid)) {
            // get all subids
            $subin = '';
            foreach($subid as $sub) {
                $subin .= "'" . $sub . "',";
            }
            $subin = substr($subin,0,-1);
            if(!empty($subin)) {
                $subwhere = "AND (sub_id IN ($subin) OR sub_id2 IN ($subin) OR sub_id3 IN ($subin)) ";
            }
        }
        $date = '';
        switch($date_range){
            case "PSD":
                $date="AND DATE(l.click_time)>'".date('Y-m-d', strtotime("-7 days",strtotime(DATEONLY)))."'";
                break;
            case "MTD":
                $date="AND MONTH(l.click_time)='".MONTH."' and year(l.click_time)='".YEAR."'";
                break;
            case "YTD":
                $date="AND YEAR(l.click_time)='".YEAR."'";
                break;
            case "lastyear":
                $year=YEAR-1;
                $date="AND YEAR(l.click_time)='".$year."'";
                break;
            case "daily":
                $date="AND DATE(l.click_time)='".DATEONLY."'";
                break;
            case "yesterday":
                $date="AND DATE(l.click_time)='".date('Y-m-d', strtotime("-1 day",strtotime(DATEONLY)))."'";
                break;
            case "custom":
                $date="AND DATE(l.click_time) BETWEEN '".date("Y-m-d",strtotime($_POST["start_date"]))."' AND '".date("Y-m-d",strtotime($_POST["end_date"]))."'";
                break;
            case "custom1":
                $date="AND DATE(l.click_time) BETWEEN '".date("Y-m-d",strtotime($_REQUEST["start_date"]))."' AND '".date("Y-m-d",strtotime($_REQUEST["end_date"]))."'";
                break;
        }


        $where .= "$offer_where $date $subwhere ORDER BY l.click_time DESC";
		if($d){
			return $where;	
		}
        $leads = $this->db->getRecords("l.*, c.name AS campaign_name", "er_leads l, er_campaigns c", $where, 0);
        return $leads;
    }
	
	
	
	public function getAffiliateLeadsNew($affiliate_id, $offer_id=array(), $subid=array(), $date_range='', $d=false) {
        //$where = "l.affiliate_id = $affiliate_id AND l.campaign_id = c.id ";
		$offer_where = '';
    
	
        if(count($offer_id)) {
            $offeridSub = '';
            foreach($offer_id as $val) {
                $offeridSub .= "'" . $val . "',";
            }
            $offerIdd = substr($offeridSub,0,-1);
            if(!empty($offerIdd)) {
                $offer_where = " AND l.campaign_id IN ($offerIdd)";
            }
        }
		
        // build out the subwhere search
        $subwhere = '';
        if(count($subid)) {
            // get all subids
            $subin = '';
            foreach($subid as $sub) {
                $subin .= "'" . $sub . "',";
            }
            $subin = substr($subin,0,-1);
            if(!empty($subin)) {
                $subwhere = " AND (sub_id IN ($subin) OR sub_id2 IN ($subin) OR sub_id3 IN ($subin)) ";
            }
        }
        $date = '';
         switch($date_range){
            case "PSD":
                $date="AND DATE(l.click_time)>'".date('Y-m-d', strtotime("-7 days",strtotime(DATEONLY)))."'";
                break;
            case "MTD":
                $date="AND MONTH(l.click_time)='".MONTH."' and year(l.click_time)='".YEAR."'";
                break;
            case "YTD":
                $date="AND year(l.click_time)='".YEAR."'";
                break;
            case "lastyear":
                $year=YEAR-1;
                $date="AND YEAR(l.click_time)='".$year."'";
                break;
            case "daily":
                $date="AND DATE(l.click_time)='".DATEONLY."'";
                break;
            case "yesterday":
                $date="AND DATE(l.click_time)='".date('Y-m-d', strtotime("-1 day",strtotime(DATEONLY)))."'";
                break;
            case "custom":
                $date="AND DATE(l.click_time) BETWEEN '".date("Y-m-d",strtotime($_POST["start_date"]))."' AND '".date("Y-m-d",strtotime($_POST["end_date"]))."'";
                break;
            case "custom1":
                $date="AND DATE(l.click_time) BETWEEN '".date("Y-m-d",strtotime($_REQUEST["start_date"]))."' AND '".date("Y-m-d",strtotime($_REQUEST["end_date"]))."'";
                break;
        }

        //$where .= "$offer_where $date $subwhere ORDER BY l.click_time DESC";
        //$leads = $this->db->getRecords("l.affiliate_id, l.click_time,l.date_credited,l.campaign_id,l.cpa,l.status,l.sub_id,l.sub_id2,l.sub_id3,l.creative_id,l.ip_address, c.name AS campaign_name", "er_leads l, er_campaigns c", $where, 0);
        
		$que = " SELECT l.affiliate_id, l.click_time,l.date_credited,l.campaign_id,l.cpa,l.status,l.sub_id,l.sub_id2,l.sub_id3,l.creative_id,l.ip_address, c.name AS campaign_name
				FROM er_leads l, er_campaigns c
				where l.affiliate_id = '".$affiliate_id."' AND l.campaign_id = c.id ".$offer_where. $date. $subwhere." ORDER BY l.click_time DESC";
		if($d){
			return $que;	
		}
		$leads = $this->db->getRecordsOptimized($que);
		return $leads;
    }
	
	
	
	
	
	 public function getAffiliateLeadsAjax($affid, $campaignid = array(), $subid = array() , $period = '', $d=false) {
        $where = "l.affiliate_id = $affid AND l.campaign_id = c.id ";
		$offer_where = '';
		
        if(count($campaignid)==0||$campaignid[0]=="") {
            $offers = "";
            if(count($campaignid)!=0||$campaignid[0]==!"") {
                $offers = substr($offers, 0, -1);
            }
            $offer_where = "AND l.campaignid IN ($offers) ";
        } else if($campaignid) {
            $offer_where = "AND l.campaignid = $affid ";
        }
	 
		 // build out the subwhere search
		 if(count($subid)==0||$subid[0]=="") {
			$subwhere="";
		} else {
            $subin = '';
            foreach($subid as $sub) {
                $subin .= "'" . $sub . "',";
            }
            $subin = substr($subin,0,-1);
            $subwhere = "AND (sub_id IN ($subin) OR sub_id2 IN ($subin) OR sub_id3 IN ($subin)) ";
		}

        $date = '';
        switch($period){
            case "PSD":
                $date="AND DATE(l.click_time)>'".date('Y-m-d', strtotime("-7 days",strtotime(DATEONLY)))."'";
                break;
            case "MTD":
                $date="AND MONTH(l.click_time)='".MONTH."' and year(l.click_time)='".YEAR."'";
                break;
            case "YTD":
                $date="AND YEAR(l.click_time)='".YEAR."'";
                break;
            case "lastyear":
                $year=YEAR-1;
                $date="AND YEAR(l.click_time)='".$year."'";
                break;
            case "daily":
                $date="AND DATE(l.click_time)='".DATEONLY."'";
                break;
            case "yesterday":
                $date="AND DATE(l.click_time)='".date('Y-m-d', strtotime("-1 day",strtotime(DATEONLY)))."'";
                break;
            case "custom":
                $date="AND DATE(l.click_time) BETWEEN '".date("Y-m-d",strtotime($_POST["start_date"]))."' AND '".date("Y-m-d",strtotime($_POST["end_date"]))."'";
                break;
            case "custom1":
                $date="AND DATE(l.click_time) BETWEEN '".date("Y-m-d",strtotime($_REQUEST["start_date"]))."' AND '".date("Y-m-d",strtotime($_POST["end_date"]))."'";
                break;
			default:
			$date = '';
	        }


        $where .= "$offer_where $date $subwhere ORDER BY l.click_time DESC";
		if($d){
			return $where;	
		}
        $leads = $this->db->getRecords("l.*, c.name AS campaign_name", "er_leads l, er_campaigns c", $where, 0);
        return $leads;
    }
	 
	 
	 // these postbacks are the ones maintained by the affiliate in the tools section,
    // thus the placement in the affiliate class
    public function get_postbacks($affiliate_id, $campaign_id = 0) {
        $campaign_where = ($campaign_id ? " AND p.campaign_id = $campaign_id" : "");
        $postbacks = $this->db->getRecords("p.*, c.name AS campaign_name","er_postbacks p, er_campaigns c","p.affiliate_id = $affiliate_id $campaign_where AND p.campaign_id = c.id",0);
        return $postbacks;
    }

	 public function get_globalpostbacks($globalid,$affID) {
        $globalpostbacks = $this->db->getRecords("*","er_global_postbacks","affiliate_id=$affID and  id=".$globalid,1);
        return $globalpostbacks;
    }
	
	
	 public function checkget_globalpostbacks($affID) {
        return  $this->db->getRecords("*","er_global_postbacks","affiliate_id = $affID",1);
    }

    public function get_postback($id, $affID) {
        $postback = $this->db->getRecords("*", "er_postbacks", "id ='".$id."' and affiliate_id='".$affID."' ",1);
        return $postback;
    }

    public function save_postback($affiliate_id, $campaign_id, $url, $old_campaign_id = 0) {
        $exists = $this->get_postbacks($affiliate_id, $campaign_id);
        $db_fields = array('affiliate_id', 'campaign_id', 'url');
        $form_fields = array($affiliate_id, $campaign_id, $url);
        if(is_array($exists)) {
            // update the record with the new information
            $this->db->updateRecord("er_postbacks", $db_fields, $form_fields, 'id = '.$exists[0]['id']);
        } else {
            // add the record
            if($old_campaign_id) {
                // get the existing record so we can update it
                $exists = $this->get_postbacks($affiliate_id, $old_campaign_id);
                if($exists) {
                    // update $form_fields to use the old
                    $this->db->updateRecord("er_postbacks", $db_fields, $form_fields, 'id = '.$exists[0]['id']);
                } else {
                    $this->db->insertRecord("er_postbacks", $db_fields, $form_fields);
                }
            } else {
                $this->db->insertRecord("er_postbacks", $db_fields, $form_fields);
            }
        }
    }

    public function delete_postback($affiliate_id, $campaign_id) {
        $exists = $this->get_postbacks($affiliate_id, $campaign_id);
        $this->db->deleteRecord("er_postbacks", "id", $exists[0]['id']);
    }
	
	 public function delete_globalpostback($globalid) {	 
		$exists = $this->db->getRecords("*","er_global_postbacks","id=".$globalid,1);		
        $this->db->deleteRecord("er_global_postbacks", "id", $exists['id']);
    }
	
	 public function deleteExtraUsers($id) {		 
              $this->db->deleteRecord("er_additionallogin","id", $id);
    }
	
	public function checkcpa($affid,$campid){	
		$que = "SELECT max(cpa) as cpa FROM er_custom_cpa WHERE offer_id=".$campid." and (affiliate_id=".$affid." or affiliate_id=0) LIMIT 1";
		return $this->db->getRecordsOptimized($que);
	}
	
	public function viewhotCamp10() {
		$que = "SELECT SUM(c.cpa) AS 'cpa_total',l.campaign_id ,c.cpa ,c.name ,c.date_added ,count(l.id) AS 'goodleads'
									FROM   er_leads l INNER JOIN er_campaigns c ON l.campaign_id = c.id
									WHERE  c.id = l.campaign_id 
											AND (l.status='Payable'|| l.status='Paid') 
											AND c.offer_type='approvalonly' 
											AND c.available='yes' 
											AND c.status='active' 
											GROUP BY l.campaign_id 
											ORDER BY sum(l.cpa)
											DESC LIMIT 10";
		return $this->db->getRecordsOptimized($que);

	}
	
	
	
	public function viewallCampaigns($status,$order,$limit=10){
		
		$que = "SELECT * FROM `er_campaigns` WHERE offer_type='approvalonly' AND status='".$status."' ORDER BY `er_campaigns`.`id` DESC LIMIT ". $limit;
		return $this->db->getRecordsOptimized($que);
	
	}
	
	
	
	/*public function TotalClicksperCampaign($campaignID) {
				$que = "SELECT l.campaign_id AS 'camp_id', c.name ,COUNT(DISTINCT(l.ip_address)) AS 'conversion'
								FROM er_leads l 
								INNER JOIN er_campaigns c ON l.campaign_id = c.id
								WHERE c.id in ('".$campaignID."')
								AND c.offer_type='approvalonly' 
								AND c.available='yes' 
								AND c.status='active' 
								GROUP BY l.campaign_id";
		return $this->db->getRecordsOptimized($que);
	}*/
	
	
	public function viewhotCampConversions() {
		$que = "SELECT c.name 'name'
									,sum(l.cpa)/count(l.id) AS epc
									,count(*) 'total_conversions'
									,l.campaign_id 'campaign_id'
									,c.date_added AS 'date_added' 
									,SUM(c.cpa) AS 'cpa_total'
									,c.cpa as 'cpa'
								FROM er_leads l
								INNER JOIN er_campaigns c ON l.campaign_id = c.id
								WHERE l.status IN ('Payable', 'Paid') 
										AND c.offer_type='approvalonly' 
										AND c.available='yes' 
										AND c.status='active' 
								GROUP BY l.campaign_id 
								ORDER BY  count(*)  DESC LIMIT 10";
		return $this->db->getRecordsOptimized($que);
	}
	
	
	public function viewcpaChange($limit){
		 
		$que = "SELECT er_custom_cpa.offer_id,er_custom_cpa.cpa as newcpa,er_campaigns.name,er_custom_cpa.offer_id,er_custom_cpa.time_filed,er_campaigns.cpa 	 as oldcpa FROM er_custom_cpa,er_campaigns WHERE er_campaigns.id=er_custom_cpa.offer_id and er_campaigns.available='yes' and er_campaigns.status='Active' and (er_custom_cpa.affiliate_id=0||er_custom_cpa.affiliate_id='".$_SESSION["affid"]."') order by er_custom_cpa.id desc LIMIT ".$limit;
		return $this->db->getRecordsOptimized($que);
	}
	
	
	
	
	
	
	
	
	
	
	
	

}

?>