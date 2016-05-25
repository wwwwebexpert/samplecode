<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_model extends CI_Model {

	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
	
	/*
	 * Select data for user to show his employment details
	 */
	function user_employment_info($logged_user_id)
	{
		$select = array('id', 'company_name', 'position', 'location', 'currently_working', 'time_period_from', 'time_period_to', 'description');
		
	    $this->db->select($select)->from('user_employment_info')->where('user_id', $logged_user_id);
	    $this->db->order_by('id', 'desc');
		$query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			return $query->result_array();	
		}
		else
		{
			return false;
		}

	}
 /*
     * Select All Information of Digital Wallet Family By User ID
     */
       function user_digitalwalletfamily_info($user_id)
       {
		$select = array('intID', 'vchFamilyName', 'vchFamilyAddress', 'vchFamilyPhone', 'dt_Date');
		
	    $this->db->select($select)->from('user_digitalwallet_family')->where('user_id', $user_id);
	    $this->db->order_by('intID', 'desc');
		$query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			return $query->result_array();	
		}
		else
		{
			return false;
		}
           }
	/*Chat Section
	*/

	function insertchatsession($from,$to,$message)
	{
		// $message=$this->linkify($message);
		  $sql="insert into chat set froms='".$from."',tos='".$to."',message='".$from.":".$message."',recd=0";
	 	  $result=$this->db->query($sql);
	  return $result;
	}  

	/*
     * Select All Information of Digital Wallet Family By User ID
     */
       function user_digitalwalletfriend_info($user_id)
       {
		$select = array('intID', 'vchFriendName', 'vchFriendAddress', 'vchFriendPhone','vchFriendEmail', 'dt_Date');
		
	    $this->db->select($select)->from('user_digitalwallet_friend')->where('user_id', $user_id);
	    $this->db->order_by('intID', 'desc');
		$query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			return $query->result_array();	
		}
		else
		{
			return false;
		}
           }
		/*
         * Select All Information of Digital Wallet Family By User ID
         */
       function user_digitalwalletemergency_info($user_id)
       {
		$select = array('intID', 'vchEmergencyContactName', 'vchEmergencyContactAddress', 'vchEmergencyContactPhone','vchEmergencyContactEmail', 'dt_Date');
		
	    $this->db->select($select)->from('user_digitalwallet_emergency')->where('user_id', $user_id);
	    $this->db->order_by('intID', 'desc');
		$query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			return $query->result_array();	
		}
		else
		{
			return false;
		}
    }
		/*
         * Select All Information of Digital Wallet Family By User ID
         */
       function user_digitalwalletschool_info($user_id)
       {
		$select = array('intID', 'vchSchoolName', 'vchSchoolAddress', 'vchSchoolEmail','vchGraduationYear','vchDegree', 'dt_Date');
		
	    $this->db->select($select)->from('user_digitalwallet_school')->where('user_id', $user_id);
	    $this->db->order_by('intID', 'desc');
		$query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			return $query->result_array();	
		}
		else
		{
			return false;
		}
       }

	/*
	 * Select All Information of Digital Wallet Family By User ID
	 */
	   function user_digitalwalletdoctor_info($user_id)
	   {
		$select = array('intID', 'vchDoctorName', 'vchHospitalName', 'vchDoctorPhone','vchHospitalPhone','vchDoctorAddress', 'vchHospitalAddress','vchDoctorAppointments','vchDoctorAppointmentsDate');
		
	    $this->db->select($select)->from('user_digitalwallet_doctor')->where('user_id', $user_id);
	    $this->db->order_by('intID', 'desc');
		$query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			return $query->result_array();	
		}
		else
		{
			return false;
		}
       }

		/*
         * Select All video Category        
         */
       function get_video_category()
       {
		$select = array('id', 'vchCategoryName');
		
	    $this->db->select($select)->from('video_category')->where('enumStatus', 'A');
	     $this->db->order_by('id', 'desc');
		$query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			return $query->result_array();	
		}
		else
		{
			return false;
		}
       }


	/*
	 * select all users from database
	 */
	function all_users($logged_user_id)
	{
		//	SELECT id, first_name, (Select meta_value FROM standard_user_info where user_id =users.id AND meta_key = 'bio' ) FROM users
		$select = array('id', 'user_file', 'first_name', 'last_name', 'sex','vchDigitalWalletStatus');
		
	    $this->db->select($select)->from('users')->where('id!=', $logged_user_id);
	    $this->db->where('id!=', 1);
	    $this->db->order_by('id', 'desc');
		$query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			return $query->result_array();	
		}
		else
		{
			return false;
		}

	}
	/*
	Quote Feature
	*/
	function getQuote()
	{
		$select = array('vchQuoteText');
		$this->db->select($select)->from('quotes');
		$query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			return $query->row_array();
		}
	}

	/*
	 * select all users from database which are not friend of current user
	 */
	function all_users_not_friend($logged_user_id)
	{
	 


		$query = $this->db->query("SELECT users.id, users.user_file, users.first_name, users.last_name, users.sex,CONCAT( users.first_name, ' ', users.last_name ) as complete_name,users.full_name, friend_request.status, friend_request.requested_as  FROM users LEFT JOIN `friend_request` ON users.id IN ( SELECT friend_request.uid_to FROM friend_request WHERE uid_from =  '{$logged_user_id}' ) where users.id NOT IN( SELECT uid1 from friend where uid2 = {$logged_user_id} ) AND users.id NOT IN({$logged_user_id},1) AND users.id NOT IN( SELECT uid2 from friend where uid1 = {$logged_user_id} ) group by users.id");
		// echo $this->db->last_query();
		// die();
		if ($query->num_rows() > 0)
		{
			$data = array();
			$i=0;
			foreach ($query->result() as $row)
			{
				$data[$i]['id'] = $row->id;
				$data[$i]['user_file'] = $row->user_file;
				$data[$i]['first_name'] = $row->first_name;
				$data[$i]['last_name'] = $row->last_name;
				$data[$i]['full_name'] = $row->full_name;
				$data[$i]['sex'] = $row->sex;
				$data[$i]['status'] = $row->status;
				$i++;
			}
			// echo "<pre>";
			// print_r($data);	
			// die();
			return $data;
		}
		else
		{
			return false;
		}

	}

	/*
	 * Select some extra data from users table for user
	 */
	function user_table_data($logged_user_id)
	{
		$select = array('user_file', 'created', 'sex', 'first_name', 'last_name', 'full_name');
		$this->db->select($select)->from('users')->where('id', $logged_user_id);
	    $query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			return $query->row_array();
		}
	}

	
	/*
	 * Select user cover image
	 */
	function user_cover_image($logged_user_id)
	{
		$this->db->select('*')->from('user_cover_image')->where('user_id', $logged_user_id);
	    $query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		else
		{
			return false;
		}
	}


	/*
     *Select all user friend request
	 */
	function user_add_friend_notification($logged_user_id)
	{
		$this->db->select('users.user_file,users.sex,users.first_name,,users.last_name,friend_request.uid_to,friend_request.uid_from,friend_request.requested_as');
		$this->db->from('users');
		$this->db->join('friend_request', 'friend_request.uid_from=users.id');
		$this->db->where('friend_request.uid_to' , $logged_user_id);
		$this->db->where('friend_request.status' , 0);
		$query = $this->db->get();
		//echo $this->db->last_query();
		

		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	function user_all_friends($logged_user_id)
	{
		$query = $this->db->query("SELECT id, sex,vchDigitalWalletStatus, user_file, CONCAT( first_name, ' ', last_name ) as complete_name,full_name FROM users where id IN( Select uid1 from friend where uid2 = {$logged_user_id} ) UNION ALL SELECT id, sex,vchDigitalWalletStatus,user_file, CONCAT( first_name, ' ', last_name ) as complete_name,full_name FROM users where id IN( Select uid2 from friend where uid1 = {$logged_user_id} ) order by id");
		// echo $this->db->last_query();
		// die();
		if ($query->num_rows() > 0)
		{
			$data = array();
			$i=0;
			foreach ($query->result() as $row)
			{
				$data[$i]['id'] = $row->id;
				$data[$i]['complete_name'] = $row->complete_name;
				$data[$i]['full_name'] = $row->full_name;
				$data[$i]['user_file'] = $row->user_file;
				$data[$i]['sex'] = $row->sex;
				$data[$i]['vchDigitalWalletStatus'] = $row->vchDigitalWalletStatus;
				$i++;
			}
			return $data;
		}
		/*else
		{
			return false;
		}*/
		
	}

	/*
	  get Today Birthday List

	*/
	public function getTodayBirthday()
	{
	   $query = $this->db->query("SELECT * FROM users WHERE DATE_FORMAT(  `birthday`  , '%m-%d' ) = DATE_FORMAT( NOW( ) , '%m-%d' ) ");
			// echo $this->db->last_query();
			// die();
			if ($query->num_rows() > 0)
			{
				$data = array();
				$i=0;
				foreach ($query->result() as $row)
				{
					$data[$i]['id'] = $row->id;
					$data[$i]['full_name'] = $row->full_name;
					$data[$i]['user_file'] = $row->user_file;
					$data[$i]['sex'] = $row->sex;
					$data[$i]['vchDigitalWalletStatus'] = $row->vchDigitalWalletStatus;
					$i++;
				}
				return $data;
			}
			else
			{
				return false;
			}
	}

	/*
	  get Weekend Birthday List

	*/
	public function getWeekBirthday()
	{
	   $query = $this->db->query("SELECT *
		FROM users
		WHERE DATE_FORMAT( birthday, '%m-%d' )
		BETWEEN DATE_FORMAT( DATE_ADD( CURDATE( ) , INTERVAL 1
		DAY ) , '%m-%d' )
		AND DATE_FORMAT( DATE_ADD( CURDATE( ) , INTERVAL 7
		DAY ) , '%m-%d' ) ");
			// echo $this->db->last_query();
			// die();

			if ($query->num_rows() > 0)
			{
				$data = array();
				$i=0;
				foreach ($query->result() as $row)
				{
					$data[$i]['id'] = $row->id;
					$data[$i]['full_name'] = $row->full_name;
					$data[$i]['user_file'] = $row->user_file;
					$data[$i]['sex'] = $row->sex;
					$data[$i]['vchDigitalWalletStatus'] = $row->vchDigitalWalletStatus;
					$i++;
				}
				return $data;
			}
			else
			{
				return '0';
			}
	}
    /*
       Search Friends
    */

 function user_all_friends_search($logged_user_id,$searchStr)
	{

 
		$query = $this->db->query("SELECT id, sex, user_file, CONCAT( first_name, ' ', last_name ) as complete_name FROM users where  id != {$logged_user_id}  and (username like '%".$searchStr."' or first_name like'%".$searchStr."' or last_name like '%".$searchStr."' )   order by id");
		// echo $this->db->last_query();
		// die();
		if ($query->num_rows() > 0)
		{
			$data = array();
			$i=0;
			foreach ($query->result() as $row)
			{
				$data[$i]['id'] = $row->id;
				$data[$i]['complete_name'] = $row->complete_name;
				$data[$i]['user_file'] = $row->user_file;
				$data[$i]['sex'] = $row->sex;
				$i++;
			}
			return $data;
		}
		else
		{
			return false;
		}
		
	}


	/*
	 * Select all user videos from uservideo table for user
	 */
	function all_videos_uploaded($logged_user_id)
	{
		//$select = array('user_file', 'created', 'sex', 'first_name', 'last_name');
		$this->db->select('*')->from('uservideo')->where('user_id', $logged_user_id);
	    $query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}


	/*
	 * get list of all countries
	 */
	public function all_country_list()
	{
		// $select = array('phonecode', 'nicename');
		
	 //    $this->db->select($select)->from('all_country_list')->;
	 //    $this->db->order_by('id');
		$query = $this->db->query("SELECT `phonecode`, `nicename` FROM `all_country_list` WHERE phonecode IS NOT NULL ORDER BY `id` ");
		
		// echo $this->db->last_query();
		// print_r($query->result_array()	);
		// die();
		if ($query->num_rows() > 0)
		{
			return $query->result_array();	
		}
		else
		{
			return false;
		}
	}


	/*Get all colors for post rating*/
	
	public function post_rating_colors()
	{
		
		$query = $this->db->query("SELECT `average`, `color` FROM `post_rating_colors`");
		
		// echo $this->db->last_query();
		// print_r($query->result_array()	);
		// die();
		if ($query->num_rows() > 0)
		{
			$all_colors = array();
			foreach ($query->result_array() as $value) {
				$all_colors[$value['average']] = $value['color'];
			}
			return $all_colors;
		}
	}
	/*Get all colors for post rating*/


	/*
	 * Select friend request notification from friend_request table
	 */
	function friend_request_notification($logged_user_id)
	{
		$select = array('id');
		$where = array('uid_to' => $logged_user_id, 'notification' => 0);
		$this->db->select($select)->from('friend_request')->where($where);
	    $query = $this->db->get();

		return $query->num_rows();
	}

	/*
	 * kill all friend request notification
	 */
	function friend_request_notification_read($logged_user_id)
	{
		$data = array(
               'notification' => 1,
            );

		$this->db->where('uid_to', $logged_user_id);
		$this->db->update('friend_request', $data);
	// 	echo $this->db->last_query();
	// 	die();
	}

	/* Get Event Category 
        */

      function getEventCategory()
   {
    		$select = array('id','vchEventCategoryName');
		$where = array('enumStatus' =>'A');
		$this->db->select($select)->from('event_category')->where($where);
		$query = $this->db->get();
		return $query->result_array();	
   }


}
	
