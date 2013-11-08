<?php
require_once APP_ROOT.'/model/Application.php';

class appsActions extends MainActions
{
	protected $app = null;

	public function initialize()
	{
		if(($err=parent::initialize())){
			return $err;
		}
		if(!in_array($this->getAction(),array('new','create'))){
			$id = mfwRequest::param('id');
			$this->app = ApplicationDb::retrieveByPK($id);
		}
	}

	public function build($params)
	{
		if(!isset($params['app'])){
			$params['app'] = $this->app;
		}
		return parent::build($params);
	}


	public function executeIndex()
	{
		//仮
		$url = mfwRequest::makeUrl('/apps/new');
		return array(array(),"<a href=\"$url\">new</a>");
	}

	public function executeNew()
	{
		$params = array(
			);
		return $this->build($params);
	}

	public function executeCreate()
	{
		$title = mfwRequest::param('title');
		$data = mfwRequest::param('icon-data');
		$description = mfwRequest::param('description');
		if(!$title || !preg_match('/^data:[^;]+;base64,(.+)$/',$data,$match)){
			return $this->response(self::HTTP_400_BADREQUEST);
		}
		$image = base64_decode($match[1]);

		$con = mfwDBConnection::getPDO();
		$con->beginTransaction();
		try{
			$app = ApplicationDb::insertNewApp(
				$this->login_user,$title,$image,$description);
			$con->commit();
		}
		catch(Exception $e){
			$con->rollback();
			throw $e;
		}

		return $this->redirect("/apps/detail?id={$app->getId()}");
	}

	public function executeDetail()
	{
		$platform = mfwRequest::param('pf');

		if(!$platform){
			$platform = 'android';// fixme: UA見て変える
		}

		$owners = $this->app->getOwners();
		$ownerid = $owners->searchPK('owner_mail',$this->login_user->getMail());
		$params = array(
			'pf' => $platform,
			'is_owner' => ($ownerid!==null),
			);
		return $this->build($params);
	}

}
