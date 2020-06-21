<?php namespace RainLab\User\Models;
use RainLab\User\Models\NewModel;
use RainLab\User\Models\Users\AccessRights;
use RainLab\User\Models\User;

class Product extends NewModel
{
	protected $table = 'product';

	public static $field_list = [
		'id'						=> [ 'type' => 'integer', ],
		'name'						=> [ 'type' => 'string', ],
		'description'				=> [ 'type' => 'text', ],
		'popularity'				=> [ 'type' => 'integer', ],		
		'published_at'				=> [ 'type' => 'timestamp', ],
		'creator_user_id'			=> [ 'type' => 'integer', ],		
		'company_id'				=> [ 'type' => 'integer', ],
		'product_status_id'			=> [ 'type' => 'integer', ],
		
		'created_at'					=> [ 'type' => 'timestamp', ],
		'updated_at'					=> [ 'type' => 'timestamp', ],
		'deleted_at'					=> [ 'type' => 'timestamp', ],
	];

	protected $fillable = [
		'name','description','popularity','published_at'
	];
	public static function checkAccess( $action = '',$data = [] ) {
		if( !empty($action) ) {
			$section = 'product';

			$user_type = AccessRights::UserTypeAccess();
			if( !empty($user_type) ) {
				$action_section_access = AccessRights::checkAccess( $user_type, $section, $action );
				
				if( $action == 'add' && $action_section_access )
					return true;
				if( !empty( $data ) && $action_section_access ) {			
					$product_list = Product::getAll( [ 'filters' => [ 'id' => $data['id'] ], 'limit' => 1 ] );
					$access_right = AccessRights::getAccessRight( $user_type, $section, $action );

					if( ( $access_right == 'own' && $product_list[0]['creator_user_id'] === $GLOBALS['user']['id'] )
						||
						( $access_right == 'company' && $product_list[0]['company_id'] === $GLOBALS['user']['company_id'] )
					)
						return true;
				}
			}
		}
		return false;
	}
	
	public static function get_select_fields( $options = [] ) {
		$prefix = !empty($options['prefix']) ? $options['prefix'] : 'product.';
		if( !empty($options['fields'] ) )
			switch( $options['fields'] ) {
				case 'count':
					return [  \DB::raw('count(*) as cnt') ];
						
			}
		return [ $prefix.'id', $prefix.'name', $prefix.'description',$prefix.'description' ];
	}

	public static function prepareFilters( $requestArray = [],$options = []) {
		$result_obj = parent::prepareFilters( $requestArray ,$options);
		
		$result_obj->leftjoin( 'product_status as p_s', function( $join ) {
			$join->on( 'p_s.id', '=', 'product.product_status_id' );
		} );
		$result_obj->leftjoin( 'users as creator_user', function( $join ) {
			$join->on( 'creator_user.id', '=', 'product.creator_user_id' );
		} );
		
		return $result_obj;
	}
}
