<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Collective\Html\Eloquent\FormAccessible;
use DB;
use auth;
use Embed\Embed;
use App\Link;
use Redirect;
use App\User;
use App\Tag;
use App\TagRelation;
use App\Community;
use App\CommunityLink;
use Hash;
use Validator;
use Input;
use Illuminate\Contracts\Auth\PasswordBroker;


class UserController extends Controller
{
    public function ajaxLogin(Request $request)
    {
        $credentials = array_trim($request->only('email', 'password'));
        $rules = ['email' => 'required|email|max:255',
            'password' => 'required'
        ];

        $validation = Validator::make($credentials, $rules);
        $errors = $validation->errors();
        $errors = json_decode($errors);
        if ($validation->passes()) {
            if (Auth::attempt(['email' => trim($request->email),
                        'password' => $request->password,
                            ], $request->has('remember'))) {


                return response()->json(['redirect' => true, 'success' => true], 200);
            } else {
                $message = 'Invalid username or password';

                return response()->json(['password' => $message], 422);
            }
        } else {
            return response()->json($errors, 422);
        }
    }

    public function ajaxForgotPassword(Request $request, PasswordBroker $passwords)
    {
        if( $request->ajax() )
        {
            $this->validate($request, ['email' => 'required|email']);

            $response = $passwords->sendResetLink($request->only('email'));

            switch ($response)
            {
                case PasswordBroker::RESET_LINK_SENT:
                   return[
                       'error'=>'false',
                       'msg'=>'A password link has been sent to your email address'
                       
                   ];

                case PasswordBroker::INVALID_USER:
                   return response()->json([
                       'error'=>'true',
                       'msg'=>"We can't find a user with that email address",
                   ], 422);
            }
        }
        return false;
    }

    public function ajaxRegister(Request $request)
    {
        $userData = array(
          'name'      => $request->input("username"),
          'email'     =>  $request->input("email"),
          'password'  =>  $request->input("password"),
          'password_confirmation' =>  $request->input("password_confirmation"),
        );
        $rules = array(
            'name'      =>  'required',
            'email'     =>  'required|email|unique:users',
            'password'  =>  'required|min:6|confirmed',
        );
        $validator = Validator::make($userData,$rules);
        if($validator->fails())
            return response()->json(array(
                'fail' => true,
                'errors' => $validator->getMessageBag()->toArray()
            ), 422);
        else {
        //save password to show to user after registration
            $password = $userData['password'];
        //hash it now
            $userData['password'] =    Hash::make($userData['password']);
            unset($userData['password_confirmation']);
        //save to DB user details
          if(User::create($userData)) {

            Auth::attempt(['email' => trim($request->email),'password' => $request->password], $request->has('remember'));

              //return success  message
            return response()->json(array(
              'success' => true,
              'email' => $userData['email'],
              'password'    =>  $password
            ));
          }
        }
    }

    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return Response
     */
    public function showProfile()
    {
        return view('user.profile');
    }
    
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return Response
     */
    public function showSettings($id)
    {
        return view('user.profile', ['user' => User::findOrFail($id)]);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('settings.index');
    }

    /**
     * API / Post processing for email setting.
     *
     * @return \Illuminate\Http\Response
     */
    public function postEmail(Request $request)
    {
        $user = Auth::User();
        if (!filter_var($request->input("email"), FILTER_VALIDATE_EMAIL) === true) return response()->json(["errors"=>["Invalid email."]]);
        $emailTaken = User::where("email", "=", $request->input("email"))->count();
        if($emailTaken && $user->email != $request->input("email")) return response()->json(["errors"=>["Another user already used that email."]]);

        $user->email = $request->input("email");
        $user->save();

        return response()->json(["success"=>["Success."]]);
    }

    /**
     * API / Post processing for new password setting.
     *
     * @return \Illuminate\Http\Response
     */
    public function postPassword(Request $request)
    {
      $user = Auth::user();

      if(Hash::check($request->input("current_password"), $user->password)) {           
        $user->password = Hash::make($request->input("new_password"));
        $user->save(); 
        return response()->json(["success"=>["Success."]]);
      }
      else {           
        return response()->json(["errors"=>["Please enter correct current password."]]);
      }
      
    }
}

function array_trim($array) {
    while (!empty($array) and strlen(reset($array)) === 0) {
        array_shift($array);
    }
    while (!empty($array) and strlen(end($array)) === 0) {
        array_pop($array);
    }
    return $array;
}