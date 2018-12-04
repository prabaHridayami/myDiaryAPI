<?php
use Restserver\Libraries\REST_Controller;
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class User extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        //model merchant
        $this->load->model('User_model', 'UserModel');
        // $this->load->library('session');
        $this->load->helper('slugify');
        $this->load->helper('string');
    }

    public function view_get(){
        $output = $this->UserModel->view();
        if($output!=NULL){
            $this->response($output, REST_Controller::HTTP_OK);
        }else{
            $message = array(
                'status' => false,
                'message' => "Empty Data"
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
       
    }

    public function add_post()
    {
        if(!empty($_FILES['image'])){   
            // $temp_file_path = tempnam(sys_get_temp_dir(), 'androidtempimage'); // might not work on some systems, specify your temp path if system temp dir is not writeable
            // file_put_contents($temp_file_path, base64_decode($_POST['image']));
            // $image_info = getimagesize($temp_file_path); 
            // $_FILES['userfile'] = array(
            //     'name' => uniqid().'.'.preg_replace('!\w+/!', '', $image_info['mime']),
            //     'tmp_name' => $temp_file_path,
            //     'size'  => filesize($temp_file_path),
            //     'error' => UPLOAD_ERR_OK,
            //     'type'  => $image_info['mime'],
            // );

            $imagename = slugify($this->input->post('name', TRUE));
            $config['upload_path'] = './image/';
            $config['allowed_types'] = 'jpg|jpeg|png|gif';
            $config_image['max_size']   = '1024';
            $config['file_name'] = $imagename;
            $config['overwrite']= true;
        
            $this->load->library('upload',$config);
            if(!($this->upload->do_upload('image',true))){

                $message = array(
                    'status' => false,
                    'message' => "Upload image failed",
                    'error' => $this->upload->display_errors()
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);

            }else{
                $data = array('upload_data' =>$this->upload->data());
                $record = $data['upload_data']['file_name'];
                $this->add1_post($record);

                $message = array(
                    'status' => true,
                    'message' => "Upload image successful"
                );
                $this->response($message, REST_CONTROLLER::HTTP_OK);
            }
        }else{
            $record = NULL;
            $this->add1_post($record);
        }
    }

    public function add1_post()
    {

        header("Access-Control-Allow-Origin: *");
        # XSS Filtering (https://www.codeigniter.com/user_guide/libraries/security.html)
        $data = $this->security->xss_clean($_POST);
        # Form Validation
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('username', 'Username', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[100]|is_unique[user.email]', 
        array('is_unique' => 'This %s already exists please enter another email address'));
        $this->form_validation->set_rules('password', 'Password', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('passconfirm', 'Password Confirm', 'trim|required|max_length[100]');
        if ($this->form_validation->run() == FALSE)
        {
            //Form Validation error
            $message = array(
                'status' => false,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            $code = random_string('alnum',8);
            $email = $this->input->post('email', TRUE);
            $password = $this->input->post('password', TRUE);
            $passconfirm =$this->input->post('passconfirm', TRUE);
            $data = [
                'name' => $this->input->post('name', TRUE),
                'username' => $this->input->post('username', TRUE),
                'email' => $email,
                'password' => md5($password),
                // 'image' => $record,
                'code' => $code,
                'createdate' => date('Y-m-d'),
                'status' => 0
            ];
            //insert data merchant to database
            if($password == $passconfirm){
                $record = $this->UserModel->insert_user($data);
                if($record > 0 AND !empty($record))
                {
                    $this->sendMail_post($code, $email); 
                    //200 code send means success
                    $message = array(
                        'status' => true,
                        'message' => "User add successful",
                        'id_user' => $record
                    );
                    $this->response($message, REST_CONTROLLER::HTTP_OK);
                } else
                {

                    $message = array(
                        'status' => false,
                        'message' => "User add failed"
                    );
                    $this->response($message, REST_Controller::HTTP_OK);
                }
            }else{
                $message = array(
                    'status' => false,
                    'message' => "Passwords are not matching"
                );
                $this->response($message, REST_Controller::HTTP_OK);
            }
            
        }
    }
    
    public function sendMail_post($code, $email) 
    {
        $this->load->library('email');
        $ci = get_instance();
        $config['protocol'] = "smtp";
        $config['smtp_host'] = "ssl://smtp.gmail.com";
        $config['smtp_port'] = "465";
        $config['smtp_user'] = "practicmydiary@gmail.com";
        $config['smtp_pass'] = "redvelvet";
        $config['charset'] = "utf-8";
        $config['mailtype'] = "html";
        $config['newline'] = "\r\n";
        $ci->email->initialize($config);

        $ci->email->from('practicmydiary@gmail.com');
        // $list = array('xxx@xxxx.com');
        $ci->email->to($email);
        $ci->email->subject('Registration Verification');
        $ci->email->message('http://192.168.43.79/myDiary/api/user/verification/'.$code);
        if ($this->email->send()){ 
            $message = array(
                'status' => true,
                'message' => "Email has sent"
            );
            $this->response($message, REST_Controller::HTTP_OK);;
        } else {
            $message = array(
                'status' => false,
                'message' => "Data is empty",
                'error' => show_error($this->email->print_debugger())
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function verification_get(){
        $code = $this->uri->segment(4);
        $row = $this->UserModel->verification($code);
        if($row > 0 AND !empty($row))
        {
            //200 code send means success
            $message = array(
                'status' => true,
                'message' => "User is verified"
            );
            $this->response($message, REST_CONTROLLER::HTTP_OK);
        } else
        {
            //error means failed
            $message = array(
                'status' => false,
                'message' => "User is not valid"
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }

    }

    public function edit_post()
    {

        header("Access-Control-Allow-Origin: *");
        # XSS Filtering (https://www.codeigniter.com/user_guide/libraries/security.html)
        $data = $this->security->xss_clean($_POST);
        # Form Validation
        $this->form_validation->set_rules('id_user', 'ID', 'trim|required|numeric');
        $this->form_validation->set_rules('name', 'Name', 'trim|max_length[100]');
        $this->form_validation->set_rules('username', 'Username', 'trim|max_length[100]');

        if ($this->form_validation->run() == FALSE)
        {
            //Form Validation error
            $message = array(
                'status' => false,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            $id_user = $this->input->post('id_user');
            $data = [
                'name' => $this->input->post('name', TRUE),
                'username' => $this->input->post('username', TRUE),
            ];
            //insert data merchant to database
            $record = $this->UserModel->update_user($id_user, $data);
            if(!empty($record))
            {
                //200 code send means success
                $message = array(
                    'status' => true,
                    'message' => "Successful editing ".$record." user",
                );
                $this->response($message, REST_CONTROLLER::HTTP_OK);
            } else
            {

                $message = array(
                    'status' => false,
                    'message' => "User edit failed"
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
            
        }
    }

    public function viewbyuser_get(){
        $id_user = $this->get('id_user');
        $record = $this->UserModel->viewbyuser($id_user);
        if($record > 0 AND !empty($record))
        {
            $this->response($record, REST_CONTROLLER::HTTP_OK);
        } else
        {

            $message = array(
                'status' => false,
                'message' => "load diary list failed"
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }                 
    }

    public function login_post()
    {
        $this->form_validation->set_rules('username', 'username', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('password', 'password', 'trim|required|max_length[100]');
        if ($this->form_validation->run() == FALSE)
        {
            //Form Validation error
            $message = array(
                'status' => false,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {

            $username = $this->input->post('username');
            $password = $this->input->post('password');

            $row = $this->UserModel->login($username, $password);
            if($row){
                //true
                foreach ($row as $row){
                    $sess = array(
                        'logged' => TRUE,
                        'id_user' => $row->id_user
                    );
                }
                $this->session->set_userdata($sess);

                $message = array(
                    'status' => true,
                    'message' => "Login success",
                    'name' => $row->name,
                    'id_user' => $row->id_user,
                    'username'=> $row->username,
                    'email' =>$row->email
                );
                $this->response($message, REST_Controller::HTTP_OK);
            }else{
                // login gagal
                $message = array(
                    'status' => false,
                    'message' => "Failed to login"
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    } 
    
    public function editpublicinfo_post()
    {
        $data = $this->security->xss_clean($_POST);           
        # Form Validation
        $this->form_validation->set_rules('merchantname', 'Merchant Name', 'trim|reqiured|max_length[200]');
        $this->form_validation->set_rules('id_merchant', 'Merchant ID', 'trim|reqiured|max_length[200]');
        $this->form_validation->set_rules('merchantimage', 'Merchant Image', 'trim|reqiured|max_length[14]');
        if ($this->form_validation->run() == FALSE)
        {
            //Form Validation error
            $message = array(
                'status' => false,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            $imagename = $this->input->post('merchantname', TRUE);
            $config['upload_path'] = './ktpimage/';
            $config['allowed_types'] = 'jpg|jpeg|png|gif';
            $config_image['max_size']   = '1024';
            $config['file_name'] = $imagename;
            $config['overwrite']= true;
        
            $this->load->library('upload',$config);
            if(!$this->upload->do_upload('ktpimage')){

                $message = array(
                    'status' => false,
                    'message' => "Upload ktp failed",
                    'error' => $this->upload->display_errors()
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);

            }else{
                $data = array('upload_data' =>$this->upload->data());
                $record_image = $data['upload_data']['file_name'];
                $update_merchant = [
                    'merchantname' => $this->input->post('merchantname', TRUE),
                    'merchantimage' => $record_image,
                    'slug' => slugify($this->input->post('merchantname', TRUE))
                ];
                //insert data merchant to database
                $id_merchant = $this->input->post('id_merchant', TRUE);  
                $record = $this->MerchantModel->update_merchant($update_merchant,$id_merchant);
                if($record > 0 AND !empty($record))
                {
                    //200 code send means success
                    $message = array(
                        'status' => true,
                        'message' => "Merchant update successful"
                    );
                    $this->response($message, REST_CONTROLLER::HTTP_OK);
                } else
                {
                    //error means failed to create merchant 
                    $message = array(
                        'status' => false,
                        'message' => "Merchant update failed"
                    );
                    $this->response($message, REST_Controller::HTTP_NOT_FOUND);
                }
            }
        }
    }

    public function editcontact_post()
    {
        $data = $this->security->xss_clean($_POST);            
        # Form Validation
        $this->form_validation->set_rules('useremail', 'User email', 'trim|required|valid_email|max_length[200]');
        $this->form_validation->set_rules('merchantphone', 'Merchant Phone', 'trim|required|max_length[14]');
        if ($this->form_validation->run() == FALSE)
        {
            //Form Validation error
            $message = array(
                'status' => false,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            $update_merchant = [
                'useremail' => $this->input->post('useremail', TRUE),
                'merchantphone' => $this->input->post('merchantphone', TRUE)
            ];
            //insert data merchant to database
            $id_merchant = $this->input->post('id_merchant', TRUE); 
            $record = $this->MerchantModel->update_merchant($update_merchant,$id_merchant);
            if($record > 0 AND !empty($record))
            {
                //200 code send means success
                $message = array(
                    'status' => true,
                    'message' => "Merchant update successful"
                );
                $this->response($message, REST_CONTROLLER::HTTP_OK);
            } else
            {
                //error means failed to create merchant 
                $message = array(
                    'status' => false,
                    'message' => "Merchant update failed"
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function editaddress_post()
    {
        $data = $this->security->xss_clean($_POST);            
        # Form Validation
        $this->form_validation->set_rules('merchantaddress', 'Merchant Address', 'trim|required|valid_email|max_length[200]');
        if ($this->form_validation->run() == FALSE)
        {
            //Form Validation error
            $message = array(
                'status' => false,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            $update_merchant = [
                'merchantaddress' => $this->input->post('merchantaddress', TRUE)
            ];
            //insert data merchant to database
            $id_merchant = $this->input->post('id_merchant', TRUE); 
            $record = $this->MerchantModel->update_merchant($update_merchant,$id_merchant);
            if($record > 0 AND !empty($record))
            {
                //200 code send means success
                $message = array(
                    'status' => true,
                    'message' => "Merchant update successful"
                );
                $this->response($message, REST_CONTROLLER::HTTP_OK);
            } else
            {
                //error means failed to create merchant 
                $message = array(
                    'status' => false,
                    'message' => "Merchant update failed"
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    // public function edit_post()
    // {
    //     $config['upload_path'] = './ktpimage/';
    //     $config['allowed_types'] = 'jpg|jpeg|png|gif';
    //     $config_image['max_size']   = '1024';
    //     $config['file_name'] = $this->input->post('merchantname', TRUE);
    //     $config['overwrite']= true;

    //     $this->load->library('upload',$config);
    //     if ($this->upload->do_upload('ktpimage')){
    //         $data = array('upload_data' =>$this->upload->data());
    //         $record_image = $data['upload_data']['file_name'];
    //         $this->edit1_post($record_image);

    //         $message = array(
    //             'status' => true,
    //             'message' => "Upload ktp successful"
    //         );
    //         $this->response($message, REST_CONTROLLER::HTTP_OK);
    //     }else{
    //         $message = array(
    //             'status' => false,
    //             'message' => "Upload ktp failed"
    //         );
    //         $this->response($message, REST_Controller::HTTP_NOT_FOUND);
    //     }
    // }

    public function dailytrx_get()
    {

        // if (!($this->session->userdata('role')=='seller')) {
        //     $message = array(
        //         'status' => false,
        //         'message' => "Please login as seller"
        //     );
        //     $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        // } else {
            $id_seller = $this->get('id_user');
            $output = $this->MerchantModel->dailytrx($id_seller);
            if (!empty($output)){
                $this->response($output, REST_Controller::HTTP_OK);
            }else{
                $message = array(
                    'status' => false,
                    'message' => "Data is empty"
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        // }
    }

    public function listbyuser_get()
    {   
        $id_user = $this->get('id_user');
        $output = $this->MerchantModel->list_merchantbyuser($id_user);
        if($output == NULL){
            $message = array(
                'status' => false,
                'message' => "Data is empty"
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }else{
            $this->response($output, REST_Controller::HTTP_OK);
        }
    }

    public function sellertrxcount_post(){

        $this->form_validation->set_rules('id_merchant', 'id_merchant', 'trim|required');
        $this->form_validation->set_rules('startdate', 'startdate', 'trim|required');
        $this->form_validation->set_rules('enddate', 'enddate', 'trim|required');

        if ($this->form_validation->run() == FALSE)
        {
            //Form Validation error
            $message = array(
                'status' => false,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            $startdate =$this->input->post('startdate');
            $enddate = $this->input->post('enddate');
            $status = $this->input->post('status');
            $id_merchant = $this->input->post('id_merchant');

            if($status != NULL){
                $output = $this->MerchantModel->sellertrxcount($startdate,$enddate,$status,$id_merchant);
                if($output!=NULL){
                    $this->response($output, REST_Controller::HTTP_OK);
                }else{
                    $message = array(
                        'status' => false,
                        'message' => "Merchant ID ".$id_merchant." is not identified"
                    );
                    $this->response($message, REST_Controller::HTTP_NOT_FOUND);
                }
            }else{
                $output = $this->MerchantModel->sellertrxcountall($startdate,$enddate,$id_merchant);
                if($output!=NULL){
                    $this->response($output, REST_Controller::HTTP_OK);
                }else{
                    $message = array(
                        'status' => false,
                        'message' => "Merchant ID ".$id_merchant." is not identified"
                    );
                    $this->response($message, REST_Controller::HTTP_NOT_FOUND);
                }
            }
        }
       
    }

    public function sellerreport_post(){

        $this->form_validation->set_rules('id_merchant', 'id_merchant', 'trim|required');
        $this->form_validation->set_rules('startdate', 'startdate', 'trim|required');
        $this->form_validation->set_rules('enddate', 'enddate', 'trim|required');
        $this->form_validation->set_rules('status', 'status', 'trim|required');

        if ($this->form_validation->run() == FALSE)
        {
            //Form Validation error
            $message = array(
                'status' => false,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            $startdate =$this->input->post('startdate');
            $enddate = $this->input->post('enddate');
            $status = $this->input->post('status');
            $id_merchant = $this->input->post('id_merchant');

            if($status == 1){
                $output = $this->MerchantModel->reportcount($startdate,$enddate,$id_merchant);
                if($output!=NULL){
                    $this->response($output, REST_Controller::HTTP_OK);
                }else{
                    $message = array(
                        'status' => false,
                        'message' => "Merchant ID ".$id_merchant." is not identified"
                    );
                    $this->response($message, REST_Controller::HTTP_NOT_FOUND);
                }
            }else if($status == 2){
                $output = $this->MerchantModel->reportsum($startdate,$enddate,$id_merchant);
                if($output!=NULL){
                    $this->response($output, REST_Controller::HTTP_OK);
                }else{
                    $message = array(
                        'status' => false,
                        'message' => "Merchant ID ".$id_merchant." is not identified"
                    );
                    $this->response($message, REST_Controller::HTTP_NOT_FOUND);
                }
            }else{
                $message = array(
                    'status' => false,
                    'message' => "Field is empty"
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
       
    }

    public function sellertrxdata_post(){

        $this->form_validation->set_rules('id_merchant', 'id_merchant', 'trim|required');
        $this->form_validation->set_rules('startdate', 'startdate', 'trim|required');
        $this->form_validation->set_rules('enddate', 'enddate', 'trim|required');

        if ($this->form_validation->run() == FALSE)
        {
            //Form Validation error
            $message = array(
                'status' => false,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            $startdate =$this->input->post('startdate');
            $enddate = $this->input->post('enddate');
            $status = $this->input->post('status');
            $id_merchant = $this->input->post('id_merchant');
            if($status != NULL){
                $output = $this->MerchantModel->sellertrxdata($startdate,$enddate,$status,$id_merchant);
                if($output!=NULL){
                    $this->response($output, REST_Controller::HTTP_OK);
                }else{
                    $message = array(
                        'status' => false,
                        'message' => "Merchant ID ".$id_merchant." is not identified"
                    );
                    $this->response($message, REST_Controller::HTTP_NOT_FOUND);
                }
            }else{
                $output = $this->MerchantModel->sellertrxdataall($startdate,$enddate,$id_merchant);
                if($output!=NULL){
                    $this->response($output, REST_Controller::HTTP_OK);
                }else{
                    $message = array(
                        'status' => false,
                        'message' => "Merchant ID ".$id_merchant." is not identified"
                    );
                    $this->response($message, REST_Controller::HTTP_NOT_FOUND);
                }
            }
        }
    }

    public function action_post(){
        $this->form_validation->set_rules('id_order', 'id_order', 'trim|required');
        $this->form_validation->set_rules('status', 'status', 'trim|required');

        if ($this->form_validation->run() == FALSE)
        {
            //Form Validation error
            $message = array(
                'status' => false,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {

            $status = $this->input->post('status');
            $id_order = $this->input->post('id_order');

            $output = $this->MerchantModel->actionseller($status,$id_order);
            if($output!=NULL){
                $message = array(
                    'status' => true,
                    'message' => "Action success"
                );
                $this->response($message, REST_CONTROLLER::HTTP_OK);
            }else{
                $message = array(
                    'status' => false,
                    'message' => "Order ID ".$id_order." is not identified"
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function topproduct_post(){

        $startdate = $this->input->post('startdate');
        $enddate = $this->input->post('enddate');
        $status = $this->input->post('status');
        $id_merchant = $this->input->post('id_merchant');

        if($status == 'category'){
            $output = $this->MerchantModel->categorytopproduct($startdate,$enddate,$id_merchant);
        }else if ($status == 'brand'){
            $output = $this->MerchantModel->brandtopproduct($startdate,$enddate,$id_merchant);
        }
        
        if($output!=NULL){
            $this->response($output, REST_Controller::HTTP_OK);
        }else{
            $message = array(
                'status' => false,
                'message' => "Data is empty"
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function trxdraft_post()
    {
        $startdate = $this->input->post('startdate');
        $enddate = $this->input->post('enddate');
        $id_merchant = $this->input->post('id_merchant');

        $output = $this->MerchantModel->trxdraft($startdate,$enddate,$id_merchant);
        if($output!=NULL){
            $this->response($output, REST_Controller::HTTP_OK);
        }else{
            $message = array(
                'status' => false,
                'message' => "Data is empty"
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function reportseller_post(){
        $range = $this->input->post('range');
        $id_merchant = $this->input->post('id_merchant');

        $countproduct = $this->MerchantModel->countproduct($range,$id_merchant);
        $orderedproduct = $this->MerchantModel->orderedproduct($range,$id_merchant);
        $income = $this->MerchantModel->income($range,$id_merchant);
        $totalincome = $this->MerchantModel->totalincome($id_merchant);
        $rating = $this->MerchantModel->rating($range,$id_merchant);
        $uniqbuyer = $this->MerchantModel->uniqbuyer($range,$id_merchant);
        
        $data[] = array(
            'countproduct' => $countproduct,
            'orderedproduct'=>$orderedproduct,
            'income'=>$income,
            'totalincome'=>$totalincome,
            'rating'=>$rating,
            'uniqbuyer'=>$uniqbuyer

        );
        // $data[]=(object) $data_unmerge;
        
        if($data!=NULL){
            $this->response($data, REST_Controller::HTTP_OK);
        }else{
            $message = array(
                'status' => false,
                'message' => "Data is empty"
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }
 
    public function sumpayment_post(){

        $range = $this->input->post('range');
        $type = $this->input->post('datatype');
        $id_merchant = $this->input->post('id_merchant');
        
        if($type == 'day'){
            for ($day=$range; $day>0; $day--){
                $dateorder = (date('Y-m-d', strtotime(date('Y-m-d'). ' - '.($day).' days')));
                $totaltrx= $this->MerchantModel->graph_daytrx($dateorder,$id_merchant);

                if(empty($totaltrx)){
                    $total = 0;
                }else{
                    $total = $totaltrx[0]['total'];
                }

                $data []= array(
                    // 'i'=>$day,
                    'date' => $dateorder,
                    'value' => $total
                );
            } 
        }else if($type=='month'){
            for ($month=$range; $month>0; $month--){
                $monthorder = (date('Y-m', strtotime(date('Y-m'). ' - '.($month).' months')));
                $totaltrx= $this->MerchantModel->graph_monthtrx($monthorder,$id_merchant);
                // $amountprod = null;
                if(empty($totaltrx)){
                    $total = 0;
                }else{
                    $total = $totaltrx[0]['total'];
                }

                $data []= array(
                    // 'i'=>$day,
                    'month' => $monthorder,
                    'value' => $total
                );
            }
        }elseif($type=='year'){
            for ($year=12; $year>0; $year--){
                $monthorder = (date(''.$range.'-m', strtotime(date(''.$range.'-m'). ' - '.($year).' month')));
                $totaltrx= $this->MerchantModel->graph_yeartrx($monthorder,$id_merchant);
                // $amountprod = null;
                if(empty($totaltrx)){
                    $total = 0;
                }else{
                    $total = $totaltrx[0]['total'];
                }

                $data []= array(
                    // 'i'=>$day,
                    'month' => $monthorder,
                    'value' => $total
                );
            }
        }
        
        if($data!=NULL){
            $this->response($data, REST_Controller::HTTP_OK);
        }else{
            $message = array(
                'status' => false,
                'message' => "Data is empty"
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    
    }

    public function countprod_post(){
    
        $range = $this->input->post('range');
        $type = $this->input->post('datatype');
        $id_merchant = $this->input->post('id_merchant');
        
        if($type == 'day'){
            for ($day=$range; $day>0; $day--){
                $dateorder = (date('Y-m-d', strtotime(date('Y-m-d'). ' - '.($day).' days')));
                $amountprod= $this->MerchantModel->graph_daycount($dateorder,$id_merchant);

                if(empty($amountprod)){
                    $order = 0;
                }else{
                    $order = $amountprod[0]['count'];
                }

                $data []= array(
                    // 'i'=>$day,
                    'date' => $dateorder,
                    'value' => $order
                );
            } 
        }else if($type=='month'){
            for ($month=$range; $month>0; $month--){
                $monthorder = (date('Y-m', strtotime(date('Y-m'). ' - '.($month).' months')));
                $amountprod= $this->MerchantModel->graph_monthcount($monthorder, $id_merchant);
                // $amountprod = null;
                if(empty($amountprod)){
                    $amount = 0;
                }else{
                    $amount = $amountprod[0]['count'];
                }

                $data []= array(
                    // 'i'=>$day,
                    'month' => $monthorder,
                    'value' => $amount
                );
            }
        }elseif($type=='year'){
            for ($year=12; $year>0; $year--){
                $monthorder = (date(''.$range.'-m', strtotime(date(''.$range.'-m'). ' - '.($year+1).' month')));
                $amountprod= $this->MerchantModel->graph_yearcount($monthorder, $id_merchant);
                // $amountprod = null;
                if(empty($amountprod)){
                    $amount = 0;
                }else{
                    $amount = $amountprod[0]['count'];
                }

                $data []= array(
                    // 'i'=>$day,
                    'month' => $monthorder,
                    'value' => $amount
                );
            }
        }
        
        if($data!=NULL){
            $this->response($data, REST_Controller::HTTP_OK);
        }else{
            $message = array(
                'status' => false,
                'message' => "Data is empty"
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function topmerchant_get(){
        $output = $this->MerchantModel->topmerchant();
        if($output!=NULL){
            $this->response($output, REST_Controller::HTTP_OK);
        }else{
            $message = array(
                'status' => false,
                'message' => "Data is empty"
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }
}
