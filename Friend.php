<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Friend extends MX_Controller {
	

	function __construct()
	{
		parent::__construct();
		$this->load->model('user/user_model');
		$this->load->model('common_model');	
		$this->load->module('main');
		
		$this->load->helper(array('form', 'url'));
		$this->load->library('form_validation');
		$this->load->library('tank_auth');
		$this->lang->load('tank_auth');
		
		if( !$this->tank_auth->get_user_id() )
		{
			redirect('auth/login');
		}

	}
	
	public function add_friend()
	{
		$logged_user_id = $this->tank_auth->get_user_id();
		
		if( $this->input->post() )
		{

			$this->form_validation->set_rules('uid_to', 'Uid To', 'trim|required|xss_clean');
			// $this->form_validation->set_rules('to_user_image', 'User image', 'trim|required|xss_clean');
			// $this->form_validation->set_rules('to_user_name', 'User Name', 'trim|required|xss_clean');
			
			if ($this->form_validation->run())
			{
				$uid_to = $this->input->post('uid_to');
				// $to_user_image = $this->input->post('to_user_image');
				// $to_user_name = $this->input->post('to_user_name');
				$requested_as = $this->input->post('requested_as');

				$add_data = array(
					'uid_to' => $uid_to,
					'uid_from' => $logged_user_id,
					'requested_as' => $requested_as,
					'notification' => 0,
					);
				

				if( $this->common_model->add('friend_request', $add_data) )
				{
					//$data['success_data'] = '<li><span class="item"><span class="item-left"><img src="'.$to_user_image.'" alt="" width="47"><span class="item-info"><span class="fulnames">'.$to_user_name.'</span></span></span><span class="item-right"><button class="conrnbtn btn btn-xs btn-danger" onClick="confirmFriend('.$uid_to.', '.$logged_user_id.')">Confirm</button><button class="ignobtn btn btn-xs btn-danger pull-right" onClick="ignoreFriend('.$uid_to.', '.$logged_user_id.')">Ignore</button></span></span></li>';
					$data['success_data'] = 'Request Sent.';
					$data['uid_to'] = $uid_to;
					$data['success'] = 1;
				}
				else
				{
					$data['error'] = 1;
				}
				

				
			}
			else
			{
				if ( strlen( validation_errors() ) > 0 )
				{
					$data['validation_errors'] = validation_errors('<li>','</li>');
				}	
				
			}

			
			echo json_encode($data);
			die();
		}
	}


	public function confirm_friend()
	{
		$logged_user_id = $this->tank_auth->get_user_id();
		
		if( $this->input->post() )
		{

			$this->form_validation->set_rules('uid_to', 'Uid To', 'trim|required|xss_clean');
			$this->form_validation->set_rules('uid_from', 'Uid from', 'trim|required|xss_clean');
			
			
			if ($this->form_validation->run())
			{
				$uid_to = $this->input->post('uid_to');
				$uid_from = $this->input->post('uid_from');
				

				$add_data = array(
					'uid1' => $uid_to,
					'uid2' => $uid_from,
					);
				
				

				if( !empty( $user_details = $this->common_model->findWhere( 'friend', $add_data, false )  ) )
				{
					$data['already'] = 'Friends already';
				}
				else
				{
					if( $this->common_model->add('friend', $add_data) )
					{
						$data['success'] = 1;
						$data['uid_to'] = $uid_to;
						$data['uid_from'] = $uid_from;

						$where_data = array(
							'uid_to' => $uid_to,
							'uid_from' => $uid_from,
							);

						$this->common_model->updateWhere('friend_request', $where_data, array('status'=> 1));
					}
					else
					{
						$data['error'] = 1;
					}	
				}
				
			}
			else
			{
				if ( strlen( validation_errors() ) > 0 )
				{
					$data['validation_errors'] = validation_errors('<li>','</li>');
				}	
				
			}

			
			echo json_encode($data);
			die();
		}
	}


	public function ignore_friend()
	{
		$logged_user_id = $this->tank_auth->get_user_id();
		
		if( $this->input->post() )
		{

			$this->form_validation->set_rules('uid_to', 'Uid To', 'trim|required|xss_clean');
			$this->form_validation->set_rules('uid_from', 'Uid from', 'trim|required|xss_clean');
			
			
			if ($this->form_validation->run())
			{
				$uid_to = $this->input->post('uid_to');
				$uid_from = $this->input->post('uid_from');
				

				$add_data = array(
					'uid1' => $uid_to,
					'uid2' => $uid_from,
					);
				

				if( $this->common_model->delete('friend', $add_data) )
				{
					$data['success'] = 1;
					$data['uid_to'] = $uid_to;
					$data['uid_from'] = $uid_from;

					$where_data = array(
						'uid_to' => $uid_to,
						'uid_from' => $uid_from,
						);

					$this->common_model->updateWhere('friend_request', $where_data, array('status'=> 2));
				}
				else
				{
					$data['error'] = 1;
				}
			}
			else
			{
				if ( strlen( validation_errors() ) > 0 )
				{
					$data['validation_errors'] = validation_errors('<li>','</li>');
				}	
				
			}

			
			echo json_encode($data);
			die();
		}
	}

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
