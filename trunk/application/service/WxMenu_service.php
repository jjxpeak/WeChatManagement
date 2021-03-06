<?php
class WxMenu_service extends MY_Service {
	const MSG_NAME_NOT_NULL = "菜单名称不能为空";
	const MSG_PID_NOT_NULL = "上级菜单无效";
	const MSG_PID_ONE_NOT_NULL = "一级菜单只能创建3个";
	const MSG_PID_TOW_NOT_NULL = "二级菜单只能创建5个";
	const MSG_MENU_EXISTS = "此菜单已存在";
	const MSG_BUILT = "内置菜单不可执行此操作";
	const MSG_MAKE_NULL = "自定义菜单上传不能为空";

	public function __construct() {
		parent::__construct();
		$this->loadModel("WxMenu","MySysLog");
	}

	/**
	 *添加信息
	 */
	public function addoredit($options = array()) {
		$optionsKeys = array(
			"id",
			"user_id",
			"token",
			"pid",
			"title",
			"keyword",
			"is_show",
            "sort",
            "type",
            "url",
            "note"
		);
		extract(formatOptions($options, $optionsKeys));

		$data = array();

		if (optionExists($id)) {
			$data['id'] = $id;
		}
		if (optionExists($user_id)) {
			$data['user_id'] = $user_id;
		}else{
            $data['user_id'] = $this->login_user['user_id'];
        }

		if (optionExists($token)) {
			$data['token'] = $token;
		}else{
            $data['token'] = $this->login_user['token'];
        }
		if (optionExists($pid)) {
            //检验id是否有效
            $Wx_menu = $this->WxMenu_model->where('id',$pid)->get_item();
            if($pid!=0 and empty($Wx_menu)){
                return wrrong_msg(self::MSG_NAME_NOT_NULL);
            }
			$data['pid'] = $pid;
		}
		if (optionExists($title)) {
			$data['title'] = $title;
		}else{
            return wrrong_msg(self::MSG_NAME_NOT_NULL);
        }
		if (optionExists($keyword)) {
			$data['keyword'] = $keyword;
		}
		if (optionExists($is_show)) {
			$data['is_show'] = $is_show;
		}
		if (optionExists($sort)) {
			$data['sort'] = $sort;
		}
		if (optionExists($type)) {
			$data['type'] = $type;
		}
		if (optionExists($url)) {
			$data['url'] = $url;
		}
		if (optionExists($note)) {
			$data['note'] = $note;
		}


		if (optionExists($id)) {
			$success = $this->WxMenu_model->where("id", $id)->set($data)->update();
		} else {
            if($pid==0){
                //一级菜单不能超过3
                $count = $this->WxMenu_model->where('pid',0)->count();
                if($count >= 3){
                    return wrrong_msg(self::MSG_PID_ONE_NOT_NULL);
                }
            }else{
                //二级菜单不能超过5
                $count = $this->WxMenu_model->where('pid',$pid)->count();
                if($count >= 5 or $count < 0){
                    return wrrong_msg(self::MSG_PID_TOW_NOT_NULL);
                }
            }
			$success = $this->WxMenu_model->set($data)->insert();
		}

		if (!$success) {
			return wrrong_msg(MSG_SERVICE_BUSY);
		}
		if(!$id){
            $this->MySysLog_model->set(array(
                "user_name"=>$this->login_user['user_name'],
                "action"=>"IP地址: ".ip(),
                "class_name"=>"Admin_WxMenu",
                "class_obj"=>"add",
                "result"=>$title."添加成功",
                "op_time"=>time()
            ))->insert();
            return success_msg(MSG_ADD_SUCCESS);
        }else{
            $this->MySysLog_model->set(array(
                "user_name"=>$this->login_user['user_name'],
                "action"=>"IP地址: ".ip(),
                "class_name"=>"Admin_WxMenu",
                "class_obj"=>"add",
                "result"=>$title."修改成功",
                "op_time"=>time()
            ))->insert();
            return success_msg(MSG_EDIT_SUCCESS);
        }
	}


	/**
	 * 获取列表
	 * @param unknown $options	数组
	 * @return multitype:NULL string
	 */
	public function get_list($options = array()) {
        $optionsKeys = array(
            "id",
            "pid",
            "title",
            "keyword",
            "sort",
            "type"
        );
        extract(formatOptions($options, $optionsKeys));
        $where=array();
        if (optionExists($id)) {
            $where['id'] = $id;
        }
        if (optionExists($pid)) {
            $where['pid'] = $pid;
        }
        if (optionExists($title)) {
            $where['title'] = $title;
        }
        if (optionExists($keyword)) {
            $where['keyword'] = $keyword;
        }
        if (optionExists($sort)) {
            $where['sort'] = $sort;
        }
        if (optionExists($type)) {
            $where['type'] = $type;
        }
		$menu_url = $this->WxMenu_model->where($where)->order_by('sort','asc')->get_list();
		$menu = $this->getTree($menu_url,0);
		return $menu;
	}

    public function getTree($data, $pId)
	{
		$tree = '';
		foreach($data as $k => $v)
		{
			if($v['pid'] == $pId)
			{         //父亲找到儿子
				$v['children'] = self::getTree($data, $v['id']);
				$tree[] = $v;
				//unset($data[$k]);
			}
		}
		return $tree;
	}

	/**
	 *	删除内容
	 */
	public function delete($options = array()) {
		$optionsKeys = array("id");
		extract(formatOptions($options, $optionsKeys));
		if (!$id) {
			return wrrong_msg(MSG_INVALID_ARGUMENTS);
		}

		$success = $this->WxMenu_model->where("id", $id)->delete();
		if (!$success) {
			return wrrong_msg(MSG_SERVICE_BUSY);
		}
        $this->MySysLog_model->set(array(
            "user_name"=>$this->login_user['user_name'],
            "action"=>"IP地址: ".ip(),
            "class_name"=>"Admin_WxMenu",
            "class_obj"=>"del",
            "result"=>"删除微信自定义菜单：id为".$id,
            "op_time"=>time()
        ))->insert();
		return success_msg(MSG_DELETE_SUCCESS);

	}

    /**
     * 生成菜单
     * @param array $options
     * @return array
     */
	public function make($options = array()){
        $optionsKeys = array("token");
        extract(formatOptions($options, $optionsKeys));
        $where = array();
        if (!$token) {
            return wrrong_msg(MSG_INVALID_ARGUMENTS);
        }
        if(optionExists($token)){
            $where['token'] = $token;
        }
        $menu_list = $this->WxMenu_model->where($where)->order_by('sort','asc')->get_list();
        $menu_list = $this->getTree($menu_list,0);
        //生成菜单数据

        if(!empty($menu_list)){
//未完成
            $access_token = 1;
            $url = Weixin::POST_MENU_CREATE.$access_token;
            $json = Curl::post($url, self::buttonData($menu_list));
            $success = json_decode($json,true);
            if($success['errcode']==0){
                return $success['errmsg'];
            }else{
                return $success['errmsg'];
            }
        }else{
            return wrrong_msg(self::MSG_MAKE_NULL);
        }
    }

    public static function getAccessToken($suite_id,$suite_ticket,$suite_secret)
    {
        Logger::debug('%s %s %s',$suite_id,$suite_ticket,$suite_secret);
        if (empty ( $suite_id ) || empty ($suite_ticket) || empty($suite_secret)) {
            return 0;
        }

        $memKey = MemcacheNameDef::SUITE_ACCESS_TOKEN . $suite_id;
        $token = PressMemcached::get($memKey);
        if(!$token) {
            $param = array(
                'suite_id' => $suite_id,
                'suite_ticket' => $suite_ticket,
                'suite_secret' => $suite_secret
            );

            $header = array('content-type: application/json; charset=UTF-8');
            $url = WeixinDef::SUITE_ACCESS_TOKEN_URL;
            $data = Util::hs_curl_post($header, $url, json_encode($param));
            $de = json_decode($data);
            Logger::debug('suite_access_token : %s',$data);
            if (isset($de->suite_access_token)) {
                $token = $de->suite_access_token;
                PressMemcached::set($memKey, $token, 7000);
            } else {
                $token = 0;
            }
        }
        return $token;
    }

    function buttonData($data){
        $button = array();
        $button_2 = array();
        if(empty($data)){
           return NULL;
        }
        foreach ($data as $key=>$value){
            if(!empty($value['children']) and isset($value['children'])){
                foreach ($value['children'] as $v){
                    $button_2[] = array(
                        'name' => $v['title'],
                        'type' => 'view',
                        'url' => 'http://www.ijyue.com',
                    );
                }
                $button[] = array(
                    "name"=>$value['title'],
                    "sub_button"=>$button_2,
                );
            }else{
                $button[] = array(
                    "name"=>$value['title'],
                    "type"=>"view",
                    "url"=>"http://www.ijyue.com",
                );
            }

        }
        return array(
            'button'=>$button
        );
    }

}
?>