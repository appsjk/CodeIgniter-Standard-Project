<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Api_v1 extends CI_Controller {

    function __construct() {
        parent::__construct();

        header('Content-Type: application/json');

        $this->load->model('mdl_api_v1');

        $this->result = array();
        $this->msg = '';
    }

    public function show_error() {
        $this->msg = "404 Error : Method not found.";
        $this->_sendResponse(0);
    }

    public function add_user() {

        if (!$_POST) {
            $this->msg = "Method not accessible.";
            $this->_sendResponse(0);
        }

        $common_rules = "required|xss_clean|trim";

        $validation_rules = array(
            array(
                'field' => 'email',
                'rules' => $common_rules . '|valid_email|is_unique[tbl_user.email]'
            ),
            array(
                'field' => 'first_name',
                'rules' => $common_rules
            ),
            array(
                'field' => 'last_name',
                'rules' => $common_rules
            ),
            array(
                'field' => 'gender',
                'rules' => $common_rules
            ),
            array(
                'field' => 'dob',
                'rules' => $common_rules
            ),
            array(
                'field' => 'password',
                'rules' => $common_rules
            )
        );

        $errors_array = $this->validation_errors($validation_rules);

        if ($errors_array) {

            $this->msg = "Required parameters are missing or it should not null";
            $this->result['required_fields'] = $errors_array;
            $this->_sendResponse(0);
        } else {

            // Profile image upload

            $profile_pic = "";
            if (!empty($_FILES)) {

                $image_name = $_FILES["profile_picture"]['name'];

                $ext = pathinfo($image_name, PATHINFO_EXTENSION);

                $new_name = time() . '_' . $this->get_random_string();

                $config['file_name'] = $new_name . $ext;
                $config['upload_path'] = "uploads/profile/";
                $config['allowed_types'] = "jpg|png|jpeg";

                $this->load->library('upload', $config);

                if ($this->upload->do_upload('profile_picture')) {

                    $finfo = $this->upload->data();
                    $profile_pic = base_url() . 'uploads/profile/' . $finfo['file_name'];
                } else {

                    $error = $this->upload->display_errors();

                    $this->msg = $error;
                    $this->_sendResponse(0);
                }
            }

            $dob = $this->input->post('dob', TRUE);

            $params = array(
                'email' => $this->input->post('email', TRUE),
                'user_token' => $this->get_random_string(),
                'first_name' => $this->input->post('first_name', TRUE),
                'last_name' => $this->input->post('last_name', TRUE),
                'display_name' => $this->input->post('first_name', TRUE) . ' ' . $this->input->post('last_name', TRUE),
                'gender' => $this->input->post('gender', TRUE),
                'dob' => $this->date_format($dob),
                'password' => md5($this->input->post('password', TRUE)),
                'created_date' => date('Y-m-d H:i:s'),
                'profile_picture' => $profile_pic
            );

            $add_user = $this->mdl_api_v1->add_user($params);

            if ($add_user) {

                $user_id = $this->db->insert_id();

                $user_info = $this->mdl_api_v1->select_user($user_id);

                $this->msg = "User added successfully.";
                $this->result['user_info'] = $user_info;
                $this->_sendResponse(1);
            } else {

                $this->msg = "Something went wrong, please try again later.";
                $this->_sendResponse(0);
            }
        }
    }

    public function update_user() {

        if (!$_POST) {
            $this->msg = "Method not accessible.";
            $this->_sendResponse(0);
        }

        $common_rules = "required|xss_clean|trim";

        $validation_rules = array(
            array(
                'field' => 'user_id',
                'rules' => $common_rules
            ),
            array(
                'field' => 'user_token',
                'rules' => $common_rules
            ),
            array(
                'field' => 'email',
                'rules' => $common_rules . '|valid_email'
            ),
            array(
                'field' => 'first_name',
                'rules' => $common_rules
            ),
            array(
                'field' => 'last_name',
                'rules' => $common_rules
            ),
            array(
                'field' => 'gender',
                'rules' => $common_rules
            ),
            array(
                'field' => 'dob',
                'rules' => $common_rules
            )
        );

        $errors_array = $this->validation_errors($validation_rules);

        if ($errors_array) {

            $this->msg = "Required parameters are missing or it should not null";
            $this->result['required_fields'] = $errors_array;
            $this->_sendResponse(0);
        } else {

            $user_id = $this->input->post('user_id', TRUE);
            $user_token = $this->input->post('user_token', TRUE);

            // authorised user
            $this->is_authorised($user_id, $user_token);

            $email = $this->input->post('email', TRUE);

            $check_email = $this->mdl_api_v1->check_email_update($user_id, $email);

            if ($check_email) {

                $this->msg = "email already exist";
                $this->_sendResponse(0);
            } else {

                $dob = $this->input->post('dob', TRUE);

                $params = array(
                    'email' => $this->input->post('email', TRUE),
                    'first_name' => $this->input->post('first_name', TRUE),
                    'last_name' => $this->input->post('last_name', TRUE),
                    'display_name' => $this->input->post('first_name', TRUE) . ' ' . $this->input->post('last_name', TRUE),
                    'gender' => $this->input->post('gender', TRUE),
                    'dob' => $this->date_format($dob)
                );

                // Profile image upload

                if (!empty($_FILES)) {

                    $image_name = $_FILES["profile_picture"]['name'];

                    $ext = pathinfo($image_name, PATHINFO_EXTENSION);

                    $new_name = time() . '_' . $this->get_random_string();

                    $config['file_name'] = $new_name . $ext;

                    $config['upload_path'] = "uploads/profile/";
                    $config['allowed_types'] = "jpg|png|jpeg";

                    $this->load->library('upload', $config);

                    if ($this->upload->do_upload('profile_picture')) {

                        $finfo = $this->upload->data();

                        $params['profile_picture'] = base_url() . 'uploads/profile/' . $finfo['file_name'];

                        // delete old profile pic
                        $user_info = $this->mdl_api_v1->select_user($user_id);

                        $user_profile_pic = $user_info[0]['profile_picture'];
                        $parts = explode('uploads/', $user_profile_pic);
                        @unlink('uploads/' . $parts[1]);
                    } else {

                        $error = $this->upload->display_errors();

                        $this->msg = $error;
                        $this->_sendResponse(0);
                    }
                }

                $update_user = $this->mdl_api_v1->update_user($user_id, $params);

                if ($update_user) {

                    $user_info = $this->mdl_api_v1->select_user($user_id);

                    $this->msg = "User updated successfully.";
                    $this->result['user_info'] = $user_info;
                    $this->_sendResponse(1);
                } else {

                    $this->msg = "Something went wrong, please try again later.";
                    $this->_sendResponse(0);
                }
            }
        }
    }

    public function delete_user() {
        if (!$_POST) {
            $this->msg = "Method not accessible.";
            $this->_sendResponse(0);
        }

        $common_rules = "required|xss_clean|trim";

        $validation_rules = array(
            array(
                'field' => 'user_id',
                'rules' => $common_rules
            ),
            array(
                'field' => 'user_token',
                'rules' => $common_rules
            )
        );

        $errors_array = $this->validation_errors($validation_rules);

        if ($errors_array) {

            $this->msg = "Required parameters are missing or it should not null";
            $this->result['required_fields'] = $errors_array;
            $this->_sendResponse(0);
        } else {

            $user_id = $this->input->post('user_id', TRUE);
            $user_token = $this->input->post('user_token', TRUE);

            // authorised user
            $this->is_authorised($user_id, $user_token);

            $user_info = $this->mdl_api_v1->select_user($user_id);

            $delete_user = $this->mdl_api_v1->delete_user($user_id);

            if ($delete_user) {

                // Delete Profile Picture
                $user_profile_pic = $user_info[0]['profile_picture'];
                $parts = explode('uploads/', $user_profile_pic);
                @unlink('uploads/' . $parts[1]);

                $this->msg = "User Deleted successfully.";
                $status_code = 1;
            } else {

                $this->msg = "Something went wrong, please try again later.";
                $status_code = 0;
            }

            $this->_sendResponse($status_code);
        }
    }

    public function user_list() {

        $page_no = 1;
        if (isset($_POST['page_no']) && is_numeric($_POST['page_no']) && $_POST['page_no'] != 0) {
            $page_no = $_POST['page_no'];
        }

        $limit = 2;
        if (isset($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] != 0) {
            $limit = $_POST['limit'];
        }

        $offset = ($page_no * $limit) - $limit;

        $user_list = $this->mdl_api_v1->user_list($offset, $limit);

        $this->msg = "Fetch user list successfully.";
        $this->result['user_list'] = $user_list;
        $this->_sendResponse(1);
    }

    public function index() {
        $this->msg = "Welcome to Codeigniter REST API Demo!";
        $this->_sendResponse(1);
    }

    ###### Private Functions ######

    private function _sendResponse($status_code = 200) {

        if ($this->msg == '') {
            $this->msg = 'No Message';
        }

        $this->result['msg'] = $this->msg;
        $this->result['status_code'] = $status_code;
        echo json_encode($this->result);
        die();
    }

    private function validation_errors($validation_rules) {

        $this->form_validation->set_rules($validation_rules); //through this statement rules are set
        $this->form_validation->set_error_delimiters('', '');

        $errors_array = array();

        if ($this->form_validation->run() == false) {

            foreach ($validation_rules as $row) {

                $field = $row['field'];
                $error = form_error($field);

                if ($error) {
                    $errors_array[$field] = $error;
                }
            }

            return $errors_array;
        } else
            return false;
    }

    private function date_format($date, $formate = 'Y-m-d') {

        $date = strtotime($date);
        return date("$formate", $date);
    }

    private function get_random_string($length = 10) {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $token = "";
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $token.= $alphabet[$n];
        }
        return $token;
    }

    private function is_authorised($user_id, $user_token) {

        $user_authorised = $this->mdl_api_v1->user_authorised($user_id, $user_token);

        if ($user_authorised == FALSE) {

            $this->msg = "You are not authorised.";
            $this->_sendResponse(2);
        }

        return $user_authorised;
    }

}

?>