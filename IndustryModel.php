<?php namespace RainLab\User\Models;
use RainLab\User\Models\NewModel;

class Industry extends NewModel
{
	protected $table = 'industry';

	public static $field_list = [
		'id'						=> [ 'type' => 'integer', ],
		'industry'				=> [ 'type' => 'string', ],
		'industry_code'			=> [ 'type' => 'integer', ],
		'industry_group'		=> [ 'type' => 'string', ],

		'created_at'	=> [ 'type' => 'timestamp', ],
		'updated_at'	=> [ 'type' => 'timestamp', ],
		'deleted_at'	=> [ 'type' => 'timestamp', ],
	];

	protected $fillable = [
		'industry','industry_code','industry_group',
	];
	public static function get_select_fields( $options = [] ) {
		$prefix = !empty($options['prefix']) ? $options['prefix'] : '';
		switch( $options['fields'] ) {
			case 'industry_group_code':
				return [ $prefix.'.industry_code', $prefix.'.industry_group' ];
			   
		}
		return [ $prefix.'id', $prefix.'industry', $prefix.'industry_code',$prefix.'industry_group', ];
	}

	public static function prepareFilters( $requestArray = [],$options = []) {
		$result_obj = parent::prepareFilters( $requestArray ,$options);
		
		return $result_obj;
	}
}
