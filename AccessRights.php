<?php namespace RainLab\User\Models\Users;

class AccessRights 
{
	public static $access_rights = [ // the rights: no, owner, company		
		'company-admin' => [ // type=Admin
			'products' => [
				'view' => 'company',
				'add' => 'company',
				'edit' => 'company',
				'delete' => 'company',
			],
			'users' => [
				'view' => 'company',
				'add' => 'company',
				'edit' => 'company',
				'delete' => 'company',
			],
			'clients' => [
				'view' => 'company',
				'assign' => 'company',
				'add' => 'company',
				'edit' => 'company',
				'delete' => 'company',
			]
		],
		'company-user' => [ // type=Standard
			'products' => [
				'view' => 'company',
				'add' => 'company',
				'edit' => 'company',
				'delete' => 'company',
			],
			'users' => [
				'view' => 'no',
				'add' => 'no',
				'edit' => 'no',
				'delete' => 'no',
			],
			'clients' => [
				'view' => 'company',
				'assign' => 'company',
				'add' => 'company',
				'edit' => 'company',
				'delete' => 'company',
			]
		],
		'anonymous' => [ // anonymous
			'products' => [
				'view' => 'code',
				'add' => 'no',
				'edit' => 'no',
				'delete' => 'no',
			],
			'users' => [
				'view' => 'no',
				'add' => 'no',
				'edit' => 'no',
				'delete' => 'no',
			],
			'clients' => [
				'view' => 'no',
				'add' => 'no',
				'edit' => 'no',
				'delete' => 'no',
			]
		],		
	];
	
	public static function UserTypeAccess( ) {
		$user_type = 'anonymous';
		if( !empty($GLOBALS['user']) ) {
			if( !empty($GLOBALS['user']) ) {
				if( $GLOBALS['user']['type'] == 'Standard')
					$user_type = 'company-user';
				if( $GLOBALS['user']['type'] == 'Admin')
					$user_type = 'company-admin';
			}
		}		
		return $user_type;
	}
	public static function checkUserAccess( $section = '', $action = '') {
		$user_type = static::UserTypeAccess();
		if( !empty($user_type) ) {
			return static::checkAccess($user_type,$section,$action);
		}
		else
			return false;
	}
	public static function getAccessRight( $user_type = '', $section = '', $action = '') {
		if( !empty($user_type) 
			&& !empty($section) 
			&& !empty($action) 
			&& !empty(static::$access_rights[$user_type])
			&& !empty(static::$access_rights[$user_type][$section])
			&& !empty(static::$access_rights[$user_type][$section][$action])
		)
			return static::$access_rights[$user_type][$section][$action];
		return '';
	}

	public static function checkAccess( $user_type = '', $section = '', $action = '') {
		$access_rights = static::getAccessRight($user_type , $section , $action);
		if( !empty($access_rights) && $access_rights != 'no' ) {
			return true;
		}		
		return false;
	}
}
