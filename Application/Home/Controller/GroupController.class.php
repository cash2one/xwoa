<?php
/*---------------------------------------------------------------------------
 小微OA系统 - 让工作更轻松快乐

 Copyright (c) 2013 http://www.smeoa.com All rights reserved.

 Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )

 Author:  jinzhu.yin<smeoa@qq.com>

 Support: https://git.oschina.net/smeoa/smeoa
 -------------------------------------------------------------------------*/

// 角色模块
namespace Home\Controller;

class GroupController extends HomeController {
	protected $config = array('app_type' => 'master');

	//过滤查询字段
	function _search_filter(&$map) {
		$map['is_del'] = array('eq', '0');
		$keyword = I('keyword');
		if (!empty($keyword)) {
			$map['User.name|emp_no|Position.name|Rank.name|Dept.name'] = array('like', "%" . $keyword . "%");
		}
	}

	public function index() {
		$list = M("Group") -> order('sort asc') -> select();
		$this -> assign('list', $list);
		$this -> display();
	}

	public function node() {
		$node_model = M("Node");
		if (!empty($_POST['eq_pid'])) {
			$eq_pid = $_POST['eq_pid'];
		} else {
			$eq_pid = "#";
		}

		//dump($node_model -> select());
		$node_list = $node_model -> order('sort asc') -> select();

		if ($eq_pid != "#") {
			$node_list = tree_to_list(list_to_tree($node_list, $eq_pid));
		} else {
			$node_list = tree_to_list(list_to_tree($node_list));
		}

		$node_list = rotate($node_list);
		//dump($node_list);
		$node_list = implode(",", $node_list['id']) . ",$eq_pid";

		$where['id'] = array('in', $node_list);
		$menu = $node_model -> field('id,pid,name,url') -> where($where) -> order('sort asc') -> select();

		$tree = list_to_tree($menu);
		$this -> assign('eq_pid', $eq_pid);

		$list = tree_to_list($tree);
		$this -> assign('node_list', $list);
		//$this->assign('menu',sub_tree_menu($list));

		$role_list = M("Role") -> order('sort asc') -> select();
		$this -> assign('list', $role_list);

		$group_list = $node_model -> where('pid=0') -> order('sort asc') -> getField('id,name');
		$this -> assign('group_list', $group_list);
		$this -> display();
	}

	public function del() {
		$role_id = $_POST['id'];

		$model = M("RoleNode");
		$where['role_id'] = $role_id;
		$model -> where($where) -> delete();

		$model = M("RoleUser");
		$model -> where($where) -> delete();
		$this -> _destory($role_id);
	}

	public function get_node_list() {
		$role_id = $_POST["role_id"];
		$model = D("Role");
		$data = $model -> get_node_list($role_id);
		if ($data !== false) {// 读取成功
			$return['data'] = $data;
			$return['status'] = 1;
			$this -> ajaxReturn($return);
		}
	}

	public function set_node() {
		$role_id = $_POST["role_id"];
		$org_list = $_POST["org_node_list"];
		$node_list = $_POST["node_list"];
		$admin_list = $_POST["admin"];
		$write_list = $_POST["write"];
		$read_list = $_POST["read"];

		$model = D("Role");
		$model -> del_node($role_id, $org_list);

		$result = $model -> set_node($role_id, $node_list);

		$model = M("RoleNode");
		$where['role_id'] = $role_id;

		if (!empty($admin_list)) {
			$where['node_id'] = array('in', $admin_list);
			$model -> where($where) -> setField('admin', 1);
		}

		if (!empty($write_list)) {
			$where['node_id'] = array('in', $write_list);
			$model -> where($where) -> setField('write', 1);
		}

		if (!empty($read_list)) {
			$where['node_id'] = array('in', $read_list);
			$model -> where($where) -> setField('read', 1);
		}

		if ($result === false) {
			$this -> error('操作失败！');
		} else {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('操作成功！');
		}
	}

	public function get_role_list() {
		$model = D("Role");
		$id = I('id');
		$data = $model -> get_role_list($id);
		if ($data !== false) {// 读取成功
			$return['data'] = $data;
			$return['status'] = 1;
			$this -> ajaxReturn($return);
		}
	}

	public function set_role() {
		$emp_list = $_POST["emp_id"];
		$role_list = $_POST["role_list"];
		//dump($_POST);
		//die;
		$model = D("Role");
		$model -> del_role($emp_list);

		$result = $model -> set_role($emp_list, $role_list);
		if ($result === false) {
			$this -> error('操作失败！');
		} else {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('操作成功！');
		}
	}

	public function user($id) {
		$map = $this -> _search();
		if (method_exists($this, '_search_filter')) {
			$this -> _search_filter($map);
		}

		$row_info = M("Group") -> find($id);
		$this -> assign('row_info', $row_info);

		$where_group_user['group_id'] = array('eq', $id);
		$group_user = M("GroupUser") -> where($where_group_user) -> getField('user_id', true);
		
		if(!empty($group_user)){
			$where_user_list['id'] = array('in', $group_user);	
		}else{
			$where_user_list['_string']='1=2';
		}
		
		$user_list = D("UserView") -> where($where_user_list) -> select();
		$this -> assign("user_list", $user_list);

		$this -> display();
	}

	public function add_user($group_id) {
		$map = $this -> _search();
		if (method_exists($this, '_search_filter')) {
			$this -> _search_filter($map);
		}
		$this -> assign('group_id', $group_id);

		$model = D("Group");
		$user_list = $model -> get_user_list($group_id);

		if (!empty($user_list)) {
			$map['id'] = array('not in', $user_list);
		}
		$user_list = D("UserView") -> where($map) -> select();
		$this -> assign("user_list", $user_list);
		$this -> display();
	}

	public function del_user($group_id,$user_id) {
		$model = D("Group");
		$result = $model -> del_user($group_id,$user_id);
		if ($result === false) {
			$this -> error('操作失败！');
		} else {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('操作成功！');
		}
	}

	public function save_user($group_id, $user_id) {
		$model = D("Group");
		$result = $model -> save_user($user_id, $group_id);
		if ($result === false) {
			$this -> error('操作失败！');
		} else {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('操作成功！');
		}
	}

	public function duty() {
		$duty = M("Duty");
		$duty_list = $duty -> order('sort asc') -> select();
		$this -> assign("duty_list", $duty_list);

		$role_list = M("Role") -> order('sort asc') -> select();
		$this -> assign('role_list', $role_list);
		$this -> display();
	}

	public function get_duty_list() {
		$role_id = $_POST["role_id"];
		$model = D("Role");
		$data = $model -> get_duty_list($role_id);
		if ($data !== false) {
			$return['status'] = 1;
			$return['data'] = $data;
			// 读取成功
			$this -> ajaxReturn($return);
		}
	}

	public function set_duty() {
		$role_id = $_POST["role_id"];
		$duty_list = $_POST["duty_list"];

		$model = D("Role");
		$model -> del_duty($role_id);

		$result = $model -> set_duty($role_id, $duty_list);
		if ($result === false) {
			$this -> error('操作失败！');
		} else {
			$this -> assign('jumpUrl', get_return_url());
			$this -> success('操作成功！');
		}
	}

}
?>