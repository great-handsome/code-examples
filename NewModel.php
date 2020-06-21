<?php namespace RainLab\User\Models;
use Carbon\Carbon;
use October\Rain\Database\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NewModel extends Model
{
	protected $table = '';	
	public static $field_list = [ ];   	
	protected $fillable = [ ];
	
	public static function getTableName()
	{
		return with(new static)->getTable();
	}
	public function delete() {
		$this->deleted_at = now();
		return $this->save();
	}

	public static function getPage($requestArray = [],$options = [] ) {
		if( empty($requestArray['order']) || empty($requestArray['order']['field']) ) {
			$requestArray['order']['field'] = 'id';
			$requestArray['order']['dir'] = 'desc';
		}

		$requestArray['offset'] = $requestArray['pager']['current_page'] * $requestArray['pager']['page_size'];
		$requestArray['limit'] = $requestArray['pager']['page_size'];
		if( empty($options['prefix']) )
			$options['prefix'] = static::getTableName().'.';
			
		$result_obj = static::prepareFilters( $requestArray ,$options);

		$select_list = static::get_select_fields($options);
		$result_obj->select($select_list);

		$pager = $result_obj->paginate($requestArray['pager']['page_size'])->toArray();
		if( !empty($requestArray['offset']) )
			$result_obj->offset($requestArray['offset']);

		$result_obj = $result_obj->get();

		$result['rows'] = $result_obj->toArray();
		if( !empty($options['prepare_send']) )
			foreach($result['rows'] as $k => $v )
				$result['rows'][$k] = static::prepare_send_data($result['rows'][$k]);
	
		$result['count'] = $pager['total'];
		if( !empty($options['post_process']) )
			$result['rows'] = static::post_process($result['rows'],$options);
		return $result;
	}
	protected static function post_process( $result,$options = [] ) {
		return $result;
	}
	public static function get_select_fields( $options = [] ) {
		return [];
	}
	public static function getAll($requestArray = [],$options = [] ) {
		if( empty($options['prefix']) )
			$options['prefix'] = static::getTableName().'.';
		$result_obj = static::prepareFilters( $requestArray , $options );
		
		if( !empty($requestArray['offset']) )
			$result_obj->offset($requestArray['offset']);
		
		
		$result_obj = $result_obj->get(static::get_select_fields($options) );
		
		if( !empty($options['index']) )
			$result_obj = $result_obj->keyBy($options['index']);
		$result = $result_obj->toArray();
		if( !empty($options['post_process']))
			$result = static::post_process($result,$options);

		return $result;
	}
	
	public static function prepareFilters( $requestArray = [],$options = []) {
		if( !isset($requestArray['filters']) )
			throw new \Exception('empty filters', 100);
		
		$prefix = !empty($options['prefix']) ? $options['prefix'] : '';
		
		if( empty($requestArray['order']) || empty($requestArray['order']['field']) )
			$requestArray['order'] = [ 'field' => $prefix.'id' , 'dir' => 'asc' ];
		
		$result_obj = static::when( !empty($requestArray['filters']) ,function ($q) use ($requestArray,$prefix) {
			$filtersArray = $requestArray['filters'];
			if( !empty($filtersArray) ) {
				foreach(static::$field_list as $field => $field_conf) {
					if( isset($filtersArray[$field]) ) {
						if( $field_conf['type'] === 'timestamp' && !empty($filtersArray[$field]) ) {
							$q->where($prefix.$field,'=',$filtersArray[$field]);
						}
						elseif( in_array($field_conf['type'],['date']) && !empty($filtersArray[$field]) ) {
							$temp = explode('/',$filtersArray[$field]);
							$q->where($prefix.$field,'=',$temp[2].'-'.$temp[1].'-'.$temp[0]);
						}
						elseif( in_array($field_conf['type'],['integer','float']) 
								&& (!empty($filtersArray[$field]) || $filtersArray[$field]===0) 
							) {
							if( is_array($filtersArray[$field]) )
								$q->whereIn($prefix.$field,$filtersArray[$field]);
							else
								$q->where($prefix.$field,'=',$filtersArray[$field]);
						}
						elseif( in_array( $field_conf['type'],[ 'string','text' ] ) && !empty($filtersArray[$field]) )
							$q->where($prefix.$field,'ilike','%'.$filtersArray[$field].'%');
					}
				}
			}
		});
		if( empty($requestArray['filters']['show_deleted']) )
			$result_obj->whereNull($prefix.'deleted_at');
		
		if( !empty($requestArray['limit']) )
			$result_obj->limit($requestArray['limit']);
		if( !empty($requestArray['groupBy']) )
			$result_obj->groupBy($requestArray['groupBy']);		
		if( empty($options['no_order']) ) {
			if( !empty($requestArray['order']['nulls_first_last']) ) {
				$result_obj->orderByRaw(' '.$requestArray['order']['field'].' '.$requestArray['order']['dir'].' '.$requestArray['order']['nulls_first_last']);
			}
			else {
				if( !empty($requestArray['order']['field']) && is_array($requestArray['order']['field']) ) {
					foreach($requestArray['order']['field'] as $kk => $v_field )
						$result_obj->orderBy($v_field, $requestArray['order']['dir'][$kk]);
				}
				else
					$result_obj->orderBy($requestArray['order']['field'], $requestArray['order']['dir']);
			}
			if( $requestArray['order']['field'] != 'id' && empty($requestArray['groupBy']) && empty($requestArray['no_id_sort']) )
				$result_obj->orderBy($prefix.'id','asc');			
		}

		return $result_obj;
	}
	public static function prepare_request_data( $data , $options = [] ) {
		$result = [];
		$class_field_list = ( !empty($options) && !empty($options['field_list']) ) ? $options['field_list'] : static::$field_list;

		foreach($data as $key => $value) {
			if( isset($class_field_list[$key]) ) {
				if( $class_field_list[$key]['type'] == 'date' ) {
					if( !empty($value) && $value != 'null' )
					{
						$date_ar = explode('/',$value);
						$result[$key] = $date_ar[2].'-'.$date_ar[1].'-'.$date_ar[0];
					}
					else
						$result[$key] = null;
				}
				elseif( $class_field_list[$key]['type'] == 'timestamp' ) {
					$result[$key] = NewModel::dateTime_to_sqlTimestamp($value);
				}
				elseif( ( $class_field_list[$key]['type'] == 'string' || $class_field_list[$key]['type'] == 'text' )
						&& $value !== "0" && empty($value) )
					$result[$key] = '';
				elseif($class_field_list[$key]['type'] == 'integer' && $value !== "0" && empty($value) && $key != 'id' )
				{
					if( empty($class_field_list[$key]['nullable']) )
						$result[$key] = 0;
					else
						$result[$key] = null;
				}
				elseif($class_field_list[$key]['type'] == 'int_bool' )
				{
					if( empty($value) || $value === 'false' || $value === '0' )
						$result[$key] = 0;
					else
						$result[$key] = 1;
				}
				else					
					$result[$key] = $value;
			}
		}
		return $result;
	}
	
	public static function save_data($data) {
		if( !empty($data['id']) )
			$obj = static::find((int)$data['id']);
		else
			$obj = new static();	
		
		$obj->fill( static::prepare_request_data($data) );
		$obj->save();
		return $obj;
	}
	public static function save_array( $data = [] , $update_array = [] ) {
		if( !empty($data) ) {
			foreach($data as $k => $v) {
				$data[$k]['created_at'] = now();
				$data[$k]['updated_at'] = now();
			}
			self::insert($data);
		}		
	}
	public static function prepare_send_data( $data, $options = [] ) {
		$class_field_list = ( !empty($options) && !empty($options['field_list']) ) ? $options['field_list'] : static::$field_list;
		$result = [];
		foreach($data as $key => $value) {
			if( in_array($key,['deleted_at','created_at','updated_at','user_id']) )
				continue;
			if( isset($class_field_list[$key]) ) {
				if( $class_field_list[$key]['type'] == 'date' ) {
					if( !empty($value) )
					{
						$date_ar = explode('-',$value);
						$result[$key] = $date_ar[2].'/'.$date_ar[1].'/'.$date_ar[0];
					}
					else
						$result[$key] = '';
				}
				elseif( $class_field_list[$key]['type'] === 'timestamp') {
					if( !empty($value) )
					{
						$temp = explode(' ',$value);
						if( !empty($temp) && count($temp) > 1 ) {
							$date_ar = explode('-',$temp[0]);
							$time_ar = explode(':',$temp[1]);
							$mk_time  = mktime((int)$time_ar[0],(int)$time_ar[1],(int)$time_ar[2],(int)$date_ar[1],(int)$date_ar[2],(int)$date_ar[0]);
							$result[$key] = date('d/m/Y h:i A',$mk_time);
						}
						else
							$result[$key] = '';
					
					}
					else
						$result[$key] = '';
				}
				else
					$result[$key] = $value;
			}
			else
				$result[$key] = $value;
		}
		return $result;
	}
	
	public static function validate($data,$trim = false,$custom_rules = [],$custom_messages = []) {
		if( $trim ) {
			$data = static::trim($data);
		}
		$rules = static::validateRules();
		if( !empty($custom_rules) ){
			 $rules = $custom_rules;
		}
		$errors = [];
		
		$validator = Validator::make($data,$rules,$custom_messages);

		$validator = static::customValidateRules($validator,$data);

		if( $validator->fails() ) {
			$messages = $validator->getMessageBag()->toArray();
			foreach ($messages as $k => $v) {
				$errors[$k] = $v[0];
			}
		}
		return $errors;
	}

	public static function validateRules($fields = []){
		$rules = [];
		if( empty($fields) )
			$fields = static::$field_list;
		foreach ($fields as $k => $field){
			if($k == 'id' )
				continue;
			$rule = [];
			if(!empty($field['required'])){
				array_push($rule,'required|filled');
			}else{
				array_push($rule,'nullable');
			}
			if($field['type'] == 'string'){
				array_push($rule,'string');
			}else if($field['type'] == 'integer') {
				array_push( $rule, 'integer' );
			}else if($field['type'] == 'float'){
				array_push( $rule, 'numeric' );
			}else if($field['type'] == 'date'){
				array_push($rule,'date_format:d/m/Y');
			}else if($field['type'] == 'timestamp'){
				array_push($rule,'date_format:d/m/Y h:i A');
			}
			if(isset($field['values'])){
				array_push($rule,Rule::in($field['values']));
			}
			if(isset($field['length'])){
				array_push($rule,'max:'.$field['length']);
			}
			$rules[$k] = implode('|',$rule);
		}
		return $rules;
	}
	public static function customValidateRules($validator,$data){
		return $validator;
	}

	public static function trim($data){
		return array_map(function($itm){
			if( is_string($itm))
				return trim($itm);
			else
				return $itm;
		},$data);
	}
}
