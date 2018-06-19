<?php
/*
 * This controller includes all api for the user to login, forgot password, verification code and reset password
 *
 * @category   Restapi
 * @controller User controller
 * @package    Restapi_Api
 * @author     Betasoft Team 
*/
 
class Restapi_Api_UserController extends Mage_Core_Controller_Front_Action{	     

   
    /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    } // end function

    
    /**
     * Action predispatch
     *
     * Check customer authentication for some actions
     */
    public function preDispatch(){

        // a brute-force protection here would be nice

        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }

        $action = strtolower($this->getRequest()->getActionName());
        $openActions = array(
            'loginApi',
            'resetPasswordPostApi',
            'verifyCodeApi',
            'forgotPasswordPostApi'
        );
        $pattern = '/^(' . implode('|', $openActions) . ')/i';

        if (!preg_match($pattern, $action)) {
            if (!$this->_getSession()->authenticate($this)) {
                $this->setFlag('', 'no-dispatch', true);
            }
        } else {
            $this->_getSession()->setNoReferer(true);
        }
    } // end function

    /**
     * Action postdispatch
     *
     * Remove No-referer flag from customer session after each action
     */
    public function postDispatch()
    {
        parent::postDispatch();
        $this->_getSession()->unsNoReferer(false);
    } // end function


    /**
     * Action loginApiAction
     *
     * Will login user in api through post request with auth authentication
    */    

    public function loginApiAction(){

        //Receive the RAW post data via the php://input IO stream.        
        $postData = file_get_contents("php://input");
        if(!empty($postData)){
            $postData = json_decode($postData);
        }

        $session = $this->_getSession();
        $session->clear();

        $login['username'] = trim($postData->email_id);
        $login['password'] = trim($postData->password);
        
        if (!empty($login['username']) && !empty($login['password'])) {
            try {

                $session->login( $login['username'], $login['password']);
                //$data = $user->login( $login['username'], $login['password']);

                if ($session->getCustomer()->getIsJustConfirmed()) {
                    $this->_welcomeCustomer($session->getCustomer(), true);
                }

            } catch (Mage_Core_Exception $e) {

                switch ($e->getCode()) {

                    case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:

                    $value      = $this->_getHelper('customer')->getEmailConfirmationUrl($login['username']);
                    $message    = $this->_getHelper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                    break;
                    case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                    $message = $e->getMessage();
                    break;
                    default:
                    $message = $e->getMessage();
                }
                $session->addError($message);
                $session->setUsername($login['username']);
            } catch (Exception $e) {
            // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
            }
        }

        if( $session->getCustomer()->email){
            
            /********************************** auth component verification start here ******************************/

            $callbackUrl                    = Mage::getBaseUrl()."oauth_admin.php";
            $temporaryCredentialsRequestUrl = Mage::getBaseUrl()."oauth/initiate?oauth_callback=" . urlencode($callbackUrl);
            $adminAuthorizationUrl          = Mage::getBaseUrl().'admin/oAuth_authorize';
            $accessTokenRequestUrl          = Mage::getBaseUrl().'oauth/token';
            $apiUrl                         = Mage::getBaseUrl().'api/rest';
            $consumerKey                    = '6e0ef15f2a968e8ff0f9086b303dfb56';
            $consumerSecret                 = '70d793376bedee90e6bf91db5f2a6af0';

            $authType = ($_SESSION['state'] == 2) ? OAUTH_AUTH_TYPE_AUTHORIZATION : OAUTH_AUTH_TYPE_URI;
            $oauthClient = new OAuth($consumerKey, $consumerSecret, OAUTH_SIG_METHOD_HMACSHA1, $authType);
            $oauthClient->enableDebug();
            
            $requestToken = $oauthClient->getRequestToken($temporaryCredentialsRequestUrl);    
      

            /********************************** auth component verification end here ******************************/

            /****************************** fetch data respect to customer logged in *****************************/

            $customerID = $session->getCustomer()->getId(); 

            $data = array(             
                'name'         => $session->getCustomer()->firstname.' '.$session->getCustomer()->lastname,
                'email'        => $session->getCustomer()->email,
                'isVerified'   => $session->getCustomer()->getIsActive(),
                'user_id'      => $session->getCustomer()->getId(),
                'auth_token'   => $requestToken['oauth_token'], // auth key created after login
                'token_secret' => $requestToken['oauth_token_secret'], // auth secret 
                'auth_key'     => base64_encode(convert_uuencode($customerID)), // customer id in encrypted form
                'display_name' => $session->getCustomer()->getName()
            );

            $response = array(
                'success'=>1,
                'message'=>'Login successfully.',
                'data'=>$data
            );
        }else{

            // set response in case data not found
            if (!empty($login['username']) && !empty($login['password'])) {                
                $response = array('success'=>0,'message'=>$message,'data'=>array());
            }else{
                $response = array('success'=>0,'message'=>"Enter email id and password both.",'data'=>array());
            }
        }
        echo json_encode(array("response"=>$response)); // return response

    } // end function


    /**
     * Used to forgot password with mail send to user on its email id 
     *
     * @param string $url
     * @param array $params (email_id)
     * @return string
     */
    public function forgotPasswordPostApiAction(){

        //Receive the RAW post data via the php://input IO stream.        
        $postData = file_get_contents("php://input");
        if(!empty($postData)){
            $postData = json_decode($postData);
        }

        $email = trim($postData->email_id);        
        $success = 0;

        if (!empty($email)){ 

            // get information of customer by email id
            $customer = Mage::getModel('customer/customer');
            $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
            $customer->loadByEmail( $email );
            
            //Check customer exists or not            
            if( !$customer->getId() ){
                $message = 'Email does not exists. Please enter registered email address';
            }else{

                $customerId = $customer->getId(); 
                $date = date('Y-m-d H:i:s');

                //4 digit code
                $code   = str_pad(rand(0, pow(10, 4)-1), 4, '0', STR_PAD_LEFT);

                 
                $resource = Mage::getSingleton('core/resource');

                $readConnection  = $resource->getConnection('core_read');
                $writeConnection = $resource->getConnection('core_write');
     
                // table prefix of website
                $tablePrefix = (string) Mage::getConfig()->getTablePrefix();

                // check if verification code already send for customer or not
                $query = "SELECT * FROM ".$tablePrefix."customer_verification_code where customer_id='$customerId' ";

                $results = $readConnection->fetchAll($query);              

                if(empty($results)){ 

                    // will insert entry in database for verification code with respect to customer id
                    $writeConnection->query("insert into ".$tablePrefix."customer_verification_code (verification_code,customer_id,created_at,is_used) values('$code','$customerId','$date','0')");

                }else{

                    // will update entry in database for verification code with respect to customer id
                    $writeConnection->query("update ".$tablePrefix."customer_verification_code set verification_code ='$code',updated_at='$date',is_used='0' where customer_id='$customerId' ");

                }

                // send mail to user for verification code
                $body = '<h5 style="font-family:Verdana,Arial;font-weight:normal;text-align:center;font-size:22px;line-height:32px;">Please verify your email address.</h5>
                 <p style="text-align:center;margin-bottom:28px;">Please copy the varification code below to reset password reset Wizard.</p><p style="text-align:center;font-weight:bold;">Verification Code : ';

                 $body .= "{$code}</p>&nbsp;<h5 style='font-family:Verdana,Arial;font-weight:normal;text-align:center;font-size:22px;line-height:32px;margin-bottom:75px;margin-top:30px;'>Thank you, 12PM by Mon Ami!</h5>";

                $mail = new Zend_Mail();        
                $mail->setBodyHtml($body);        
                $mail->setFrom('info@12pm.cc', '12pmbymonami.com CustomerSupport');        
                $mail->addTo($email);        
                $mail->setSubject('Verification code');      

                try {
                    $mail->send();
                    $success = 1;
                    $message =  'Verification code sent to your registered email for reset password';
                }catch(Exception $ex){
                    $success = 0;
                    $message = 'Email sending failed. Please try again';
                } 
            }
            $response = array('success'=>$success,'message'=>$message,'data'=>array());
        }else{
            $response = array('success'=>0,'message'=>"Enter enter email id.",'data'=>array());
        }        
        echo json_encode(array("response"=>$response)); // return response   
        die(); 

    } // end function


    /**
     * Display verifyCodeApiAction
     * Used to verify code for particular customer by using these params
     * @param - verification_code (verification code to match in database)
     * @param - email_id (email id of customer )
     */
    public function verifyCodeApiAction()
    {

        //Receive the RAW post data via the php://input IO stream.        
        $postData = file_get_contents("php://input");
        if(!empty($postData)){
            $postData = json_decode($postData);
        }
        $email_id = trim($postData->email_id); 
        $verification_code = trim($postData->verification_code);  

        if(!empty($verification_code) && !empty($email_id)){

            // get information of customer by email id 
            $customer = Mage::getModel('customer/customer');
            $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
            $customer->loadByEmail($email_id);

            if( !$customer->getId() ){
                $success = 0;
                $message = 'Email does not exists. Please enter registered email address';
            }else{

                // get customer id 
                $customerID = $customer->getId();

                // connection for sql queries
                $resource       = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');
                $writeConnection = $resource->getConnection('core_write');

                // table prefix of website
                $tablePrefix = (string) Mage::getConfig()->getTablePrefix();

                // check verification code of custom id
                $customerCode       = $readConnection->fetchAll("SELECT * FROM ".$tablePrefix."customer_verification_code WHERE verification_code ='$verification_code' and customer_id ='$customerID' ");

                
                if( !empty($customerCode)){

                    // check expiry period of verification code  for 30 minutes
                    
                    if($customerCode[0]['updated_at']=='' || $customerCode[0]['updated_at']=='0000-00-00 00:00:00'){
                        $verifyCodeTime = strtotime($customerCode[0]['created_at']);
                    }else{
                        $verifyCodeTime = strtotime($customerCode[0]['updated_at']);
                    }
                    $currentTime = strtotime(date('Y-m-d H:i:s'));
                    $minutesFrom = round(abs($verifyCodeTime - $currentTime) / 60,2);

                    if($minutesFrom>30){
                        $success = 0;
                        $message = 'Verification code has been expired.';
                    }else{
                        if($customerCode[0]['is_used']==1){
                            $success = 0;
                            $message = 'Verification code already used.';

                        }else{

                            // will update is_used as 1  in database for verification code with respect to customer id
                            $writeConnection->query("update ".$tablePrefix."customer_verification_code set is_used='1' where customer_id='$customerID' and verification_code ='$verification_code' ");

                            $success = 1;
                            $message = 'Verification code verified successfully.';
                        }
                    }

                }else{
                    $success = 0;
                    $message = 'Verification code not matched. Please try again';
                }
            }
            $response = array('success'=>$success,'message'=>$message,'data'=>array());
        }else{
            $response = array('success'=>0,'message'=>'Please enter verification code and email id both','data'=>array());
        }
        echo json_encode(array("response"=>$response)); // return response   
        exit();

    } // end function


    /**
     * Used to reset password after login
     * Method resetPasswordPostApi
     * @param - password (password to set for user)
     * @param - email_id (email id of customer)
     * @return JSON
     */
    public function resetPasswordPostApiAction(){

        //Receive the RAW post data via the php://input IO stream.        
        $postData = file_get_contents("php://input");
        if(!empty($postData)){
            $postData = json_decode($postData);
        }
        $email_id = trim($postData->email_id); 
        $password = trim($postData->password);  

        if(!empty($email_id) && !empty($password)){

            // get information of customer by email id 
            $customer = Mage::getModel('customer/customer');
            $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
            $customer->loadByEmail($email_id);

           
            if( !$customer->getId() ){
                $success = 0;
                $message = 'Email does not exists. Please enter registered email address';
            }else{

                $customerId = $customer->getId();
                

                $hash = $customer->getData('password_hash');
                $hashPassword = explode(':', $hash);
                $firstPart = $hashPassword[0]; 
                $salt = $hashPassword[1]; 
                $passwordCheck=md5($salt.$password);

                /************************************* disable user to save same password *******************************/
                if($passwordCheck==$firstPart){
                    $success = 0;
                    $message = 'Cannot reset same password.';
                }
                else{
                    $customer->setPassword($password);
                    $customer->setPasswordConfirmation($password);
                    
                    try {
                        // Empty current reset password token i.e. invalidate it
                        $customer->setRpToken(null);
                        $customer->setRpTokenCreatedAt(null);
                        $customer->cleanPasswordsValidationData();
                        $customer->save();
                        
                        $success = 1;
                        $message = 'Your Password successfully reset. Please try to login again.';
                    } catch (Exception $exception) {
                        $success = 0;
                        $message = 'Cannot save a new password.Please try again.';
                    }
                }                
                
            }
            $response = array('success'=>$success,'message'=>$message,'data'=>array());
        }else{
            $response = array('success'=>0,'message'=>'Please enter password and email id both.','data'=>array());
        }

        echo json_encode(array("response"=>$response)); // return response   
    
    } // end function



}
?>