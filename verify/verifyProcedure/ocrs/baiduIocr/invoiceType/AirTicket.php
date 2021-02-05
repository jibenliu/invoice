<?php


namespace app\components\verifyProcedure\ocrs\baiduIocr\invoiceType;


use yii\base\Model;

class AirTicket extends Model
{
	public $date;
	public $flight;
	public $starting_station;
	public $ticket_rates;
	public $destination_station;
	public $name;
	public $ticket_number;

	public function rules()
	{
		return [
			[[
				'date',
				'flight',
				'starting_station',
				'ticket_rates',
				'destination_station',
				'name',
				'ticket_number',
			], 'string'],
		];
	}
}