<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Capstone_Controller
{


        private $page_;
        private $limit;

        function __construct()
        {
                parent::__construct();
                $this->lang->load('ci_capstone/ci_excel');
                $this->load->model('User_model');
                $this->load->library('pagination');

                /**
                 * pagination limit
                 */
                $this->limit = 10;

                /**
                 * get the page from url
                 * 
                 * if has not, default $page will is 1
                 */
                $this->page_ = get_page_in_url();
                $this->breadcrumbs->unshift(2, lang('index_heading'), 'users');
        }

        /**
         * @author Lloric Mayuga Garcia <emorickfighter@gmail.com>
         */
        public function index()
        {

                //list the users
                $users_obj = $this->User_model->
                        limit($this->limit, $this->limit * $this->page_ - $this->limit)->
                        order_by('created_on', 'DESC')->
                        order_by('updated_at', 'DESC')->
                        set_cache('users_page_' . $this->page_)->
                        get_all();


                /**
                 * where data array from db stored
                 */
                $table_data = array();
                /**
                 * check if has a result
                 * 
                 * sometime pagination can replace a page that has no value by crazy users :)
                 */
                if ($users_obj)
                {

                        foreach ($users_obj as $k => $user)
                        {
                                $users_obj[$k]->groups = $this->ion_auth->get_users_groups($user->id)->result();
                        }



                        foreach ($users_obj as $user)
                        {
                                $groups = '';
                                foreach ($user->groups as $group)
                                {
                                        $groups .= anchor(site_url("edit-group/?group-id=" . $group->id), my_htmlspecialchars($group->name)) . ' | ';
                                }
                                $tmp   = array();
                                $tmp[] = my_htmlspecialchars($user->last_name);
                                $tmp[] = my_htmlspecialchars($user->first_name);
                                $tmp[] = my_htmlspecialchars($user->username);
                                $tmp[] = my_htmlspecialchars($user->email);
                                $tmp[] = trim($groups, ' | ');

                                if (in_array('deactivate', permission_controllers()))
                                {
                                        $active_      = (($user->active) ? anchor(site_url("deactivate/?user-id=" . $user->id), 'set deactive') : anchor("users/activate/" . $user->id, 'set active'));
                                        $active_label = (($user->active) ? '<span class="done">' . lang('index_active_link') : '<span class="pending">' . lang('index_inactive_link')) . '</span>';
                                        $tmp[]        = array('data' => $active_label . nbs() . $active_, 'class' => 'taskStatus');
                                }
                                if (in_array('edit-user', permission_controllers()))
                                {
                                        $tmp[] = anchor(site_url("edit-user/?user-id=" . $user->id), 'Edit');
                                }
                                array_push($table_data, $tmp);
                        }
                }
                //  echo print_r($table_data);
                /*
                 * preparing html table
                 */
                /*
                 * header
                 */
                $header   = array();
                $header[] = lang('index_lname_th');
                $header[] = lang('index_fname_th');
                $header[] = lang('login_identity_label');
                $header[] = lang('index_email_th');
                $header[] = lang('index_groups_th');

                if (in_array('deactivate', permission_controllers()))
                {
                        $header[] = lang('index_status_th');
                }
                if (in_array('edit-user', permission_controllers()))
                {
                        $header[] = lang('index_action_th');
                }
                /**
                 * table values
                 */
                $this->data['table_data'] = $this->my_table_view($header, $table_data, 'table_open_bordered');

                /**
                 * pagination
                 */
                $this->data['pagination'] = $this->pagination->generate_link('users/index', $this->User_model->count_rows() / $this->limit);

                /**
                 * caption of table
                 */
                $this->data['caption'] = lang('index_heading');


                /**
                 * table of users ready,
                 * so whole html table with datas passing as var table_data_users
                 */
                /**
                 * templates for users controller
                 */
                // set the flash data error message if there is one

                $this->template['message']          = $this->session->flashdata('message');
                $this->template['table_data_users'] = MY_Controller::_render('admin/_templates/table', $this->data, TRUE);
                $this->template['controller']       = 'table';
                if (in_array('create-user', permission_controllers()))
                {

                        $this->template['create_user_button'] = MY_Controller::_render('admin/_templates/button_view', array(
                                    'href'         => 'create-user',
                                    'button_label' => lang('create_user_heading'),
                                    'extra'        => array('class' => 'btn btn-success icon-edit'),
                                        ), TRUE);
                }
                if ($this->ion_auth->is_admin())
                {
                        $this->template['export_user_button'] = MY_Controller::_render('admin/_templates/button_view', array(
                                    'href'         => 'users/export-excel',
                                    'button_label' => lang('excel_export'),
                                    'extra'        => array('class' => 'btn btn-info icon-download-alt')
                                        ), TRUE);
                }
                $this->template['bootstrap'] = $this->bootstrap();

                /**
                 * rendering users view
                 */
                ;


                $this->_render('admin/users', $this->template);
        }

        /**
         * Export data
         * @author Lloric Mayuga Garcia <emorickfighter@gmail.com>
         */
        public function export_excel()
        {
                $titles   = array(
                    lang('index_fname_th'),
                    lang('index_lname_th'),
                    lang('index_email_th'),
                    lang('index_groups_th'),
                    lang('index_status_th'),
                );
                $data_    = array();
                $user_obj = $this->ion_auth->users()->result();
                foreach ($user_obj as $k => $user)
                {
                        $user_obj[$k]->groups = $this->ion_auth->get_users_groups($user->id)->result();
                }
                foreach ($user_obj as $k => $user)
                {
                        $groups = '';
                        foreach ($user->groups as $group)
                        {
                                $groups .= $group->name . ' | ';
                        }
                        $data_[] = array(
                            $user->first_name,
                            $user->last_name,
                            $user->email,
                            trim($groups, ' | '),
                            ($user->active) ? lang('index_active_link') : lang('index_inactive_link'),
                        );
                }
                $this->load->library('excel');
                // echo print_r($data_);
                $this->excel->make_from_array($titles, $data_);
        }

        /**
         * 
         * @param type $id
         * @param type $code
         * @author ion_auth
         */
        public function activate($id = NULL, $code = false)
        {
                if ($code !== false)
                {
                        $activation = $this->ion_auth->activate($id, $code);
                }
                else if ($this->ion_auth->is_admin())
                {
                        $activation = $this->ion_auth->activate($id);
                }

                if ($activation)
                {
                        /**
                         * delete all query cache 
                         */
                        $this->delete_all_query_cache();
                        // redirect them to the auth page
                        $this->session->set_flashdata('message', $this->ion_auth->messages());
                        redirect(site_url('users'), 'refresh');
                }
                else
                {
                        // redirect them to the forgot password page
                        $this->session->set_flashdata('message', $this->ion_auth->errors());
                        redirect(site_url('users'), 'refresh');
                }
        }

        /**
         * 
         * @return array
         *  @author Lloric Garcia <emorickfighter@gmail.com>
         */
        private function bootstrap()
        {
                /**
                 * for header
                 * 
                 */
                $header       = array(
                    'css' => array(
                        'css/bootstrap.min.css',
                        'css/bootstrap-responsive.min.css',
                        'css/uniform.css',
                        'css/select2.css',
                        'css/matrix-style.css',
                        'css/matrix-media.css',
                        'font-awesome/css/font-awesome.css',
                        'http://fonts.googleapis.com/css?family=Open+Sans:400,700,800',
                    ),
                    'js'  => array(
                    ),
                );
                /**
                 * for footer
                 * 
                 */
                $footer       = array(
                    'css' => array(
                    ),
                    'js'  => array(
                        'js/jquery.min.js',
                        'js/jquery.ui.custom.js',
                        'js/bootstrap.min.js',
                        'js/jquery.uniform.js',
                        'js/select2.min.js',
                        'js/jquery.dataTables.min.js',
                        'js/matrix.js',
                        'js/matrix.tables.js',
                    ),
                );
                /**
                 * footer extra
                 */
                $footer_extra = '';
                return generate_link_script_tag($header, $footer, $footer_extra);
        }

}
