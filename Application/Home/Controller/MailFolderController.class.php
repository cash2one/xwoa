<?php
/*---------------------------------------------------------------------------
  小微OA系统 - 让工作更轻松快乐 

  Copyright (c) 2013 http://www.smeoa.com All rights reserved.                                             

  Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )  

  Author:  jinzhu.yin<smeoa@qq.com>                         

  Support: https://git.oschina.net/smeoa/smeoa               
 -------------------------------------------------------------------------*/


namespace Home\Controller;

class MailFolderController extends UserFolderController {
	protected $config=array('app_type'=>'personal');
	//过滤查询字段

	function index() {
		$this -> assign("folder_name", "邮件文件夹设置");				
		$this -> _index();
	}
}