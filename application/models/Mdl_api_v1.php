<?php

defined('BASEPATH') OR exit('No direct script access allowed');

Class Mdl_api_v1 extends CI_Model {

    function add_user($params) {

        return $this->db->insert('tbl_user', $params);
    }

    function select_user($user_id) {

        $this->db->select('user_id,user_token,email, first_name, last_name, dob, gender, profile_picture, display_name');
        $this->db->from('tbl_user');
        $this->db->where('user_id', $user_id);
        $this->db->limit(1);
        $user_info = $this->db->get();

        return $user_info->result_array();
    }

    function check_email_update($user_id, $email) {

        $this->db->select('user_id');
        $this->db->from('tbl_user');
        $this->db->where('email', $email);
        $this->db->where('user_id != ', $user_id);
        $info = $this->db->get();
        $rows = $info->num_rows();

        if ($rows > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function update_user($user_id, $params) {

        $this->db->where('user_id', $user_id);
        return $this->db->update('tbl_user', $params);
    }

    function user_authorised($user_id, $user_token) {

        $this->db->select('user_id');
        $this->db->from('tbl_user');
        $this->db->where('user_id', $user_id);
        $this->db->where('user_token', $user_token);
        $info = $this->db->get();
        $rows = $info->num_rows();

        if ($rows > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function delete_user($user_id) {

        $this->db->where('user_id', $user_id);
        return $this->db->delete('tbl_user');
    }

    function user_list($offset, $limit) {

        $this->db->select('email, first_name, last_name, profile_picture, gender, dob');
        $this->db->from('tbl_user');
        $this->db->where('is_active', 1);
        $this->db->limit($limit, $offset);
        $list = $this->db->get();
        return $list->result_array();
    }

}

?>