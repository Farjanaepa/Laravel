<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AccountController extends Controller
{
    // This method will show user registration page

    public function registration() {

        return view('font.account.registration');

    }

    // This method will save a user

    public function processRegistration(Request $request) {
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:5|same:cnfrm_password',
            'cnfrm_password' => 'required',
        ]);
        
        if ($validator->passes()) {

            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            // $user ->cnfrm_password = $request->cnfrm_password;
            
            $user ->save();

            session()->flash('success', 'You have registered successfully.');


            return response()->json([
                'status' => true,
                'errors' =>[]
            ]); 
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
        

    }

    // This method will show user login page

    public function login() {
        return view('font.account.login');
    }

    public function authenticate(Request $request) {

        $validator = Validator::make($request->all(),[
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->passes()) {

            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return redirect()->route('account.profile');
            } else {
                return redirect()->route('account.login')->with('error','Either Email/Password is incorrect');
            }
            
           
        } else {
           return redirect()->route('account.login')
           ->withErrors($validator)
           ->withInput($request->only('email'));
        }
        
    }

    public function profile(){

       


        $id = Auth::user()->id;

        $user = User::where('id',$id)->first();

       

       return view('font.account.profile',[
        'user' => $user
       ]);
    }



    public function updateProfile(Request $request){
        $id = Auth::user()->id;

        $validator = Validator::make($request->all(),[
            'name' => 'required|min:5|max:20',
            'email' => 'required|email|unique:users,email,'.$id.',id'
        ]);
        if ($validator->passes()) {

            $user = User::find($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->designation = $request->designation;
            $user->save();

            session()->flash('success','profile update succcessfully.');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);

            
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    
    public function logout(){
       Auth::logout();
        return redirect()->route('account.login');
     }


     public function updateProfilePic(Request $request) {

        $id = Auth::user()->id;

        $validator = Validator::make($request->all(),[
            'image' => 'required|image'
        ]);

        if ($validator->passes()) {
            
            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = $id.'-'.time().'-'.$ext;
            $image->move(public_path('/profile_pic/'), $imageName);

            //create a small thumbnail
                    $sourcePath = public_path('/profile_pic/'.$imageName);
                    $manager = new ImageManager(Driver::class);
                    $image = $manager->read($sourcePath);

                    // crop the best fitting 5:3 (600x360) ratio and resize to 600x360 pixel
                     $image->cover(150, 150);
                     $image->toPng()->save(public_path('/profile_pic/thumb/'.$imageName));

                    //Delete old profile pic
                    File::delete(public_path('/profile_pic/thumb/'.Auth::user()->image));
                    File::delete(public_path('/profile_pic/'.Auth::user()->image));

            User::where('id',$id)->update(['image' => $imageName]);

            session()->flash('success','Profile pic Update Successfully.');

            return response()->json([
                'status' => true,
                'errors' =>[]
            ]);


        }else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

     }
}
