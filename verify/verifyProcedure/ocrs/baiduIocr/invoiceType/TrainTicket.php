<?php

namespace app\components\verifyProcedure\ocrs\baiduIocr\invoiceType;

use yii\base\Model;
use yii\helpers\BaseInflector;

class TrainTicket extends Model
{
	public $date;
	public $seat_category;
	public $starting_station;
	public $ticket_num;
	public $train_num;
	public $ticket_rates;
	public $name;
	public $destination_station;

	public function rules()
	{
		return [
			[[
				'date',
				'seat_category',
				'starting_station',
				'ticket_num',
				'train_num',
				'ticket_rates',
				'name',
				'destination_station',
			], 'string'],
		];
	}
}