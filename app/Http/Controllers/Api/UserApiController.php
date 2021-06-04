<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\AuditLog;
use Lang;
use Validator;


class UserApiController extends Controller
{
    public function index()
    {
      $users =  User::select('id','name','username','email','street','city')->get();
      
	  if(count($users) > 0){
		foreach ($users as $user) {
			
			$user->address = array(
				'street' => $user->street,
				'city' 	=> $user->city,
			);
			unset( $user->street);
			unset( $user->city);
		}
	  }
        return response()->json(
            array(
                'status'    => config('constants.HTTP_OK'),
                'message'   => Lang::get('api_messages.success_getdata'),
                'data'      => $users
            )
        );
    }

    public function store(Request $request) {
		$request_data 		= $request->all();
		$audit_log			= array(
			'session_id'			=> '',
			'source_address'		=> url('api/users'),
			'request_payload'		=> json_encode($request_data),
			'response_payload'		=> '',
			'response'				=> '',
			'request_timestamp'		=> new \DateTime(),
		);
		if(isset($request_data['source']) && $request_data['source'] != "" ) {
			if(strtolower($request_data['source']) == 'form') {
				$validator = Validator::make($request->all(), [
					'source'  			=> 'required',
					'name'  			=> 'required',
					'username'  		=> 'required|unique:users',
					'email'     		=> 'required|unique:users|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,4}$/ix',
					'street'  			=> 'required',
					'city'  			=> 'required',
					'password'  		=> 'required|min:8|required_with:confirm_password|same:confirm_password',
					'confirm_password'  => 'required',
				],
				[
					'email.regex' => 'Please enter Only dbase_get_record_with_names(dbase_identifier, record_number)',
					'email.unique' => 'Email already exist!',
				]);
				if ($validator->fails()) {
					$response = array(
						'status'    	=> config('constants.HTTP_BAD_REQUEST'),
						'message'   	=> 'bad required',
						'errors'      	=> $validator->errors()
					);
					$audit_log['response_payload'] 	= json_encode($response);
					$audit_log['response_timestamp'] = new \DateTime();
                    AuditLog::create_audit_log($audit_log);
					return response()->json($response);
				} else {
					try {
						$request_data = $request->all();
						if(strtolower($request_data['source']) == 'form') {
							$input = array(
								'name' 		=> $request_data['name'],
								'username' 	=> $request_data['username'],
								'email' 	=> $request_data['email'],
								'street' 	=> $request_data['street'],
								'city' 		=> $request_data['city'],
								'password' 	=> Hash::make($request_data['password']),
							);
							$credentials = array(
								'email' 	=> $request_data['email'],
								'password' 	=> $request_data['password']
							);
							$user 			= User::create($input);
							$user_id 		= $user->id;
							if ($token   	= auth('api')->attempt($credentials)) {
								$address 	= array(
												'street' => $request_data['street'],
												'city' => $request_data['city']
											);
								$response   = array(
									'status'       => config("constants.HTTP_OK"),
									'message'      => 'User Create Successfully',
									'data'         => array(
										'access_token' 	=> $token,
										'token_type'  	=> 'bearer',
										'expires_in'   	=> auth('api')->factory()->getTTL() * 60,
										'name' 			=> $request_data['name'],
										'username' 		=> $request_data['username'],
										'email' 		=> $request_data['email'],
										'address'		=> $address
									),
								);
								$audit_log['session_id'] = $token;
							} else {
								$response 	= array(
									'status'   => config('constants.HTTP_UNAUTHORIZED'),
									'message'  => 'unauthantication',
									'data'     => []
								);
							}
						} else if (strtolower($request_data['source']) == 'api'){
							$response = array(
								'status'   => config('constants.HTTP_UNAUTHORIZED'),
								'message'  => 'api',
							);
						} else {
							$response =  array(
								'status'    => config('constants.HTTP_BAD_REQUEST'),
								'message'   => 'bad required',
								'errors'    => 'Please provide valid source'
							);
						}
						$audit_log['response_payload'] 		= json_encode($response);
						$audit_log['response_timestamp'] 	= new \DateTime();
						AuditLog::create_audit_log($audit_log);
						return response()->json($response);
					} catch (\Exception $e) {
						return $e->getMessage();
					}
				}
			} else if (strtolower($request_data['source']) == 'api'){
				$validator = Validator::make($request->all(), [
					'id'  				=> 'required'
				]);
				if ($validator->fails()) {
					$response = array(
						'status'    => config('constants.HTTP_BAD_REQUEST'),
						'message'   => 'bad required',
						'errors'      => $validator->errors()
					);
					$audit_log['response_payload'] 		= json_encode($response);
					$audit_log['response_timestamp'] 	= new \DateTime();
					AuditLog::create_audit_log($audit_log);
					return response()->json($response);
				} else {
					$audit_log['response_payload'] 		= json_encode($response);
					$audit_log['response_timestamp'] 	= new \DateTime();
					AuditLog::create_audit_log($audit_log);
					$response = array(
						'status'   => config('constants.HTTP_UNAUTHORIZED'),
						'message'  => 'api',
					);
					return response()->json($response);
				}
			} else {
				$audit_log['response_payload'] 		= json_encode($response);
				$audit_log['response_timestamp'] 	= new \DateTime();
				AuditLog::create_audit_log($audit_log);
				$response = array(
					'status'    => config('constants.HTTP_BAD_REQUEST'),
					'message'   => 'bad required',
					'errors'    => 'Please provide valid source'
				);
				return response()->json($response);
			}
		} else {
			$audit_log['response_payload'] 		= json_encode($response);
			$audit_log['response_timestamp'] 	= new \DateTime();
			AuditLog::create_audit_log($audit_log);
			$response = array(
				'status'    => config('constants.HTTP_BAD_REQUEST'),
				'message'   => 'bad required',
				'errors'    => 'Please provide valid source'
			);
			return response()->json($response);
		}
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\user  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $request_data 		= $request->all();
		$audit_log			= array(
			'session_id'			=> '',
			'source_address'		=> url('api/users/' . $id),
			'request_payload'		=> json_encode($request_data),
			'response_payload'		=> '',
			'response'				=> '',
			'request_timestamp'		=> new \DateTime(),
		);
		if(isset($request_data['source']) && $request_data['source'] != "" ) {
			if(strtolower($request_data['source']) == 'form') {
				$validator = Validator::make($request->all(), [
					'source'  			=> 'required',
					'name'  			=> 'required',
					'street'  			=> 'required',
					'username'  		=> 'required|unique:users,username,'.$id,
					'email'     		=> 'required|unique:users,email,'.$id.'|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,4}$/ix',
					'city'  			=> 'required',
					'password'  		=> 'required|min:8|required_with:confirm_password|same:confirm_password',
					'confirm_password'  => 'required',
				],
				[
					'email.regex' => 'Please enter Only dbase_get_record_with_names(dbase_identifier, record_number)',
					'email.unique' => 'Email already exist!',
				]);
				if ($validator->fails()) {
					$response = array(
						'status'    	=> config('constants.HTTP_BAD_REQUEST'),
						'message'   	=> 'bad required',
						'errors'      	=> $validator->errors()
					);
					$audit_log['response_payload'] 	= json_encode($response);
					$audit_log['response_timestamp'] = new \DateTime();
                    AuditLog::create_audit_log($audit_log);
					return response()->json($response);
				} else {
					try {
						$request_data = $request->all();
						if(strtolower($request_data['source']) == 'form') {
							$input = array(
								'name' 		=> $request_data['name'],
								'username' 	=> $request_data['username'],
								'email' 	=> $request_data['email'],
								'street' 	=> $request_data['street'],
								'city' 		=> $request_data['city'],
								'password' 	=> Hash::make($request_data['password']),
							);
                            $user       = User::where('id',$id)->update($input);
							$user_id 	= $id;
                            $address 	= array(
                                            'street' => $request_data['street'],
                                            'city' => $request_data['city']
                                        );
                            $response   = array(
                                'status'       => config("constants.HTTP_OK"),
                                'message'      => 'User Update successfully',
                                'data'         => array(
                                    'name' 			=> $request_data['name'],
                                    'username' 		=> $request_data['username'],
                                    'email' 		=> $request_data['email'],
                                    'address'		=> $address
                                ),
                            );
						} else if (strtolower($request_data['source']) == 'api'){
							$response = array(
								'status'   => config('constants.HTTP_UNAUTHORIZED'),
								'message'  => 'api',
							);
						} else {
							$response =  array(
								'status'    => config('constants.HTTP_BAD_REQUEST'),
								'message'   => 'bad required',
								'errors'    => 'Please provide valid source'
							);
						}
						$audit_log['response_payload'] 		= json_encode($response);
						$audit_log['response_timestamp'] 	= new \DateTime();
						AuditLog::create_audit_log($audit_log);
						return response()->json($response);
					} catch (\Exception $e) {
						return $e->getMessage();
					}
				}
			} else if (strtolower($request_data['source']) == 'api'){
				$validator = Validator::make($request->all(), [
					'id'  				=> 'required'
				]);
				if ($validator->fails()) {
					$response = array(
						'status'    => config('constants.HTTP_BAD_REQUEST'),
						'message'   => 'bad required',
						'errors'      => $validator->errors()
					);
					$audit_log['response_payload'] 		= json_encode($response);
					$audit_log['response_timestamp'] 	= new \DateTime();
					AuditLog::create_audit_log($audit_log);
					return response()->json($response);
				} else {
					$audit_log['response_payload'] 		= json_encode($response);
					$audit_log['response_timestamp'] 	= new \DateTime();
					AuditLog::create_audit_log($audit_log);
					$response = array(
						'status'   => config('constants.HTTP_UNAUTHORIZED'),
						'message'  => 'api',
					);
					return response()->json($response);
				}
			} else {
				$audit_log['response_payload'] 		= json_encode($response);
				$audit_log['response_timestamp'] 	= new \DateTime();
				AuditLog::create_audit_log($audit_log);
				$response = array(
					'status'    => config('constants.HTTP_BAD_REQUEST'),
					'message'   => 'bad required',
					'errors'    => 'Please provide valid source'
				);
				return response()->json($response);
			}
		} else {
			$audit_log['response_payload'] 		= json_encode($response);
			$audit_log['response_timestamp'] 	= new \DateTime();
			AuditLog::create_audit_log($audit_log);
			$response = array(
				'status'    => config('constants.HTTP_BAD_REQUEST'),
				'message'   => 'bad required',
				'errors'    => 'Please provide valid source'
			);
			return response()->json($response);
		}
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $audit_log			= array(
                'session_id'			=> '',
                'source_address'		=> url('api/users/' . $id),
                'request_payload'		=> '',
                'response_payload'		=> '',
                'response'				=> '',
                'request_timestamp'		=> new \DateTime(),
            );
            $user = new User;        // Totally useless line
            $user = User::find($id); // Can chain this line with the next one
			if($user !== null){
				$user->delete($id);
                $response   = array(
                    'status'    => config('constants.HTTP_OK'),
                    'message'   => 'Success',
                    'data'      => 'Successfully Delete user'
                );
                $audit_log['response_payload'] 		= json_encode($response);
                $audit_log['response_timestamp'] 	= new \DateTime();
                AuditLog::create_audit_log($audit_log);
				return response()->json($response);
			} else {
                $response = array(
                    'status'    => config('constants.HTTP_BAD_REQUEST'),
                    'message'   => 'bad required',
                    'data'      => 'Please provide valid source'
                );
                $audit_log['response_payload'] 		= json_encode($response);
                $audit_log['response_timestamp'] 	= new \DateTime();
                AuditLog::create_audit_log($audit_log);
				return response()->json($response);
			}
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
