<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\User;
use App\AuditLog;
use App\LoginLogs;
use Illuminate\Support\Facades\Hash;

class UserAuthApiController extends Controller
{
    /**
     * Get a JWT token via given credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request) {
		$request_data 		= $request->all();
		$audit_log			= array(
			'session_id'			=> '',
			'source_address'		=> url('api/login'),
			'request_payload'		=> json_encode($request_data),
			'response_payload'		=> '',
			'response'				=> '',
			'request_timestamp'		=> new \DateTime(),
		);
		$validator 	= Validator::make($request->all(), [
			'email'     => 'required|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,4}$/ix',
			'password'  => 'required'
		]);
	   	if ($validator->fails()) {
            $response = array(
				'status'    	=> config('constants.HTTP_BAD_REQUEST'),
				'message'   	=> 'bad required',
				'errors'      	=> $validator->errors()
			);
			$audit_log['response_payload'] 	= json_encode($response);
			$audit_log['response_timestamp'] = new \DateTime();
			AuditLog::create($audit_log);
			return response()->json($response);
        } else {
	        try {
				$credentials = array(
					'email' 	=> $request_data['email'],
					'password' 	=> $request_data['password']
				);
	            if ($token   = auth('api')->attempt($credentials)) {
	                $response = array(
						'status'       => config("constants.HTTP_OK"),
						'message'      => 'seccessfully loggin',
						'data'         => array(
							'access_token' => $token,
							'token_type'   => 'bearer',
							'expires_in'   => auth('api')->factory()->getTTL() * 60
						),
					);
					// Audit Log
					$audit_log['session_id'] 			= $token;
					$audit_log['response_payload'] 		= json_encode($response);
					$audit_log['response_timestamp'] 	= new \DateTime();
					AuditLog::create($audit_log);

					// Login Log
					$id = User::where('email',$request_data['email'])->pluck('id')->first();
					$login_log			= array(
						'user_id'				=> $id,
						'session_id'			=> $token,
						'session_origin'		=> '',
						'response'				=> json_encode($response),
					);
					LoginLogs::createLoginLog($login_log);
					return response()->json($response);
	            } else {
	                $response = array(
						'status'   => config('constants.HTTP_UNAUTHORIZED'),
						'message'  => 'unauthantication',
					);
					$audit_log['response_payload'] 	= json_encode($response);
					$audit_log['response_timestamp'] = new \DateTime();
					AuditLog::create($audit_log);
					return response()->json($response);
	            }
	        } catch (\Exception $e) {
	            return $e->getMessage();
	        }
        }
    }

}
