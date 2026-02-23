<?php


namespace App\Http\Helper;

Use Illuminate\Support\Facades\Auth;

class FunctionsHelper
{
    public function __construct()
    {
    }

    public static function navigation()
    {
        if (Auth::User()->UserRole->slug === 'super-admin') {
            $layoutDirectory = 'superadmin::layouts.master';
        } elseif (Auth::User()->UserRole->slug === 'admin') {
            $layoutDirectory = 'layouts.app';
        } elseif (Auth::User()->UserRole->slug === 'doctor') {
            $layoutDirectory = 'doctor::layouts.master';
        } elseif (Auth::User()->UserRole->slug === 'nurse') {
            $layoutDirectory = 'nurse::layouts.master';
        } elseif (Auth::User()->UserRole->slug === 'receptionist') {
            $layoutDirectory = 'receptionist::layouts.master';
        }
        /** @var TYPE_NAME $layoutDirectory */
        return $layoutDirectory;
    }

    //format YYYY-MM-DD
    public static function convert_date($date_string)
    {
        return date('Y-m-d', strtotime($date_string));
    }

    public static function storeDateFilter($request)
    {
        $request->session()->put('from', $request->has('start_date') ? $request->get('start_date') : ($request->session()->has
        ('from') ? $request->session()->get('from') : ''));
        $request->session()->put('to', $request->has('end_date') ? $request->get('end_date') : ($request->session()->has
        ('to') ? $request->session()->get('to') : ''));
        //add doctor id session
        $request->session()->put('doctor_id', $request->has('doctor_id') ? $request->get('doctor_id') : ($request->session()->has
        ('doctor_id') ? $request->session()->get('doctor_id') : ''));
    }


    public static function messageResponse($message, $success)
    {
        if ($success) {
            return response()->json(['message' => $message, 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_try_again'), 'status' => false]);
    }


   public  static  function getRangeDateString($timestamp)
    {

        if ($timestamp) {
            $currentTime = strtotime('today');
            // Reset time to 00:00:00
            $timestamp = strtotime(date('Y-m-d 00:00:00', strtotime($timestamp)));
            $days = round(($timestamp - $currentTime) / 86400);
            switch ($days) {
                case '0';
                    return 'Today';
                    break;
                case '-1';
                    return "past days";
//                    return 'Yesterday';
                    break;
                case '-2';
                    return "past days";
//                    return 'Day before yesterday';
                    break;
                case '1';
                    return 'Tomorrow';
                    break;
                case '2';
                    return "future days";
//                    return 'Day after tomorrow';
                    break;
                default:
                    if ($days > 0) {
                        return "future days";
//                        return 'In ' . $days . ' days';
                    } else {
                        return "past days";
//                        return ($days * -1) . ' days ago';
                    }
                    break;
            }
        }
    }

}
