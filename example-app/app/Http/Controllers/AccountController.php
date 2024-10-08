<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Job;
use App\Models\JobType;
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

        public function createJob() {

            $categories = Category::orderBy('name','ASC')->where('status',1)->get();
            $jobTypes = JobType::orderBy('name','ASC')->where('status',1)->get();

                return view('font.account.job.create',[
                    'categories' =>  $categories,
                    'jobTypes' =>  $jobTypes,
                ]);
}

public function saveJob(Request $request) {
    $rules = [
        'title' => 'required',
        'category' => 'required',
        'jobType' => 'required',
        'vacancy' => 'required',
        'location' => 'required',
        'description' => 'required',
        'company_name' => 'required',
    ];
    $validator = Validator::make($request->all(),$rules);

    if ($validator->passes()) {

        $job = new Job();
        $job->title = $request->title;
        $job->category_id = $request->category;
        $job->job_type_id = $request->jobType;
        $job->vacancy = $request->vacancy;
        $job->salary = $request->salary;
        $job->location = $request->location;
        $job->description = $request->description;
        $job->benefits = $request->benefits;
        $job->responsibility = $request->responsibility;
        $job->qualifications = $request->qualifications;
        $job->keywords = $request->keywords;
        $job->experience = $request->experience;
        $job->company_name = $request->company_name;
        $job->company_location = $request->company_location;
        $job->company_website = $request->website;
        $job->created_at = $request->created_at;
        $job->updated_at = $request->updated_at;
        $job->save();


        session()->flash('success','Job Added Successfully');

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

public function myJobs() {
    return view('font.account.job.my-jobs');

}

}
