<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/webhook/api.php");


class SaveReview extends ApiAbstract
{
	public $apiName = 'save_review';
	
	private $bookId;
	private $externalUserId;
	private $name;
	private $comment;
	private $rating;
	private $pros;
	private $cons;
	
	protected function createAction()
	{
		$this->getJsonData();
		$review = $this->setReview();
		
		$this->createLog('Reviews ответ: ', (int)$review);
		return $this->response(['success' => $review]);
	}
	
	private function getJsonData()
	{
		$this->bookId = $this->requestParams['item'] ? (int)$this->requestParams['item'] : 0;
		$this->externalUserId = $this->requestParams['userId'] ?? 0;
		$this->name = $this->requestParams['name'] ?? '';
		$this->comment = $this->requestParams['comment'] ?? '';
		$this->rating = $this->requestParams['rating'] ?? 0;
		$this->pros = $this->requestParams['pros'] ?? '';
		$this->cons = $this->requestParams['cons'] ?? '';
		
		return true;
	}
	
	private function setReview() {
		if ($this->bookId > 0 && !empty($this->comment) && (int)$this->rating > 0) {
			Bitrix\Main\Loader::registerAutoLoadClasses(null, array(
				'CleverReview' => '/local/lib/review.php',
			));
			$params = [
				'source' => 69741, // флаг что источник приложение 
				'author_name' => $this->name,
				'product_id' => $this->bookId,
				'body' => $this->comment,
				'recommended' => '1', // FIX ???
				'rating' => $this->rating,
				'location_name' => '', // FIX ???
				'pros' => $this->pros,
				'cons' => $this->cons,
			];
			
			if ($this->externalUserId) {
				$user = CUser::GetList(
					($by = "LAST_LOGIN"),
					($order = "DESC"),
					array(
						'ID' => $this->externalUserId,
						'ACTIVE' => 'Y'
					),
					array('SELECT' => 'ID, EMAIL')
				)->Fetch();
				if (!empty($user['EMAIL'])) {
					$userData = [
						'author_details' => [
							[
								'name' => 'email',
								'value' => $user['EMAIL']
							]
						]
					];
					
					$params = array_merge($params, $userData);
				}
			}
			$id = md5($this->comment);
			CleverReview::setReviewAplaut($params, $id);
			
			return true;
		}
		
		return false;
	}
	
	protected function indexAction()
	{
		// TODO: Implement indexAction() method.
	}
	
	protected function viewAction()
	{
		// TODO: Implement viewAction() method.
	}
	
	protected function updateAction()
	{
		// TODO: Implement updateAction() method.
	}
	
	protected function deleteAction()
	{
		// TODO: Implement deleteAction() method.
	}
	
}
