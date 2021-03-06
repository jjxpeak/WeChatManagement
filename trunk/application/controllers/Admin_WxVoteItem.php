<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Admin_WxVoteItem extends MY_Controller {

	public function __construct() {
		parent::__construct();
		$this->loadService("WxSystem","WxVote","WxVoteItem");
		$this->loadModel("WxVoteItem");
	}


    /**
     * 加载主页面
     */
    public function index() {
        $WxVoteItem = $this->WxVoteItem_service->get_list();
        $this->assign("WxVoteItem", $WxVoteItem['rows']);

        $this->view("admin/wxvoteitem/index");
    }

    /**
     * 修改
     */
    public function edit() {
        $id = $this->input->get('id');
        $input = $this->input->post(NULL);
        if (!empty($input)) {
            if($_FILES){
                $input['startpicurl'] = $this->upload($_FILES['startpicurl']);
                $input['startpicurl2'] = $this->upload($_FILES['startpicurl2']);
                $input['startpicurl3'] = $this->upload($_FILES['startpicurl3']);
                $input['startpicurl4'] = $this->upload($_FILES['startpicurl4']);
                $input['startpicurl5'] = $this->upload($_FILES['startpicurl5']);
            }else{
                $input['startpicurl'] = NULL;
                $input['startpicurl2'] = NULL;
                $input['startpicurl3'] = NULL;
                $input['startpicurl4'] = NULL;
                $input['startpicurl5'] = NULL;
            }
            $success = $this->WxVoteItem_service->addoredit($input);
            if($success['success']){
                alert($success['msg'],"/Admin_WxVoteItem/index");
            }else{
                alert($success['msg']);
            }
        }
        $WxVote = $this->WxVote_service->get_list();
        $this->assign("WxVote", $WxVote['rows']);
        $wxs= $this->WxVoteItem_model->where('id',$id)->get_item();
        $this->assign("WxVoteItem", $wxs);
        $this->assign("time", time());
        $this->view("admin/wxvoteitem/edit");
    }

    /**
     * 添加
     */
    public function add() {
        $input = $this->input->post(NULL);
        if (!empty($input)) {
            if($_FILES){
                $input['startpicurl'] = $this->upload($_FILES['startpicurl']);
                $input['startpicurl2'] = $this->upload($_FILES['startpicurl2']);
                $input['startpicurl3'] = $this->upload($_FILES['startpicurl3']);
                $input['startpicurl4'] = $this->upload($_FILES['startpicurl4']);
                $input['startpicurl5'] = $this->upload($_FILES['startpicurl5']);
            }else{
                $input['startpicurl'] = NULL;
                $input['startpicurl2'] = NULL;
                $input['startpicurl3'] = NULL;
                $input['startpicurl4'] = NULL;
                $input['startpicurl5'] = NULL;
            }
            $success = $this->WxVoteItem_service->addoredit($input);
            if($success['success']){
                alert($success['msg'],"/Admin_WxVoteItem/index");
            }else{
                alert($success['msg']);
            }

        }

        $WxVote = $this->WxVote_service->get_list();
        $this->assign("WxVote", $WxVote['rows']);
        //模版获取
        $this->assign("moban", array());
        $this->assign("time", time());
        $this->view("admin/wxvoteitem/add");
    }

    /**
     * 删除
     */
    public function del() {
        $input = $this->input->get(NULL);
        $success = $this->WxVoteItem_service->delete($input);
        alert($success['msg']);
    }

    /**
     * 禁用
     */
    public function disable() {
        $input = $this->input->get(NULL);
        $input['status'] = FALSE;
        $success = $this->WxVoteItem_service->editStatus($input);
        alert($success['msg']);
    }

    /**
     * 启用
     */
    public function enable() {
        $input = $this->input->get(NULL);
        $input['status'] = TRUE;
        $success = $this->WxVoteItem_service->editStatus($input);
        alert($success['msg']);
    }


    /**
     * 启用
     */
    public function suo() {
        $input = $this->input->get(NULL);
        $input['status'] = 2;
        $success = $this->WxVoteItem_service->editStatus($input);
        alert($success['msg']);
    }

    /**
     * 后台上传图片
     * @param null $file
     * @return string
     */
    public function upload($file = NULL){
        if($file['name']==""){
            return null;
        }
        $img = upload($file, "/uploads/pic", function() use ($file) {
            if (!maxFileSize($file)) {
                return false;
            }
            if (!isImage($file)) {
                return false;
            }
        });
        if (!$img) {
            $message = '上传失败';
        }
//        return $this->config->config['server_url'].$img;
        return $img;
    }

    /**
     * ajax上传图片返回路径
     * @param null $file
     */
    public function uploadFile($file = NULL){
        $file = $_FILES['upload'];
        $img = upload($file, "/uploads/pic", function() use ($file) {
            if (!maxFileSize($file)) {
                return false;
            }
            if (!isImage($file)) {
                return false;
            }
        });
        $fn = $this->input->get('CKEditorFuncNum') ? $this->input->get('CKEditorFuncNum') : 1;
        $message = "";
        if (!$img) {
            $message = '上传失败';
        }
        mkhtml($fn, config_item("server_url") . str_replace("\\",'/',substr($img,1)), $message);
    }

}
