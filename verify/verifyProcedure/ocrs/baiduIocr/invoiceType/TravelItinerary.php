<?php


namespace app\components\verifyProcedure\ocrs\baiduIocr\invoiceType;


use yii\base\Model;

class TravelItinerary extends Model
{
	public $date;
	public $fare;
	public $flight;
	public $issued_by;
	public $starting_station;
	public $ticket_rates;
	public $ck;
	public $oil_money;
	public $serial_number;
	public $ticket_number;
	public $carrier;
	public $id_num;
	public $fare_basis;
	public $destination_station;
	public $name;
	public $agent_code;
	public $time;
	public $class;
	public $dev_fund;
	public $start_date;

	public function rules()
	{
		return [
			[[
				'date',
				'fare',
				'flight',
				'issued_by',
				'starting_station',
				'ticket_rates',
				'ck',
				'oil_money',
				'serial_number',
				'ticket_number',
				'carrier',
				'id_num',
				'fare_basis',
				'destination_station',
				'name',
				'agent_code',
				'time',
				'class',
				'dev_fund',
				'start_date',
			], 'string'],
		];
	}
}