<?php
use Restserver\Libraries\REST_Controller;
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Diary extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        //model merchant
        $this->load->model('Diary_model', 'DiaryModel');
        // $this->load->library('session');
        $this->load->helper('slugify');
        $this->load->helper('string');
    }

    public function addmob_post()
    {
        if(!empty($_FILES['image'])){
            $config = array();
            $config['upload_path'] = './image/';
            $config['allowed_types'] = 'jpg|jpeg|png|gif';
            $config_image['max_size']   = '1024';
            $config['overwrite']= true;

            $this->load->library('upload', $config, 'image'); 
            $this->image->initialize($config);
            if(!$this->image->do_upload('image',true)){

                $message = array(
                    'status' => false,
                    'message' => "Upload image failed",
                    'error' => $this->image->display_errors()
                );
                $this->response($message, REST_Controller::HTTP_OK);

            }else{
                $data = array('upload_data' =>$this->image->data());
                $record_image = $data['upload_data']['file_name'];
                $this->add_post($record_image);

                $message = array(
                    'status' => true,
                    'message' => "Upload image successful",
                    'img' => $record_image
                );
                $this->response($message, REST_CONTROLLER::HTTP_OK);
            }
        }else{
            $record_image = NULL;
            $this->add_post($record_image);
        }
    }


    public function add_post($record_image){

        if(!empty($this->input->post('title')) OR !empty($this->input->post('diary'))){
            header("Access-Control-Allow-Origin: *");
            # XSS Filtering (https://www.codeigniter.com/user_guide/libraries/security.html)
            $data = $this->security->xss_clean($_POST);
            # Form Validation
            // $this->form_validation->set_rules('title', 'Title', 'trim|max_length[100]');
            $this->form_validation->set_rules('id_user', 'ID User', 'trim|required|numeric');
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
                $data = [
                    'title' => $this->input->post('title', TRUE),
                    'diary' => $this->input->post('diary', TRUE),
                    'date' => date('Y-m-d'),
                    'image' => $record_image,
                    'id_user' => $this->input->post('id_user',TRUE)
                ];
                //insert data merchant to database
                $record = $this->DiaryModel->insert_diary($data);
                if($record > 0 AND !empty($record))
                {
                    $message = array(
                        'status' => true,
                        'message' => "Add diary success"
                    );
                    $this->response($message, REST_Controller::HTTP_OK);
                
                } else
                {
                    $message = array(
                        'status' => false,
                        'message' => "Add diary failed"
                    );
                    $this->response($message, REST_Controller::HTTP_OK);
                }                
            }
        }else{
            $message = array(
                'status' => true,
                'message' => "Diary is empty",
            );
            $this->response($message, REST_CONTROLLER::HTTP_OK);
        }
    }

    public function edit_post($record_image){

        if(!empty($this->input->post('title')) OR !empty($this->input->post('diary'))){
            header("Access-Control-Allow-Origin: *");
            # XSS Filtering (https://www.codeigniter.com/user_guide/libraries/security.html)
            $data = $this->security->xss_clean($_POST);
            # Form Validation
            // $this->form_validation->set_rules('title', 'Title', 'trim|max_length[100]');
            $this->form_validation->set_rules('id_diary', 'ID User', 'trim|required|numeric');
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
                $id_diary = $this->input->post('id_diary');
                $data = [
                    'title' => $this->input->post('title', TRUE),
                    'diary' => $this->input->post('diary', TRUE),
                    'date' => date('Y-m-d'),
                    'image' => "http://192.168.43.79/myDiary/image/".$record_image
                ];
                //insert data merchant to database
                $record = $this->DiaryModel->edit_diary($data, $id_diary);
                if($record > 0 AND !empty($record))
                {
                    //200 code send means success
                    $message = array(
                        'status' => true,
                        'message' => "Edit diary successful",
                        'id_diary' => $record
                    );
                    $this->response($message, REST_CONTROLLER::HTTP_OK);
                } else
                {

                    $message = array(
                        'status' => false,
                        'message' => "Edit diary failed"
                    );
                    $this->response($message, REST_Controller::HTTP_OK);
                }                
            }
        }else{
            $message = array(
                'status' => true,
                'message' => "Diary is empty",
            );
            $this->response($message, REST_CONTROLLER::HTTP_OK);
        }
    }

    public function edit1_post(){

        if(!empty($this->input->post('title')) OR !empty($this->input->post('diary'))){
            header("Access-Control-Allow-Origin: *");
            # XSS Filtering (https://www.codeigniter.com/user_guide/libraries/security.html)
            $data = $this->security->xss_clean($_POST);
            # Form Validation
            // $this->form_validation->set_rules('title', 'Title', 'trim|max_length[100]');
            $this->form_validation->set_rules('id_diary', 'ID User', 'trim|required|numeric');
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
                $id_diary = $this->input->post('id_diary');
                $data = [
                    'title' => $this->input->post('title', TRUE),
                    'diary' => $this->input->post('diary', TRUE),
                    'date' => date('Y-m-d'),
                    'image' => $this->input->post('image',TRUE)
                ];
                //insert data merchant to database
                $record = $this->DiaryModel->edit_diary($data, $id_diary);
                if($record > 0 AND !empty($record))
                {
                    //200 code send means success
                    $message = array(
                        'status' => true,
                        'message' => "Edit diary successful",
                        'id_diary' => $record
                    );
                    $this->response($message, REST_CONTROLLER::HTTP_OK);
                } else
                {

                    $message = array(
                        'status' => false,
                        'message' => "Edit diary failed"
                    );
                    $this->response($message, REST_Controller::HTTP_OK);
                }                
            }
        }else{
            $message = array(
                'status' => true,
                'message' => "Diary is empty",
            );
            $this->response($message, REST_CONTROLLER::HTTP_OK);
        }
    }

    public function editmob_post()
    {
        if(!empty($_FILES['image'])){
            $config = array();
            $config['upload_path'] = './image/';
            $config['allowed_types'] = 'jpg|jpeg|png|gif';
            $config_image['max_size']   = '1024';
            $config['overwrite']= true;

            $this->load->library('upload', $config, 'image'); 
            $this->image->initialize($config);
            if(!$this->image->do_upload('image',true)){

                $message = array(
                    'status' => false,
                    'message' => "Upload image failed",
                    'error' => $this->image->display_errors()
                );
                $this->response($message, REST_Controller::HTTP_OK);

            }else{
                $data = array('upload_data' =>$this->image->data());
                $record_image = $data['upload_data']['file_name'];
                $this->edit_post($record_image);

                $message = array(
                    'status' => true,
                    'message' => "Upload image successful",
                    'img' => $record_image
                );
                $this->response($message, REST_CONTROLLER::HTTP_OK);
            }
        }else{
            $record_image = NULL;
            $this->edit_post($record_image);
        }
    }

    public function viewbyuser_post(){
        $id_user = $this->input->post('id_user');
        $record = $this->DiaryModel->viewbyuser($id_user);
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

    public function viewall_post(){
        // $id_user = $this->input->post('id_user');
        $record = $this->DiaryModel->viewall();
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

    public function viewbyid_post(){
        $id_diary = $this->input->post('id_diary');
        $record = $this->DiaryModel->viewbydiary($id_diary);
        if($record > 0 AND !empty($record))
        {
            $this->response($record, REST_Controller::HTTP_OK);
        } else
        {

            $message = array(
                'status' => false,
                'message' => "load diary list failed"
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }                 
    }

    public function delete_post()
    {
        $id_diary = $this->input->post('id_diary');
        $record = $this->DiaryModel->delete($id_diary);
        if($record > 0 AND !empty($record)){
            $message = array(
                'status' => true,
                'message' => "Success deleting data"
            );
            $this->response($message, REST_Controller::HTTP_OK);;
        }else{
            $message = array(
                'status' => false,
                'message' => "Failed deleting data"
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
}
}
