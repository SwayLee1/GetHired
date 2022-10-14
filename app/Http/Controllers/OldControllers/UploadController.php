<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Beneficiary;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Country;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Disability;
use App\Models\Email;
use App\Models\Employee;
use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Validation\ValidationException;
use App\Models\Gender;
use App\Models\sys_email;
use App\Models\Identity_type;
use App\Models\Language;
use App\Models\Marital_status;
use App\Models\Nationality;
use App\Models\Next_of_kin;
use App\Models\Offboard;
use App\Models\Postal_address;
use App\Models\Province;
use App\Models\Qualification;
use App\Models\Qualification_type;
use App\Models\Relationship;
use App\Models\Residential_address;
use App\Models\Sub_department;
use App\Models\Title;
use App\Models\Stage_candidate;
use App\Models\resignation_letter;
use App\Mail\cls_OffBoard;
use Carbon\Carbon;
//use PHPUnit\Framework\MockObject\Builder\Identity;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
//use Illuminate\Support\Facades\Validator;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\WithValidation;
use Validator;
use Mail;





class UploadController extends Controller
{
    public function employeeImport()
    {

        $collection = (new FastExcel)->importSheets('./employee_uploads/'.''.Session::get("token.create_request"));


        $hold  = strval($collection);
        $col1  = "Department";
        $col2  = "SubDepartment";
        $col3  = "ID type";
        $col4  = "ID_Number";
        $col5  = "First_Name";
        $col6  = "Personal Email Address";
        $col7  = "Designation";
        $col8  = "Ethnicity";
        $col9  = "Gender";
        $col10 = "Contact No 1";
        $col11 = "Contact No 2";
        $col12 = "Recruiter";
        $col13 = "StartDate";


        if(strpos( $hold, $col1)   !==false && 
            strpos( $hold, $col2)  !==false &&
            strpos( $hold, $col3)  !==false &&
            strpos( $hold, $col4)  !==false &&
            strpos( $hold, $col5)  !==false &&
            strpos( $hold, $col6)  !==false &&
            strpos( $hold, $col7)  !==false &&
            strpos( $hold, $col8)  !==false &&
            strpos( $hold, $col9)  !==false &&
            strpos( $hold, $col10) !==false &&
            strpos( $hold, $col11) !==false &&
            strpos( $hold, $col12) !==false &&
            strpos( $hold, $col3)  !==false
         )
      {
        return view ('admin.employees.importpreview',compact('collection'));
      }
      else
        {
            return view ('admin.employees.importpreviewError');      
        }
     
    }

public function employeeSubmit(Request $request)    
    {

        $request->validate([
            'data.*.Department'  => 'required',  
            'data.*.ID_Number'   => 'required',  
            'data.*.First_Name'  => 'required',  
            'data.*.Last_Name'   => 'required',  
            'data.*.Personal Email Address'  => 'required',  
            'data.*.Designation'    => 'required',  
            'data.*.Ethnicity'      => 'required',  
            'data.*.Gender'         => 'required',  
            'data.*.Contact No 1'   => 'required', 
            'data.*.SubDepartment'  => 'required',
            'data.*.ID type'        => 'required',
            'data.*.StartDate'      => 'required',
        ]);

        
        $count_checker = count($request->data)-1;
        $stat = 0;
        $sendmail = 0;
        $user_session = auth()->user()->email;
    
                for ($i=0; $i < count($request->data); $i++)
                {
                     if($user_session=='recruitment@capabilitybpo.com'||
                        $user_session=='it@capabilitybpo.com'         ||
                        $user_session=='wfm@capabilitybpo.com'        ||
                        $user_session=='facilities@capabilitybpo.com' ||
                        $user_session=='training@capabilitybpo.com'   ||
                        $user_session=='hr@capabilitybpo.com')
                     {
                        $sendmail=1; 
                     }
                                if($count_checker==$i)
                                {
                                    $stat=1;
                                }
                
                                $data =  $request->data[$i];
                            
                                if($data['SubDepartment']=="Groupon French" || $data['SubDepartment']=="Instacart French")
                                   {
                                    $sendmail=1;
                                   }


                               $datetouse =  substr($data['StartDate'],0,10);

                               
                                $random = Str::random(40); // random string
                                $stage_candidates = Stage_candidate::updateOrCreate([
                                    'department' =>$data['Department'],
                                    'id_passport_number' =>$data['ID_Number'],
                                    'first_name' => $data['First_Name'],
                                    'last_Name' => $data['Last_Name'],
                                    'personal_email_address' =>$data['Personal Email Address'],
                                    'designation' =>$data['Designation'],
                                    'ethnicity' =>$data['Ethnicity'],
                                    'gender' =>$data['Gender'],
                                    'contact1' =>$data['Contact No 1'],
                                    'contact2' =>$data['Contact No 2'],
                                    'recruiter' =>$data['Recruiter'],
                                    'created_at' => date("Y-m-d H:i:s",strtotime('2 hour')),
                                    'updated_at' => date("Y-m-d H:i:s",strtotime('2 hour')),
                                    'subdepartment' => $data['SubDepartment'],
                                    'id_type' => $data['ID type'],
                                    'sendemail' =>1,
                                    'is_Active' =>1,
                                    'candidate_status_id' =>7,
                                    'startdate' => $datetouse,
                                    'hashkey'  =>  $random,

                                ]);
                }

               // START delete duplicates after creation
                DB::statement("WITH CTE AS(SELECT *,RN=ROW_NUMBER() OVER(PARTITION BY id_passport_number,id_passport_number ORDER BY id_passport_number) FROM stage_candidates) DELETE FROM CTE WHERE RN>1");
               // END delete duplicates after creatION

               // set notifications
               DB::statement("update notifications set user_read=0 where notification_id between 1 and 5");
               //end

                if($stat==1)
                {
                    return response()->json(['status' => $stat]);
                }
                if($stat==0)
                {
                    return response()->json(['status' => $stat]);
                }  
    }

    public function uploadImport(Request $request)
    {
        $file_other = $request->file;

        $agreement_file = $file_other;
   
        $agreement_code  = Str::random(45);
        $names = $agreement_code.'-employees.'.$agreement_file->getClientOriginalExtension();

            Session::put("token.create_request", $names );

   
        $destinationPath = './employee_uploads';
        $agreement_file->move($destinationPath, $names);
    }
    public function uploadresignation(Request $request)
    {


        $user_email_address = DB::select(DB::raw("SELECT email FROM resignation_letters ORDER BY ID DESC OFFSET 0 ROWS FETCH FIRST 1 ROW ONLY"));
        $user               = DB::select(DB::raw("SELECT fullname FROM resignation_letters ORDER BY ID DESC OFFSET 0 ROWS FETCH FIRST 1 ROW ONLY"));
        $date               = DB::select(DB::raw("SELECT resignationdate FROM resignation_letters ORDER BY ID DESC OFFSET 0 ROWS FETCH FIRST 1 ROW ONLY"));
        $e_id               = DB::select(DB::raw("SELECT employee_id FROM resignation_letters ORDER BY ID DESC OFFSET 0 ROWS FETCH FIRST 1 ROW ONLY"));

       $var_email          = $user_email_address[0]->email;
       $var_userfullname   = $user[0]->fullname;
       $var_date           = $date[0]->resignationdate;
       $sessionemail       = auth()->user()->email;
       $empid              = $e_id[0]->employee_id;
    
       $email_string =  $var_userfullname . ', '. $var_email;
       $reason = 'Resigned';
    //    
        $file_other = $request->file;
        $agreement_file = $file_other; 
        $agreement_code  =  $var_email;
        $names = $agreement_code.'-Resignation.'.$agreement_file->getClientOriginalExtension();
        Session::put("token.create_request", $names );
        $destinationPath = './resignation_letters';
        $agreement_file->move($destinationPath, $names);

        DB::statement("update notifications set notification=' ',user_read=0 where notification_id=8 and type_=2");             
        // end     
        DB::table('notifications')->where('notification_id',8)->update([
            'notification'                        => $var_email,
            'updated_at'                         =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
        ]);

          // Project Owner Check
          $owner_check = \App\Models\ProjectOwner::where('owner_id', $empid)->count() > 0;

        Mail::to("hr@capabilitybpo.com")->send(new cls_OffBoard($email_string,$var_date,$reason, $owner_check));
    }
  
    
}



