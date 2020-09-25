<?php


namespace App\Http\Controllers;


use App\Events\UserManipulated;
use App\Models\User;
use App\Models\UserManipulationsLog;
use App\Transformers\UserManipulationsLogTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use League\Fractal;
use League\Fractal\Manager;

class UserController extends Controller
{
    /**
     * @var Manager
     */
    private $fractal;

    /**
     * @var UserManipulationsLogTransformer
     */
    private $userManipulationsLogTransformer;

    function __construct(Manager $fractal, UserManipulationsLogTransformer $userManipulationsLogTransformer)
    {
        $this->fractal = $fractal;
        $this->userManipulationsLogTransformer = $userManipulationsLogTransformer;
    }

    /*
     * register function/API
     * Creates User Entity
     * Logs the the transaction to user_manipulations_logs Table
     * Accepts the following User Info within a Request Object to Create User Entity:
     * 1- Name (required)
     * 2- email (required)
     * 3- password (required)
     * 4- password confirmation (required)
     * Returns
     * 1- User Object with 201 Status Code (Created)
     * 2- Error message with 400 Status Code (Bad Request) in case of any errors.
     * */
    public function register(Request $request)
    {
        try {
            //Validate Input Parameters
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email|unique:users',
                'password' => 'required',
                'password_confirmation' => 'required|same:password',
            ]);
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], (int) config('status_code.badRequest'));
            }

            //Prepare Input Parameters for Entry Creation
            $input = $request->all();
            //Override plain password with hashed password
            $input['password'] = Hash::make($input['password']);

            //Create User Entry
            $user = User::create($input);

            //log User Entry Creation transaction
            event(new UserManipulated($user, [],'create'));

            //Return created user entry and success code
            return response()->json(['user' => $user], (int) config('status_code.created'));
        }catch (\Exception $ex){
            //Generic Error Handler
            return response()->json(['message' => $ex->getMessage()], (int) config('status_code.badRequest'));
        }
    }

    /*
     * update function/API
     * Updates a User's Entity (Current logged in User - or Other User (if used id is provided))
     * Logs the the transaction to user_manipulations_logs Table
     * Accepts the following User Info within a Request Object to Create User Entity:
     * 1- User ID (optional if other user to be updated)
     * 2- Name (required)
     * 3- email (required)
     * Returns
     * 1- User Object with 200 Status Code (Ok)
     * 2- Error message with 404 Status Code (Not Found) if user not found
     * 3- Error message with 400 Status Code (Bad Request) in case of any other type of errors.
     * */
    public function update(Request $request)
    {
        try {
            //Validate Input Parameters
            $validator = Validator::make($request->all(), [
                'id'=> 'sometimes|integer',
                'name' => 'required',
                'email' => 'required|email',
                //'is_active'=> 'sometimes|boolean',
            ]);
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], (int) config('status_code.badRequest'));
            }

            //Validate and Fetch User Entity to update
            if (!is_null($request->id)) {
                $user = User::where('id',$request->id)->first();
                if (is_null($user))
                    return response()->json(['message' => 'user not found'], (int) config('status_code.notFound'));
            }else{
                if (!Auth::check())
                    return response()->json(['message' => 'user not found'], (int) config('status_code.notFound'));

                $user=Auth::user();
            }

            //Preserve Original Entity Values for logging purpose
            $originalUserDate = $user->getOriginal();

            //Set new Values
            $user->name=$request->name;
            $user->email=$request->email;

            //Save new values
            $user->save();

            //log User modification transaction
            event(new UserManipulated($user, $originalUserDate, 'update'));

            //Return updated user entry and success code
            return response()->json(['user' => $user], (int) config('status_code.success'));
        }catch (\Exception $ex){
            //Generic Error Handler
            return response()->json(['message' => $ex->getMessage()], (int) config('status_code.badRequest'));
        }
    }

    /*
     * destroy function/API
     * Deletes a User's Entity (Current logged in User - or Other User (if used id is provided))
     * Logs the the transaction to user_manipulations_logs Table
     * Accepts the following User Info within a Request Object to Create User Entity:
     * 1- User ID (optional if other user to be updated)
     * Returns
     * 1- Success Message with 200 Status Code (Ok)
     * 2- Error message with 404 Status Code (Not Found) if user is not found
     * 3- Error message with 400 Status Code (Bad Request) in case of any other type of errors.
     * */
    public function destroy ($id=null){
        try{
            //Validate and Fetch User Entity to delete
            if(!is_null($id)){
                $user = User::where('id',$id)->first();
                if (is_null($user))
                    return response()->json(['message' => 'user not found'], (int) config('status_code.notFound'));
            }elseif (Auth::check()) {
                $user = Auth::user();
                //Log out user before deleting entry
                Auth::user()->AauthAcessToken()->delete();
            }

            //Delete User
            $user->delete();

            //log User deletion transaction
            event(new UserManipulated($user,[], 'delete'));

            //Return Success message and code
            return response()->json(['message' => "user is deleted"], (int) config('status_code.success'));

        }catch (\Exception $ex){
            //Generic Error Handler
            return response()->json(['message' => $ex->getMessage()], (int) config('status_code.badRequest'));
        }
    }

    /*
     * viewUserManipulationLog function/API
     * Views and filters User's Table data manipulations actions log entries
     * Accepts the following optional parameters to filter log records accordingly.
     * 1- Action (optional)
     * 2- User Entity ID (optional)
     * 3- User Performing Action
     * 4- Actions' Date
     * Returns
     * 1- Success Message with 200 Status Code (Ok)
     * 2- Error message with 404 Status Code (Not Found) if no logs found to match criteria
     * 3- Error message with 400 Status Code (Bad Request) in case of any other type of errors.
     * */
    public function viewUserManipulationLog(Request $request){
        try{
            //Build the Query
            $queryCriteria=UserManipulationsLog::query();

            //Add the Action Filter
            if ($request->has('action')) {
                $queryCriteria->where('action', $request->input('action'));
            }

            //Add the Entity ID Filter
            if ($request->has('entity_id')) {
                $queryCriteria->where('entity_id', $request->input('entity_id'));
            }

            //Add the Performing User Filter
            if ($request->has('by_user')) {
                $queryCriteria->where('by_user', $request->input('by_user'));
            }

            //Add the Date Filter
            if ($request->has('created_at')) {
                $queryCriteria->whereDate('created_at', Carbon::parse($request->input('created_at')));
            }

            //Fetch Data
            $results= $queryCriteria->get();

            //Confirm Existing of Results
            if (count($results)===0) {
                return response()->json(['message' => 'no logs found'], (int) config('status_code.notFound'));
            }

            //Format and Return Response
            $results = new Fractal\Resource\Collection($results, $this->userManipulationsLogTransformer);
            $results = $this->fractal->createData($results)->toArray();

            return response()->json(['logs' => $results['data']], 200);
        }catch (\Exception $ex){
            //Generic Error Handler
            return response()->json(['message' => $ex->getMessage()], (int) config('status_code.badRequest'));
        }
    }

    /*
     * logout function/API
     * logs out user
     * No input parameters (it logs out currently logged in user)
     * Returns
     * 1- Success Message with 200 Status Code (Ok)
     * 2- Error message with 400 Status Code (Bad Request) in case of errors.
     * */
    public function logout()
    {
        try{
            if (Auth::check()) {
                Auth::user()->AauthAcessToken()->delete();
                return response()->json(['message' => "user logged out"], (int) config('status_code.success'));
            }
            return response()->json(['message' => 'no logged on user'], (int) config('status_code.unauthorized'));
        }catch (\Exception $ex){
            //Generic Error Handler
            return response()->json(['message' => $ex->getMessage()], (int) config('status_code.badRequest'));
        }
    }

    /*
     * show function/userInfo API
     * Returns Current logged in User's Information
     * No input parameters
     * Returns
     * 1- User Object with 200 Status Code (Ok)
     * 2- Error message with 400 Status Code (Bad Request) in case of any other type of errors.
     * */
    public function show()
    {
        try {
            //get and return current logged in user info
            return response()->json(['user' => Auth::user()], (int) config('status_code.success'));

        }catch (\Exception $ex){
            //Generic Error Handler
            return response()->json(['message' => $ex->getMessage()], (int) config('status_code.badRequest'));
        }
    }
}
