<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Address;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Disability;
use App\Models\Next_of_kin;
use App\Models\Postal_address;
use App\Models\resignation_letter;
use App\Models\User;
use App\Models\Relationship;
use App\Models\Qualification_type;
use App\Models\Beneficiary;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use App\Models\Email;
use App\Models\Offboard;
use App\Models\Residential_address;
use App\Models\Sub_department;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Concerns\FilterEmailValidation;
use App\Rules\PhoneNumber;
use App\Mail\cls_OffBoard;
use App\Mail\cls_ConfirmOffBoard;
use App\Mail\NewEmployee;
use Mail;


class EmployeesController extends Controller
{
    // public function employees()
    // {

    //     $employees = Employee::all();
    //    // return view('admin.employees.dashboard', compact($employees));
    //     return view('admin.employees.employeedashboard', compact($employees));    
    // }

    public function addpersonal(Request $request){
        $employee = $request->session()->get('employee');
         return view('admin.employees.addemployee', compact($employee));
     }





     public function verify_id_number($id_number, $gender = '', $foreigner = 0) {

        $validated = false;
    
    
        if (is_numeric($id_number) && strlen($id_number) === 13) {
    
            $errors = false;
    
            $num_array = str_split($id_number);
    
    
            // Validate the day and month
    
            $id_month = $num_array[2] . $num_array[3];
    
            $id_day = $num_array[4] . $num_array[5];
    
    
            if ( $id_month < 1 || $id_month > 12) {
                $errors = true;
            }
    
            if ( $id_day < 1 || $id_day > 31) {
                $errors = true;
            }
            
    
            // Validate gender
    
            $id_gender = $num_array[6] >= 5 ? 'male' : 'female';
    
            if ($gender && strtolower($gender) !== $id_gender) {
    
                $errors = true;
    
            }
    
            // Validate citizenship
    
            // citizenship as per id number
            $id_foreigner = $num_array[10];
    
            // citizenship as per submission
            if ( ( $foreigner || $id_foreigner ) && (int)$foreigner !== (int)$id_foreigner ) {
    
                 $errors = true;
            }
    
            /**********************************
                Check Digit Verification
            **********************************/
    
            // Declare the arrays
            $even_digits = array();
            $odd_digits = array();
    
            // Loop through modified $num_array, storing the keys and their values in the above arrays
            foreach ( $num_array as $index => $digit) {
    
                if ($index === 0 || $index % 2 === 0) {
    
                    $odd_digits[] = $digit;
                   
                }
    
                else {
    
                    $even_digits[] = $digit;
    
                }
    
            }
    
            // use array pop to remove the last digit from $odd_digits and store it in $check_digit
            $check_digit = array_pop($odd_digits);
    
            //All digits in odd positions (excluding the check digit) must be added together.
            $added_odds = array_sum($odd_digits);
    
            //All digits in even positions must be concatenated to form a 6 digit number.
            $concatenated_evens = implode('', $even_digits);
    
            //This 6 digit number must then be multiplied by 2.
            $evensx2 = $concatenated_evens * 2;
    
            // Add all the numbers produced from the even numbers x 2
            $added_evens = array_sum( str_split($evensx2) );
    
            $sum = $added_odds + $added_evens;
    
            // get the last digit of the $sum
            $last_digit = substr($sum, -1);
    
            /* 10 - $last_digit
             * $verify_check_digit = 10 - (int)$last_digit; (Will break if $last_digit = 0)
             * Edit suggested by Ruan Luies
             * verify check digit is the resulting remainder of
             *  10 minus the last digit divided by 10
             */
            $last_digit = intval($last_digit);
             $verify_check_digit = (10 - $last_digit) % 10 ;
             //$num = intval(last_digit);
    
            // test expected last digit against the last digit in $id_number submitted
            if ((int)$verify_check_digit !== (int)$check_digit) {
                $errors = true;
            }
    
            // if errors haven't been set to true by any one of the checks, we can change verified to true;
            if (!$errors) {
                $validated = true;
            }
    
        }
    
    
        return $validated;
    
    
    }


    public function get_Departments(){
        $data = Department::all();
        return response()->json(['dep' => $data]);
     }

     public function get_Sub_Departments(Request $request){
        $data = Sub_department::where('department_id', $request->department_id)->get();
        $designation_data = Designation::where('department_id', $request->department_id)->get();
        return response()->json(['sub_departments' => $data, 'designations' => $designation_data]);
     }


     public function GetSubDepartments(Request $request,$depid){
        $data = Sub_department::where('department_id',$depid)->get();
        $designation_data = Designation::where('department_id',$depid)->get();
        return response()->json(['sub_departments' => $data, 'designations' => $designation_data]);
     }

     

     public function get_designations(Request $request){
        $designation_data = Designation::where('department_id', $request->department_id)->get();
        return response()->json($designation_data);
     }

     public function validateStep(Request $request){
        // return response()->json($request->all());
 
         
         if($request->step == 'one'){
 
             $request->validate([
                 'title_id' => 'required',
                 'initials' => 'required|regex:/^[a-zA-Z]+$/u',
                 'first_name' => 'required|regex:/^[a-zA-Z]+$/u',
                 'employee_number' => 'required',
                 'disability_id' => 'required',
         
                 'last_name' => 'required|regex:/^[a-zA-Z]+$/u',
                 'personal_email'=> 'required|email|regex:/(.+)@(.+)\.(.+)/i',
                 //'work_email'=> 'required|email|regex:/(.+)@(.+)\.(.+)/i',  //|ends_with:capabilitybpo.com,debtin.co.za,capabilityhr.com
                 // 'contact' => ['required', new PhoneNumber],
                 'contact' => 'required|numeric|phone', 
                 'lastHighSchoolAttended' => 'required',
                 'qualification_type_id' => 'required',
                 'qualification_name' => 'required',
                 'language_id' => 'required',
     
                 'marital_status_id' => 'required',
                 'country_id' => 'required',
                 'identity_type_id' => 'required',
                 'id_number' => 'required',
                 'dob' => 'required|date|before:-18 years',
                 'nationality_id' => 'required',
                 'gender_id' => 'required',
               ]); 
 
              // $email = "john.doe@example.com";
 
             // if (filter_var($request->personal_email, FILTER_VALIDATE_EMAIL)) {
             // echo("$request->personal_email is a valid email address");
             // } else {
             // echo("$request->personal_emailis not a valid email address");
             // }
             
               
               if($request->identity_type_string == 'South African - ID'){
                 
                   
                     if(!$request->id_number){
                         throw ValidationException::withMessages(['id_number' => "Please enter valid South Afican ID number"]);
                     }
 
                     //id validation
                     if(!$this->verify_id_number($request->id_number)){
                        // (!$request->id_number){
                             throw ValidationException::withMessages(['id_number' => "Please enter a valid South Afican ID number"]);
                       //  }
                      }
            
 
               }else{
                 if(!$request->id_number){
                     throw ValidationException::withMessages(['id_number' => "Please enter valid Passport ID number"]);
                 }
               }
            
 
                 if($request->work_email){
                   
                      if(Email::Where('email', $request->work_email)->exists()){
                          
                          throw ValidationException::withMessages(['work_email' => "Email address already exists - You've already been onboarded on the system"]);
                      }
                 }
                 // duplicate id - swe
                 if($request->id_number){
                   
                     if(Employee::Where('id_number_passport_number', $request->id_number)->exists()){
                         throw ValidationException::withMessages(['id_number' => "ID/Passport number already exists - You've already been onboarded on the system"]);
                     }
                }
                 // duplicate id - swe
 
                 if($request->employee_number){
                   
                      if(Employee::Where('employee_number', $request->employee_number)->exists()){
                          throw ValidationException::withMessages(['employee_number' => "Employee number already exists - You've already been onboarded on the system"]);
                      }
                 }
 
                 if($request->identity_type_id == 1 ){
                     if(!$request->id_number){
                         throw ValidationException::withMessages(['id_number' => "The field ID/Passsport number cannot be null"]);
                     }
                  }
 
                 
                     if(!$request->disability_id){
                         throw ValidationException::withMessages(['disability_id' => "The disabilty field cannot be null"]);
                     }
                 
               
         }
         
         else if($request->step == 'two'){
             $request->validate([
                     'province_id' => 'required',
                     'city_name' => 'required',
                     'suburb_name' => 'required',
                     'street_name' => 'required',
                     'postal_code' => 'required|digits:4',
 
                     'postal_province_id' => 'required',
                     'postal_street' => 'required',
                     'postal_city' => 'required',
                     'postal_suburb' => 'required',
                     'postal_code_postal' => 'required|digits:4',
                 ]); 
         }
         
         elseif($request->step == 'three'){
 
            
 
             $request->validate([
                     'n_fname'=> 'required',
                     'n_lname'=> 'required',
                     'n_contact'=> 'required|numeric',
                     'relationship_id'=> 'required',
 
                     'n_suburb'=> 'required',
                     'n_province'=> 'required',
                     'n_city'=> 'required',
                     'n_street'=> 'required',
                     'n_postal_code'=> 'required|digits:4'
               ]);
 
         
         }
         
         elseif($request->step == 'four'){
              $request->validate([
                 'company_id' =>  'required',
                 'department_id' =>  'required',
                 'sub_department_id' =>  'required',
                 'contract_id' =>  'required', 
                 'designation_id' =>  'required',
                 'hire_date' =>  'required',
                ]);  
         }else{
             // response()->json($request->all());
            
 
 
             
             //DB::beginTransaction();
             //try {
             
             $disability = $request->disability_id;
             if(!is_numeric($disability)){
                 $disability = Disability::create([
                         'name' => $disability
                 ]);
                 $disability = $disability->disability_id;
             }
             
                // duplicate id - swe
                if($request->id_number){
                
                if(Employee::Where('id_number_passport_number', $request->id_number)->exists()){
                    throw ValidationException::withMessages(['id_number' => "ID/Passport number already exists"]);
                }
            }
             
             $employeeDetails = Employee::create([
 
                 'title_id' => $request->title_id,
                 'employee_number' => $request->employee_number,
                 'initials' => $request->initials,
                 'first_name' => $request->first_name, 
                 'second_name' => $request->second_name,
                 'identity_type_id' => $request->identity_type_id,
                 'last_name' => $request->last_name,
                 'contact' => $request->contact,
                 'last_high_school_attended' => $request->lastHighSchoolAttended,
                 'qualification_type_id' => $request->qualification_type_id,
                 'qualification_name' => $request->qualification_name,
                 'language_id' => $request->language_id,
                 'disability_id' => $disability,
                 'marital_status_id' => $request->marital_status_id,
                 'country_id' => $request->country_id,
                 'id_number_passport_number' => $request->id_number,
                 'date_of_birth' => Carbon::parse($request->dob),
                 'nationality_id' => $request->nationality_id,
                 'gender_id' => $request->gender_id,
                 'company_id' => $request->company_id,
                 'department_id' => $request->department_id,
                 'sub_department_id' => $request->sub_department_id,
                 'contract_id' => $request->contract_id,
                 'Hire_Date' =>  Carbon::parse($request->hire_date),
                 'identity_type_id' => $request->identity_type_id,
                 'designation_id' => $request->designation_id,
                 'is_Active' => 1,
                 'setdeactivate' => 0,
              ]);
            
              $employee = Employee::where('employee_number',  $employeeDetails->employee_number)->first();
 
              for ($i=0; $i < count($request->beneficiary); $i++) { 
                  if(isset($request->beneficiary[$i]['birthdate']) && $request->beneficiary[$i]['value']){
                     $beneficiary = Beneficiary::create([
                         'employee_id' => $employee->employee_id,
                         'name' => $request->beneficiary[$i]['value'],
                         'birthdate' => $request->beneficiary[$i]['birthdate'],
                     ]);
                  }
                
                 
              }
 
             
 
             $emails = Email::create([
                 'employee_id' => $employee->employee_id,
                 'email_type_id' =>1,
                 'email'=> $request->personal_email, 
              ]);
 
 
              if($request->work_email=='' || $request->work_email==null)
              {
                 $work_emails = Email::create([
                     'employee_id' => $employee->employee_id,
                     'email_type_id' =>2,
                     'email'=> ' ', 
                  ]);
              }
              else
              {
                 $work_emails = Email::create([
                     'employee_id' => $employee->employee_id,
                     'email_type_id' =>2,
                     'email'=> $request->work_email, 
                  ]);
              }
            
              
              $next_of_kin = Next_of_kin::create([
                 'employee_id' => $employee->employee_id,
                 'fname' =>$request->n_fname,
                 'lname' =>$request->n_lname,
                 'relationship_id' => $request->relationship_id,
                 'contact' =>$request->n_contact,
                 'street' =>$request->n_street,
                 'city' =>$request->n_city,
                 'suburb' => $request->n_suburb,
                 'province' =>$request->n_province,
                 'postal_code' =>$request->n_postal_code,
             ]);
    
             $address = Residential_address::create([
                     'employee_id' => $employee->employee_id,
                     'province_id' => $request->province_id,
                     'city' => $request->city_name,
                     'suburb' => $request->suburb_name,
                     'street' => $request->street_name,
                     'postal_code' => $request->postal_code,
                 ]);
    
             $address_postal = Postal_address::create([
                 'employee_id' => $employee->employee_id,
                 'province_id' => $request->postal_province_id,
                 'city' => $request->postal_city,
                 'suburb' => $request->postal_suburb,
                 'street' => $request->postal_street,
                 'postal_code' => $request->postal_code_postal,
             ]);
 
             // return $next_of_kin->id;
            
             
              // DB::commit();
         
     
                 // } //catch(\Exception $e){
             //         return $e;
             //    }
     
              return response()->json('');
         }
 
 
 
     }


    public function empSearch(Request $request)
    {
        return view('admin.search');
    }

    /************************************************************************************
     * *********************************************************************************
     * *******************************UPDATE EMPLOYEE*******************************************************
     * *********************************************************************************
     */

    public function update(Request $request){
        
        if($request->step == 'one'){

            $request->validate([
                'title_id' => 'required',
                'initials' => 'required|regex:/^[a-zA-Z]+$/u',
                'first_name' => 'required|regex:/^[a-zA-Z]+$/u',
                'employee_number' => 'required',
        
                'last_name' => 'required|regex:/^[a-zA-Z]+$/u',
                'personal_email'=> 'required',
                'contact' => 'required',
                'lastHighSchoolAttended' => 'required',
                'qualification_type_id' => 'required',
                'qualification_name' => 'required',
                'language_id' => 'required',
    
                'marital_status_id' => 'required',
                'country_id' => 'required',
                'identity_type_id' => 'required',
                'dob' => 'required|date|before:-18 years',
                'nationality_id' => 'required',
                'gender_id' => 'required',
              ]); 

              
              if($request->identity_type_string == 'South African - ID'){
                
                  
                    if(!$request->id_number){
                        throw ValidationException::withMessages(['id_number' => "Please enter valid South Afican ID number"]);
                    }

                    //id validation
                    if(!$this->verify_id_number($request->id_number)){
                       // (!$request->id_number){
                            throw ValidationException::withMessages(['id_number' => "Please enter a valid South Afican ID number"]);
                      //  }
                     }
           

              }else{
                if(!$request->id_number){
                    throw ValidationException::withMessages(['id_number' => "Please enter valid Passport ID number"]);
                }
              }
           

               //  if($request->work_email){
                  
               //       if(Email::Where('work_email', $request->work_email)->exists()){
               //           throw ValidationException::withMessages(['work_email' => "Email address already exists"]);
               //       }
               //  }

                if($request->employee_number){
                  
                     if(Employee::Where('employee_number', $request->employee_number)->where('employee_id', '!=', $request->employee_id)->exists()){
                         throw ValidationException::withMessages(['employee_number' => "Employee number is already exists, please enter unique employee number"]);
                     }
                }

                if($request->identity_type_id == 1 ){
                    if(!$request->id_number){
                        throw ValidationException::withMessages(['id_number' => "The field ID/Passsport number cannot be null"]);
                    }
                 }

                
                    if(!$request->disability_id){
                        throw ValidationException::withMessages(['disability_id' => "The disabilty field cannot be null"]);
                    }
                
                    return response()->json(['status' => 'success']);
        }
        
        else if($request->step == 'two'){
            $request->validate([
                    'province_id' => 'required',
                    'city_name' => 'required',
                    'suburb_name' => 'required',
                    'street_name' => 'required',
                    'postal_code' => 'required|digits:4',

                    'postal_province_id' => 'required',
                    'postal_street' => 'required',
                    'postal_city' => 'required',
                    'postal_suburb' => 'required',
                    'postal_code_postal' => 'required|digits:4',
                ]); 
        }
        
        elseif($request->step == 'three'){

           

            $request->validate([
                    'n_fname'=> 'required',
                    'n_lname'=> 'required',
                    'n_contact'=> 'required|numeric',
                    'relationship_id'=> 'required',

                    'n_street'=> 'required',
                    'n_suburb'=> 'required',
                    'n_city'=> 'required',
                    'n_province'=> 'required',
                    'n_postal_code'=> 'required|digits:4'
              ]);

        
        }
        
        elseif($request->step == 'four'){
             $request->validate([
                'company_id' =>  'required',
                'department_id' =>  'required',
                'sub_department_id' =>  'required',
                'contract_id' =>  'required', 
                'designation_id' =>  'required',
                'hire_date' =>  'required',
               ]);  
        }else{
           
           
           $request->validate([
               'title_id' => 'required',
               'initials' => 'required|regex:/^[a-zA-Z]+$/u',
               'first_name' => 'required|regex:/^[a-zA-Z]+$/u',
               'employee_number' => 'required',
               'last_name' => 'required|regex:/^[a-zA-Z]+$/u',
               'personal_email'=> 'required',
    
               'contact' => 'required',
               'lastHighSchoolAttended' => 'required',
               'qualification_type_id' => 'required',
               'qualification_name' => 'required',
               'language_id' => 'required',
               'marital_status_id' => 'required',
               'country_id' => 'required',
               'identity_type_id' => 'required',
               'dob' => 'required|date|before:-18 years',
               'nationality_id' => 'required',
               'gender_id' => 'required',


               'province_id' => 'required',
               'city_name' => 'required',
               'suburb_name' => 'required',
               'street_name' => 'required',
               'postal_code' => 'required|digits:4',

               'postal_province_id' => 'required',
               'postal_street' => 'required',
               'postal_city' => 'required',
               'postal_suburb' => 'required',
               'postal_code_postal' => 'required|digits:4',

               'n_fname'=> 'required',
               'n_lname'=> 'required',
               'n_contact'=> 'required|numeric',
               'relationship_id'=> 'required',

               'n_street'=> 'required',
               'n_suburb'=> 'required',
               'n_city'=> 'required',
               'n_province'=> 'required',
               'n_postal_code'=> 'required|digits:4',

               'company_id' =>  'required',
               'department_id' =>  'required',
               'sub_department_id' =>  'required',
               'contract_id' =>  'required', 
               'designation_id' =>  'required',
               'hire_date' =>  'required',
              ]); 

            
            //DB::beginTransaction();
            //try {
            
            $disability = $request->disability_id;
            if(!is_numeric($disability)){

              
                $disability = Disability::create([
                        'name' => $disability
                ]);
                $disability = $disability->disability_id;
            }
            $employee = Employee::where('employee_id', $request->employee_id)->firstorfail();

            if(!$request->designation_id){
                  
               throw ValidationException::withMessages(['designation_id' => "Missing your designation"]);
              
          }


            $employee->title_id = $request->title_id;
            $employee->employee_number = $request->employee_number;
            $employee->initials = $request->initials;
            $employee->first_name = $request->first_name;
            $employee->second_name = $request->second_name;
            $employee->identity_type_id = $request->identity_type_id;
            $employee->last_name = $request->last_name;
            $employee->contact = $request->contact;
            $employee->last_high_school_attended = $request->lastHighSchoolAttended;
            $employee->qualification_type_id = $request->qualification_type_id;
            $employee->qualification_name = $request->qualification_name;
            $employee->language_id = $request->language_id;
            $employee->disability_id = $request->disability_id;
            $employee->marital_status_id = $request->marital_status_id;
            $employee->country_id = $request->country_id;
            $employee->id_number_passport_number = $request->id_number;
            $employee->dob = $request->dob;
            $employee->nationality_id = $request->nationality_id;
            $employee->gender_id = $request->gender_id;
            $employee->company_id = $request->company_id;
            $employee->department_id = $request->department_id;
            $employee->sub_department_id = $request->sub_department_id;
            $employee->contract_id = $request->contract_id;
            $employee->hire_date = $request->hire_date;
            $employee->identity_type_id = $request->identity_type_id;
            $employee->designation_id = $request->designation_id;
            $employee->save();

    
             for ($i=0; $i < count($request->beneficiary); $i++) { 
                 if(isset($request->beneficiary[$i]['birthdate']) && $request->beneficiary[$i]['value'] && !isset($request->beneficiary[$i]['id'])){
                    $beneficiary = Beneficiary::create([
                        'employee_id' => $employee->employee_id,
                        'name' => $request->beneficiary[$i]['value'],
                        'birthdate' => $request->beneficiary[$i]['birthdate'],
                    ]);
                 }else{
                   $beneficiary = Beneficiary::where('beneficiary_id', $request->beneficiary[$i]['id'])->first();
                   if($beneficiary){
                       $beneficiary->name = $request->beneficiary[$i]['value'];
                       $beneficiary->birthdate = $request->beneficiary[$i]['birthdate'];
                       $beneficiary->save();
                   }
                   
                 }
               
                
             }

            

             $email = Email::where('employee_id', $request->employee_id)->first();
             if($email){
               $email->work_email = $request->work_email;
               $email->personal_email = $request->personal_email;
               $email->save();
             }
             else{
               $emails = Email::create([
                   'employee_id' => $employee->employee_id,
                   'personal_email'=> $request->personal_email,
                   'work_email'=> $request->work_email,
                ]);
             }


             
            

            
            $next_of_kin = Next_of_kin::where('employee_id', $request->employee_id)->first();
            if($next_of_kin){
              
              $next_of_kin->fname = $request->n_fname;
              $next_of_kin->lname = $request->n_lname;
              $next_of_kin->relationship_id = $request->relationship_id;
              $next_of_kin->contact = $request->n_contact;
              $next_of_kin->suburb = $request->n_suburb;
              $next_of_kin->street = $request->n_street;
              $next_of_kin->city = $request->n_city;
              $next_of_kin->province = $request->n_province;
              $next_of_kin->postal_code = $request->n_postal_code;
              $next_of_kin->save();
            }
            else{
               $next_of_kin = Next_of_kin::create([
                   'employee_id' => $employee->employee_id,
                   'fname' =>$request->n_fname,
                   'lname' =>$request->n_lname,
                   'relationship_id' => $request->relationship_id,
                   'contact' =>$request->n_contact,               
                   'suburb' => $request->n_suburb,
                   'street' =>$request->n_street,
                   'city' =>$request->n_city,
                   'province' =>$request->n_province,
                   'postal_code' =>$request->n_postal_code,
               ]);
            }
   
           

                $address = Address::where('employee_id', $request->employee_id)->first();
                if($address){
                   $address->province_id = $request->province_id;
                   $address->street = $request->street_name;
                   $address->city = $request->city_name;
                   $address->suburb = $request->suburb_name;
                   $address->postal_code = $request->postal_code;
                   $address->save();
                }
                else{
                   $address = Address::create([
                       'employee_id' => $employee->employee_id,
                       'street' => $request->street_name,
                       'suburb' => $request->suburb_name,
                       'city' => $request->city_name,
                       'province_id' => $request->province_id,
                       'postal_code' => $request->postal_code,
                   ]);
                }
       
            

            $address_postal = Postal_address::where('employee_id', $request->employee_id)->first();
                if($address_postal){
                  $address_postal->province_id = $request->postal_province_id;
                  $address_postal->street = $request->postal_street;
                  $address_postal->suburb = $request->postal_suburb;
                  $address_postal->city = $request->postal_city;
                  $address_postal->postal_code = $request->postal_code_postal;
                  $address_postal->save();
                }
                else{
                   $address_postal = Postal_address::create([
                       'employee_id' => $employee->employee_id,
                       'province_id' => $request->postal_province_id,
                       'street' => $request->postal_street,
                       'suburb' => $request->postal_suburb,
                       'city' => $request->postal_city,
                       'postal_code' => $request->postal_code_postal,
                   ]);
       
                }
            // return $next_of_kin->id;
           
            
             // DB::commit();
        
    
                // } //catch(\Exception $e){
            //         return $e;
            //    }
    
             return response()->json(['status' => 'success']);
        }



    }

    public function empUpdate($employee_number)
    {
 
     $someVariable = $employee_number;
 
     $results = DB::select( DB::raw("select 
     e.employee_id,
        e.employee_number,
        e.first_name,
        e.second_name,
        e.last_name,
        e.Initials,
     e.title_id,
     e.gender_id,
     e.contract_id,
     e.country_id,
     e.company_id,
     e.department_id,
     e.designation_id,
     e.disability_id,
     e.identity_type_id,
     e.language_id,
     e.marital_status_id,
     e.qualification_type_id,
     e.nationality_id,
        e.contact,
        e.last_high_school_attended, 
        e.id_number_passport_number,
        e.qualification_name,
        e.date_of_birth,
        e.Hire_Date,
          qt.name as QualificationName,
          gd.name as Gender,
          eml.email WorkEmail,
     eml.email_id,
     emlp.email_id as P_emailId,
     emlp.email personal_email,
     addrs.residential_address_id as ResId,
          addrs.city as ACity,
          addrs.suburb as ASuburb,
          addrs.street as AStreet,
          addrs.postal_code as APostalCode,
     addrs.province_id as AProvince,
     psa.postal_address_id as PosId,
          psa.city as City,
          psa.suburb as Suburb,
          psa.street as Street,
          psa.postal_code as PostalCode,
     psa.province_id  as PProvince,
     tt.title_abbr as Title,
          lg.name       as LanguageName,       
          mtst.name     as MaritalStatus,      
          dpt.name      as DepartmentName,
          cp.name          as CompanyName,
          ctrs.contract_type       as ContractName,
          cts.name                 as CountryName,
          dsg.name     as DesignationName,
     nxk.next_of_kin_id as nokID,
     nxk.relationship_id as RelID,
          nxk.fname      as NOF_Fname,
          nxk.lname       as NOF_Lname,
          nxk.contact     as NOF_Contact,  
          nxk.city        as NOF_City,
          nxk.postal_code as NOF_PostalCode,
     nxk.street       as NOF_Street,
     nxk.suburb       as NOF_Suburb,
     nxk.province     as NOF_Province,
     e.Sub_Department_id  as subdepid,
     e.is_Active
 
 from 
 
 dbo.employees e
 
 
 
 
 left join next_of_kins nxk              on e.employee_id=nxk.employee_id
 
 left join contracts ctrs                on e.contract_id=ctrs.contract_id
 
 left join countries cts                 on e.country_id=cts.country_id
 
 left join titles tt                     on e.title_id=tt.title_id
 
 left join companies cp                  on e.company_id=cp.company_id
 
 LEFT JOIN emails eml
    ON e.employee_id = eml.employee_id
    and eml.email_type_id = 2
 
 LEFT JOIN emails emlp
    ON e.employee_id = emlp.employee_id
    and emlp.email_type_id = 1
 
 left join qualification_types qt        on e.qualification_type_id=qt.qualification_type_id
 
 left join genders gd                    on e.gender_id=gd.gender_id
 
 left join postal_addresses psa          on e.employee_id=psa.employee_id
 
 left join residential_addresses addrs   on e.employee_id=addrs.employee_id
 
 left join languages lg                  on e.language_id=lg.language_id
 
 left join marital_statuses mtst         on e.marital_status_id=mtst.marital_status_id
 
 left join departments dpt               on e.department_id=dpt.department_id
 
 left join designations dsg              on e.designation_id=dsg.designation_id
 
 where e.is_Active=1 and employee_number = :somevariable"), array(
   'somevariable' => $someVariable,
      ));
 
     // return $results;
     return view('admin.employees.update', compact('results'));
    }
     
   public function getEmployee(Request $request){

       $employee = Employee::where('employee_id', $request->user_employee_number)->firstorfail();
       $address = Address::where('employee_id', $employee->employee_id)->first();
       $postal_address = Postal_address::where('employee_id', $employee->employee_id)->first();
       $email = Email::where('employee_id', $employee->employee_id)->first();
       $next_of_kin = Next_of_kin::where('employee_id', $employee->employee_id)->first();
       $disability = Disability::where('disability_id', $employee->disability_id)->first();
       $beneficiary = Beneficiary::where('employee_id', $employee->employee_id)->get();
       

       return response()->json([
           'employee' => $employee,
           'address' => $address,
           'postal_address' => $postal_address,
           'email' => $email,
           'next_of_kin' => $next_of_kin,
           'beneficiaries' => $beneficiary,
           'disability' => $disability,
           
       ]);

       

   }


   // SWE
   
public function updateemppersonaldetails(Request $request,$id,$empnum,$titleid,$init,$fn,$sn,$ln,$gid,$emid,$Pemid,$emW,$emP,$Cell,$highsname,$QualId,$QualN,$Hlang,$DisId,$MId,$CId,$idype,$idpass,$dob,$ethin)  
{       
   DB::transaction(function() use($request, $id,$empnum,$titleid,$init,$fn,$sn,$ln,$gid,$emid,$Pemid,$emW,$emP,$Cell,$highsname,$QualId,$QualN,$Hlang,$DisId,$MId,$CId,$idype,$idpass,$dob,$ethin)
    {   
        
       DB::table('employees')->where('employee_id',$id)->update([
            'first_name' =>                         $fn,
            'second_name' =>                        $sn,
            'employee_number' =>                    $empnum,             
            'last_name' =>                          $ln,
            'Initials' =>                           $init,
            'contact' =>                            $Cell,
            'last_high_school_attended' =>          $highsname,
            'qualification_type_id' =>              $QualId,
            'language_id' =>                        $Hlang,
            'disability_id' =>                      $DisId,
            'marital_status_id' =>                  $MId,
            'country_id' =>                         $CId,
            'identity_type_id' =>                   $idype,
            'date_of_birth' =>                      $dob,
            'nationality_id'  =>                    $ethin,
            'gender_id'  =>                         $gid,
            'title_id'  =>                          $titleid,
            'qualification_name'  =>                $QualN,
            'id_number_passport_number'  =>         $idpass,
            'updated_at' =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
        ]);

        DB::table('emails')->where('email_id',$emid)->update([
            'email' =>  $emW,
            'email_type_id' =>  2,
            'updated_at' =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
        ]);

        DB::table('emails')->where('email_id',$Pemid)->update([
         'email' =>  $emP,
         'email_type_id' =>  1,
         'updated_at' =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
     ]);



    });
    return response()->json('Updated Successfully');
}

public function updateempaddressdetails(Request $request,$empid,$aresid,$presid,$v_Rstreet,$v_RSuburb,$v_RCity,$v_RPcode,$selectedResID,$v_Pstreet,$v_PSuburb,$v_PCity,$v_PPcode,$selectedPosID)  
{       
   DB::transaction(function() use($request, $empid,$aresid,$presid,$v_Rstreet,$v_RSuburb,$v_RCity,$v_RPcode,$selectedResID,$v_Pstreet,$v_PSuburb,$v_PCity,$v_PPcode,$selectedPosID)
    {   
        
       DB::table('residential_addresses')->where('residential_address_id',$aresid)->update([
            'city' => $v_RCity,
            'suburb' => $v_RSuburb,
            'street' =>  $v_Rstreet,
            'postal_code' => $v_RPcode,
            'province_id' => $selectedResID,
            'updated_at' =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
        ]);

        DB::table('postal_addresses')->where('postal_address_id',$presid)->update([
            'city' => $v_PCity,
            'suburb' => $v_PSuburb,
            'street' => $v_Pstreet,
            'postal_code' => $v_PPcode,
            'province_id' => $selectedPosID,
            'updated_at' =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
        ]);


    });
    return response()->json('Updated Successfully');
}

//
public function updateempnextofkindetails(Request $request,$nokid,$v_nxtcontact,$v_nxtstreet,$v_nxtsurburb,$v_nxtcity,$v_nxtpostc,$v_nxtfname,$v_nxtlname,$selectednxtrel,$selectednxtpro)  
{       
   DB::transaction(function() use($request, $nokid,$v_nxtcontact,$v_nxtstreet,$v_nxtsurburb,$v_nxtcity,$v_nxtpostc,$v_nxtfname,$v_nxtlname,$selectednxtrel,$selectednxtpro)
    {   
   
       DB::table('next_of_kins')->where('next_of_kin_id',$nokid)->update([
            'relationship_id' => $selectednxtrel,
            'fname' => $v_nxtfname,
            'lname' =>  $v_nxtlname,
            'contact' => $v_nxtcontact,
            'suburb' => $v_nxtsurburb,
            'city' => $v_nxtcity,
            'province' => $selectednxtpro,
            'postal_code' =>$v_nxtpostc,
            'street' => $v_nxtstreet,
            'updated_at' =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
        ]);

    });
    return response()->json('Updated Successfully');
}

public function updateempcompanydetails(Request $request,$empid,$comid,$depid,$subdepid,$desgid,$conid,$Hd)  
{       
   DB::transaction(function() use($request, $empid,$comid,$depid,$subdepid,$desgid,$conid,$Hd)
    {   
     
       DB::table('employees')->where('employee_id',$empid)->update([
            'company_id' => $comid,
            'designation_id' => $desgid,
            'contract_id' =>  $conid,
            'Hire_Date' => $Hd,
            'department_id' => $depid,
            'Sub_Department_id' => $subdepid,
            'updated_at' =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
        ]);

    });
    return response()->json('Updated Successfully');
}

public function Getallemployeestatuses()
{
 $data = DB::select(DB::raw("select e.Employee_Status_ID,e.Name,e.isMaster,e.masterID from Employee_Statuses e where e.isMaster=1")); 
 return response()->json(['EmpStat' => $data]);
} 

public function ValidateEmployeeNumber($empnum)
{
    $someVariable = $empnum;
    $results = DB::select( DB::raw("SELECT TOP 1 1 AS VAL FROM employees WHERE employee_number = :somevariable"), array(
    'somevariable' => $someVariable,
   ));
  if(empty($results))
    {
        return 0;
    }
    else
    {
        return 1;
    }

} 

public function GetEmployeeByIDNo($idnum)
{
    DB::statement("WITH CTE AS(SELECT *,RN=ROW_NUMBER() OVER(PARTITION BY id_number_passport_number,id_number_passport_number ORDER BY id_number_passport_number) FROM employees) DELETE FROM CTE WHERE RN>1");

    $someVariable = $idnum; 
    $results = DB::select( DB::raw("select top 1 *
    from stage_candidates
    where is_employee <> 1 and is_Active=1 and id_passport_number = :somevariable"), array(
    'somevariable' => $someVariable,
 ));

    // Check if ID number isn't on employee table
    $checker = $idnum; 
    $CheckerResults = DB::select( DB::raw("select top 1 * from employees where is_Active=1 and id_number_passport_number = :somevariable"), array(
    'somevariable' => $someVariable,
    ));
    // Check if ID number isn't on employee table

        if($CheckerResults!=false)
        {
          
            $Status = 404;  // failed - idnumber dosn't exist
            $Message = "Candidate ID/Passport number already exist as an active employee";
            return response()->json(['Status' =>  $Status,'Message' =>   $Message,'CandidateDetails' => $results]);
            }
        else
        {
            if($results==false)
            {
                $Status = 0;  // failed - idnumber dosn't exist
                $Message = "Candidate doesn't exist";
                return response()->json(['Status' =>  $Status,'Message' =>   $Message,'CandidateDetails' => $results]);
            }
            else
            {
                $Status = 1; //passed - idnumber does exist
                $Message = "Candidate found";
                return response()->json(['Status' =>  $Status,'Message' =>   $Message, 'CandidateDetails' => $results]);
            }
        }
}

public function viewcandidates()
{
 return view('admin.employees.candidate');
}

public function ValidateWorkMail($empemail)
{
    $someVariable = $empemail;
    $results = DB::select( DB::raw("SELECT TOP 1 1 AS VAL FROM emails WHERE email = :somevariable"), array(
    'somevariable' => $someVariable,
   ));
  if(empty($results))
    {
        return 0;
    }
    else
    {
        return 1;
    }

} 


public function Getalldisabilities()
{
 $data = DB::select(DB::raw("select name,disability_id from disabilities ")); 
 return response()->json(['Dis' => $data]);
} 


public function addcandidate($idnum)
{
     $someVariable = $idnum; 
     $candidate = DB::select( DB::raw("select top 1 first_name,last_Name,personal_email_address,id_passport_number
     from stage_candidates
     where hashkey = :somevariable"), array(
     'somevariable' => $someVariable,
     ));

    // return $candidate;
     return view('admin.employees.addcandidate', compact('candidate'));
}



public function editcandidate()
{
   $AllCandidates = DB::select( DB::raw("select * from stage_candidates"));
   return view('admin.employees.candidatedash', compact('AllCandidates'));
}




public function GetAllCandidates()
{
 $AllCandidates = DB::select( DB::raw("select * from stage_candidates where is_Active=1 and is_employee <> 1 ")); 
 return response()->json(['AllCandidates' => $AllCandidates]);
}





 // START SINGLE LOOKUPS
 public function getsingleidtype($name)
 {
    $someVariable = $name;
    $data = DB::select( DB::raw("select top 1 identity_type_id from identity_types WHERE name = :somevariable"), array(
     'somevariable' => $someVariable,
    ));
     return response()->json(['data' => $data]);
 }
 public function getsingledep($name)
 {
     $someVariable = $name;
     $data = DB::select( DB::raw("select top 1 department_id from departments WHERE name = :somevariable"), array(
      'somevariable' => $someVariable,
     ));
     return response()->json(['data' => $data]);
 }
 public function getsinglesubdep($name)
 {
     $someVariable = $name;
     $data = DB::select( DB::raw("select top 1 sub_department_id from sub_departments WHERE name = :somevariable"), array(
      'somevariable' => $someVariable,
     ));
     return response()->json(['data' => $data]);   
 }
 public function getsingledes($name)
 {
     $someVariable = $name;
     $data = DB::select( DB::raw(" select top 1 designation_id from designations WHERE name = :somevariable"), array(
      'somevariable' => $someVariable,
     ));
     return response()->json(['data' => $data]); 
 }
 public function getsinglegend($name)
 {
     $someVariable = $name;
     $data = DB::select( DB::raw("select top 1 gender_id from genders WHERE name = :somevariable"), array(
      'somevariable' => $someVariable,
     ));
     return response()->json(['data' => $data]); 
 }
 public function getsinglenat($name)
 {
     $someVariable = $name;
     $data = DB::select( DB::raw("select top 1 nationality_id from nationalities WHERE name = :somevariable"), array(
      'somevariable' => $someVariable,
     ));
     return response()->json(['data' => $data]);
 }

 public function getsinglecandidate($id)
 {
     $someVariable = $id;
     $data = DB::select( DB::raw("select top 1 * from stage_candidates WHERE id = :somevariable"), array(
      'somevariable' => $someVariable,
     ));
     return response()->json(['data' => $data]);   
 }
// END SINGLE LOOKUPS
// fetch lookups

     public function updatecandidatedetails(Request $request,$id,$fn,$ln,$idnum,$date,$con,$email,$idtype,$dep,$subdep,$des,$ethen,$gen,$stat,$deactivatereason)  
     {       
        //  return $stat;
         DB::transaction(function() use($request,$id,$fn,$ln,$idnum,$date,$con,$email,$idtype,$dep,$subdep,$des,$ethen,$gen,$stat,$deactivatereason)
         {            
             DB::table('stage_candidates')->where('id',$id)->update([
                 'first_name'                         => $fn,
                 'last_Name'                          => $ln,
                 'id_passport_number'                 => $idnum,
                 'startdate'                          => $date,
                 'contact1'                           => $con,
                 'personal_email_address'             => $email,
                 'id_type'                            => $idtype,
                 'department'                         => $dep,
                 'subdepartment'                      => $subdep,
                 'designation'                        => $des,
                 'ethnicity'                          => $ethen,
                 'gender'                             => $gen,
                 'is_Active'                          => $stat,
                 'candidate_status_id'                => $deactivatereason,
                 'updated_at'                         =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
             ]);
         });
         return response()->json('Updated Successfully');
     }

      public function getcampaigns()
      {
        $camps = DB::select( DB::raw("select id,first_name,last_name,startdate,subDepartment,designation from stage_candidates where sendemail=1")); 
        return response()->json(['camps' => $camps]);
      }     

      public function updatecampaignsandsendemails(Request $request)
       {
         DB::statement("update stage_candidates set sendemail=0");
       }    

       public function sendemail(Request $request)
       {
          // return  response()->json($request->All());
         Mail::to("Recruitmenttest@capabilitybpo.com")->send(new NewEmployee($request->All()));
       }

       public function getnotifications()
       {

        $tempemail = auth()->user()->email;
        $someVariable =  $tempemail;
        $data = DB::select( DB::raw("select * from notifications WHERE user_read=0 and email = :somevariable"), array(
         'somevariable' => $someVariable,
        ));
        return response()->json(['data' =>$data]);   
       }

       public function setnotifications()
       {
        $tempemail = auth()->user()->email;
        DB::table('notifications')->where('email',$tempemail)->update([
            'user_read'   => 0,
        ]);
        return response()->json(['data' =>"Notification Set"]);   
       }
      

       public function clearnotification()
       {
        $tempemail = auth()->user()->email;
        DB::table('notifications')->where('email',$tempemail)->where('type_','=',1)->update([
            'user_read'   => 1,
        ]);
        return redirect('/candidates/view');
       }

       public function signup()
       {
        return view('admin.employees.Signup');
       }

      public function adduser(Request $request,$email,$pass,$depid)
         {
           
            $user = User::create([
                'email'    => $email,
                'password' => $pass,
                'role_id'  => $depid,
                'created-at'=> date("Y-m-d H:i:s",strtotime('2 hour')), 
             ]);

             return "success";
         }


         public function deactivatecandidatestatus()
         {
            $candidatestatus = DB::select( DB::raw("select candidate_status_id,candidate_status_name from candidatestatus")); 
            return response()->json(['candidatestatus' => $candidatestatus]);
         }

         public function removecandidate(Request $request,$id)  
         {       
            $someVariable =  $id;
            $data = DB::select( DB::raw("delete from stage_candidates WHERE id = :somevariable"), array(
             'somevariable' => $someVariable,
            ));
            return response()->json('Deleted Successfully');  
         }

         public function bulknotificationsetting($val)  
         {    
         if($val==1)
            {
              // send notifications to everyone besdies WFM  - 1
              // If (groupon or  Instacart) then email and inapp 
              // (IT, Facilities,Recruitment, WFM)
              // ,'facilities@capabilitybpo.com','it@capabilitybpo.com'


             // 3 emails just for test but on live we do cc
            //Mail::to("Recruitmenttest@capabilitybpo.com")->send(new NewEmployee($request->All()));
            //  Mail::to("wfm@capabilitybpo.com")->send(new NewEmployee($request->All()));
            //  Mail::to("facilities@capabilitybpo.com")->send(new NewEmployee($request->All()));
            //  Mail::to("it@capabilitybpo.com")->send(new NewEmployee($request->All()));


            //  Mail::to("Recruitmenttest@capabilitybpo.com")
            //  ->cc("wfm@capabilitybpo.com")
            //  ->bcc("facilities@capabilitybpo.com")
            //  ->send(new NewEmployee($request->All()));
             DB::statement("update notifications set user_read=0 where notification_id between 1 and 5");
            }

          if($val==2)
            {
             // send notifications to everyone besdies WFM  - 1
             //( IT, Facilities, Recruitment)  
             DB::statement("update notifications set user_read=0 where notification_id <> 4");
            }
            return response()->json('Updated Successfully');   
            
         }

          public function candidatetoemployee($id)
            {
            DB::table('stage_candidates')->where('id_passport_number',$id)->update([
            'is_employee'   => 1,
            ]);
            return response()->json(['Feedback' =>"Candidate is now an employee"]);   
            }

            public function insertcandidateemail(Request $request,$id,$email)
            {
               DB::transaction(function() use($request,$id,$email)
               {            
                   DB::table('stage_candidates')->where('id',$id)->update([
                       'work_email'                         =>  $email,
                       'updated_at'                         =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
                   ]);
               });
               return response()->json('Updated Successfully');
            }
            public function checkaccess()
         {
           
  
                $tempemail = auth()->user()->email;
                $someVariable =  $tempemail;
                $data = DB::select( DB::raw("select u.role_id from roles r join users u on u.role_id=r.role_id  where u.email = :somevariable"), array(
                'somevariable' => $someVariable,
                ));
        
                return response()->json(['data' => $data]);
            

                // if($data!=7)
                // {
                //     $Status = 0;  
                //     $Message = "NOT IT ACCESS";
                //     return response()->json(['Status' =>  $Status]);
                // }
                // else
                // {
                //     $Status = 1;
                //     $Message = "IT ACCESS";
                //     return response()->json(['Status' =>  $Status]);
                // }

         }




         public function confirmoffboard(Request $request,$id,$fn,$ln,$empemail,$offboardreason,$date,$lastphysicalworkday,$datenoticegiven,$department,$designation,$linemanager)  
         {       

            $email_string =  $fn . ' ' . $ln  . ', '. $empemail;

           
            DB::transaction(function() use($request,$id)
            {            
                DB::table('offboards')->where('employee_id',$id)->update([
                    'confirmed'                          =>  1,
                    'confirmed_by'                       =>  "hr@capabilitybpo.com",
                    'confirmed_at'                       =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
                ]);

              //DeactivateDate on employees table

              DB::table('employees')->where('employee_id',$id)->update([
                       'DeactivationDate'                   =>  date("Y-m-d H:i:s",strtotime('2 hour')),
                       'setdeactivate'                      => "1",
                       'DeactivationDate_by'                =>  "hr@capabilitybpo.com",
                  ]);

              //End

            });

            // Mail::to("wfm_test@capabilitybpo.com")->send(new cls_ConfirmOffBoard($email_string,$date,"Offboarded",$department,$designation,$lastphysicalworkday,$datenoticegiven,$linemanager));
            // Mail::to("it_test@capabilitybpo.com")->send(new cls_ConfirmOffBoard($email_string,$date,"Offboarded",$department,$designation,$lastphysicalworkday,$datenoticegiven,$linemanager));
            // Mail::to("hr@capabilitybpo.com")->send(new cls_ConfirmOffBoard($email_string,$date,"Offboarded",$department,$designation,$lastphysicalworkday,$datenoticegiven,$linemanager));
            // Mail::to("payroll@capabilitybpo.com")->send(new cls_ConfirmOffBoard($email_string,$date,"Offboarded",$department,$designation,$lastphysicalworkday,$datenoticegiven,$linemanager));
            // Mail::to("facilities@capabilitybpo.com")->send(new cls_ConfirmOffBoard($email_string,$date,"Offboarded",$department,$designation,$lastphysicalworkday,$datenoticegiven,$linemanager));     
            // Mail::to("compliance@capabilitybpo.com")->send(new cls_ConfirmOffBoard($email_string,$date,"Offboarded",$department,$designation,$lastphysicalworkday,$datenoticegiven,$linemanager));
          

             return response()->json('Offboarded Successfully');


         }

         public function postresignationdata($employee_id,$email,$resdate,$fullname,$lastphysicalworkday,$datenoticegiven,$department,$designation)
         {
              $sessionemail = auth()->user()->email;
              $docname      =  $email .'-Resignation';
              $docloc       =  'public\resignation_letters';
              $ResLetterData = resignation_letter::create([
                  'employee_id' =>           $employee_id,
                  'fullname' =>              $fullname,
                  'email' =>                 $email,
                  'document_name' =>         $docname,
                  'document_location' =>     $docloc,
                  'created_by'=>             $sessionemail,
                  'resignationdate'=>        $resdate,   
                  'created_at' =>            date("Y-m-d H:i",strtotime('2 hour')),
              ]);
  
              $offboard = Offboard::create([
                  'employee_email' =>                    $email,
                  'employee_id' =>                       $employee_id,
                  'Employee_Status_ID'=>                 13, 
                  'offboard_reason'=>                    'Resigned', 
                  'created_by'=>                         $sessionemail, 
                  'lastday' =>                           $resdate,
                  'confirmed' =>                         0,
                  'fullname'       =>                    $fullname,
                  'lastphysicalworkdate'       =>        $lastphysicalworkday,
                  'datenoticegiven'      =>              $datenoticegiven,
                  'department'       =>                  $department,
                  'designation'      =>                  $designation,
              ]);
  
  
  
              //post to offboards
              //mail to hr
              return response()->json('Created Successfully');
         }
  
         public function postlastdate($employee_id,$enddate, $psubtype)
         {    
              DB::table('offboards')->where('employee_id',$employee_id)->update([
                  'updated_at'                 =>  date("Y-m-d H:i",strtotime('2 hour')),
                  'lastday'                    =>  $enddate,
                //   'lastphysicalworkdate'       =>  $lastphysicalday,
                //   'datenoticegiven'            =>  $datenotice,
                  'project_sub_type_id'        =>  $psubtype 
              ]);
              return response()->json('Updated Successfully');
         }


public function sendconfimationmails(Request $request,$id,$fn,$ln,$empemail,$offboardreason,$date,$lastphysicalworkday,$datenoticegiven,$department,$designation,$linemanager)  
        {  

            $email_string =  $fn . ' ' . $ln  . ', '. $empemail;
            // Assignment check
            $assignments = \App\Models\Task::where('tasks.assigned_to', $id)->where('tasks.task_status_id', 2)->count() > 0;
            $dep_name = \App\Models\Employee::join('departments', 'departments.department_id','=','employees.department_id')
            ->select('departments.department_id')
            ->where('employees.employee_id', $id)
            ->first();
            function assignCheck($dep, $user_dep, $assignment){
                return ($dep == $user_dep) && $assignment;
            }

            Mail::to("wfm_test@capabilitybpo.com")->send(new cls_ConfirmOffBoard($email_string,$date,"Offboarded",$department,$designation,$lastphysicalworkday,$datenoticegiven,$linemanager, assignCheck("9",$dep_name->department_id, $assignments)));
            Mail::to("it_test@capabilitybpo.com")->send(new cls_ConfirmOffBoard($email_string,$date,"Offboarded",$department,$designation,$lastphysicalworkday,$datenoticegiven,$linemanager, assignCheck("2",$dep_name->department_id, $assignments)));
            Mail::to("hr@capabilitybpo.com")->send(new cls_ConfirmOffBoard($email_string,$date,"Offboarded",$department,$designation,$lastphysicalworkday,$datenoticegiven,$linemanager, assignCheck("5",$dep_name->department_id, $assignments)));
            Mail::to("payroll@capabilitybpo.com")->send(new cls_ConfirmOffBoard($email_string,$date,"Offboarded",$department,$designation,$lastphysicalworkday,$datenoticegiven,$linemanager, assignCheck("4",$dep_name->department_id, $assignments)));
            Mail::to("facilities@capabilitybpo.com")->send(new cls_ConfirmOffBoard($email_string,$date,"Offboarded",$department,$designation,$lastphysicalworkday,$datenoticegiven,$linemanager, assignCheck("6",$dep_name->department_id, $assignments)));     
            Mail::to("compliance@capabilitybpo.com")->send(new cls_ConfirmOffBoard($email_string,$date,"Offboarded",$department,$designation,$lastphysicalworkday,$datenoticegiven,$linemanager, assignCheck("8",$dep_name->department_id, $assignments)));
            return response()->json('Emails sent successfully');
        }


        public function clearnotification1()
        {
         $tempemail = auth()->user()->email;
         DB::table('notifications')->where('email',$tempemail)->where('type_','=',2)->update([
             'user_read'      => 1,
             'notification'   => ' ',
         ]);
         return redirect('/confirmoffboard');
        }
        public function offboardemployee($designation,$fn,$empemail,$empid,$offbkey,$offboardreason,$date,$lastphysicalworkday,$Description,$datenoticegiven,$department, $projsubtype)
        {    
            $sessionemail = auth()->user()->email;
            $email_string =  $fn . ', '. $empemail;
    

                   $offboard = Offboard::create([
                       'employee_email' =>                    $empemail,
                       'employee_id' =>                       $empid,
                       'Employee_Status_ID'=>                 $offbkey, 
                       'offboard_reason'=>                    $offboardreason, 
                       'created_by'=>                         $sessionemail, 
                       'lastday' =>                           $date,
                       'confirmed' =>                         0,
                       'fullname'       =>                    $fn,
                       'lastphysicalworkdate'       =>        $lastphysicalworkday,
                       'description'       =>                 $Description,
                       'datenoticegiven'      =>              $datenoticegiven,
                       'department'       =>                  $department,
                       'designation'      =>                  $designation,
                       'project_sub_type_id'      =>                  $projsubtype
                   ]);
                   //end

                  // Clear Existing Email Address

                   DB::statement("update notifications set notification=' ',user_read=0 where notification_id=8 and type_=2");
              
                   // end
                   
                   DB::table('notifications')->where('notification_id',8)->update([
                       'notification'                        => $empemail,
                       'updated_at'                         =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
                   ]);
                  
                 // set provisional offboard

                 DB::table('employees')->where('employee_id',$empid)->update([
                    'provisionaloffboard'                   => 1,
                    'setdeactivate'                         => 1,
                    'updated_at'                            =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
                ]);


                // Project Owner Check
                $owner_check = \App\Models\ProjectOwner::where('owner_id', $empid)->count() > 0;
                // End project owner check

                   Mail::to("hr@capabilitybpo.com")->send(new cls_OffBoard($email_string,$date,$offboardreason, $owner_check));
         
               return response()->json('Created Successfully');
        }


     // START METHODS FROM API

     public function DeactivateEmployee(Request $request, $id , $EmpStat)  //1
     {             
         DB::transaction(function() use($request, $id,$EmpStat)
         {          
               $set=0;                  
               DB::table('employees')->where('employee_id',$id)->update([
                 'is_Active' =>  $set,
                 'Employee_Status_ID' =>  $EmpStat,
                 'updated_at' =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
             ]);
         });
         return response()->json('Updated Successfully');
   }

     public function GetAllEmployees()
        {    
            $data =  DB::select("EXEC SP_GETALLEMPLOYEEDETAILS"); 
            return response()->json(['data' =>$data]);
        }


        public function GetEmployeesByManagerID(Request $request)
        {
            // $data = DB::table(DB::raw('employees emp'))
            //             ->leftJoin(DB::raw('emails eml'), function($leftJoin){
            //                 $leftJoin->on('emp.employee_id', '=', 'eml.employee_id')
            //                         ->on('eml.email_type_id', '=', DB::raw(2));
            //             })
            //             ->leftJoin(DB::raw('departments d'), function($leftJoin1){
            //                 $leftJoin1->on('emp.department_id', '=', 'd.department_id');
                                   
            //             })
            //             ->leftJoin(DB::raw('sub_departments sd'), function($leftJoin2){
            //                 $leftJoin2->on('emp.Sub_department_id', '=', 'sd.sub_department_id');
                                   
            //             })
            //             ->leftJoin(DB::raw('designations des'), function($leftJoin3){
            //                 $leftJoin3->on('emp.designation_id', '=', 'des.designation_id');
                                   
            //             })
            //             ->select(
            //                 DB::raw("CONCAT(emp.first_name, ' ', emp.last_name) AS full_name"),
            //                 DB::raw('sd.name AS subdepartmentname,d.name AS departmentname,des.name as DesignationName,emp.employee_id,emp.employee_number,emp.id_number_passport_number,eml.email AS work_email')
            //             )->where('manager_id', '=', $request->id)
            //             ->where('emp.setdeactivate', '=', '0')
            //             ->where('emp.provisionaloffboard', '=', '0')
            //             ->where('emp.is_Active', '=', '1')
            //             ->get();
            // return response()->json(['data' =>$data]);

            $data = DB::select(DB::raw("exec offboardusersbymanagerid $request->id"));
            return response()->json(['data'=>$data]);
        }

     public function GetEmployeesForHR(Request $request)
          {

            $hold=null;
            $data = DB::table(DB::raw('employees emp'))
                        ->leftJoin(DB::raw('emails eml'), function($leftJoin){
                            $leftJoin->on('emp.employee_id', '=', 'eml.employee_id')
                                    ->on('eml.email_type_id', '=', DB::raw(2));
                        })
                        ->leftJoin(DB::raw('departments d'), function($leftJoin1){
                            $leftJoin1->on('emp.department_id', '=', 'd.department_id');
                                   
                        })
                        ->leftJoin(DB::raw('sub_departments sd'), function($leftJoin2){
                            $leftJoin2->on('emp.Sub_department_id', '=', 'sd.sub_department_id');
                                   
                        })
                        ->leftJoin(DB::raw('designations des'), function($leftJoin3){
                            $leftJoin3->on('emp.designation_id', '=', 'des.designation_id');
                                   
                        })
                        ->select(
                            DB::raw("CONCAT(emp.first_name, ' ', emp.last_name) AS full_name"),
                            DB::raw('sd.name AS subdepartmentname,d.name AS departmentname,des.name as DesignationName,emp.employee_id,emp.employee_number,emp.id_number_passport_number,eml.email AS work_email'),
                        )->where('emp.setdeactivate', '=', '0')
                        ->where('emp.provisionaloffboard', '=', '0')
                        ->where('emp.is_Active', '=', '1')
                        ->get();
            return response()->json(['data' =>$data]);
            
          }

        public function GetEmployeeByID(Request $request){
            $validateData = $request->validate([
                'id' => 'required'
            ]);

            $data = DB::table(DB::raw('employees emp'))
                        ->leftJoin(DB::raw('emails eml'), function($leftJoin){
                            $leftJoin->on('emp.employee_id', '=', 'eml.employee_id')
                                    ->on('eml.email_type_id', '=', DB::raw(2));
                        })
                        ->select(
                            DB::raw("CONCAT(emp.first_name, ' ', emp.last_name) AS full_name"),
                            DB::raw('eml.email AS work_email'),
                            DB::raw('emp.is_active AS status'),
                            'manager_id'
                        )->where('emp.employee_id', '=', $request->id)
                        ->get();
            return response()->json(['data' =>$data]);
        }


       public function UpdateEmployee(Request $request,$id,$fn,$ln,$em,$emid)  
       
        {         
           DB::transaction(function() use($request, $id,$fn,$ln,$em,$emid)
         
            {                                        
                DB::table('employees')->where('employee_id',$id)->update([
                    'first_name' =>  $fn,
                    'last_name' =>   $ln,
                    'updated_at' =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
                ]);

                DB::table('emails')->where('email_id',$emid)->update([
                    'email' =>  $em,
                    'updated_at' =>  date("Y-m-d H:i:s",strtotime('2 hour')), 
                ]);
            });
            return response()->json('Updated Successfully');
        }

     //END METHODS FROM API 



}
