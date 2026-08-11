<?php


namespace App\Http\Helper;

Use Illuminate\Support\Facades\Auth;

class FunctionsHelper
{
    public function __construct()
    {
    }

    /**
     * 上传是否被服务器的体积上限挡住；被挡住时返回触发的那个上限值（如 "8M"）。
     *
     * 两种情形，用户看到的现象一样，但 PHP 给的信号完全不同：
     *
     *   1) 超过 upload_max_filesize —— 文件仍在 $_FILES 里，带着
     *      UPLOAD_ERR_INI_SIZE，能直接读出来。
     *   2) 超过 post_max_size —— PHP 会**丢掉整个请求体**：$_POST 和 $_FILES
     *      全空，$request->file() 是 null。此时校验器只会说「请上传图片」，
     *      而用户明明选了文件 —— 提示指向完全错误的方向，人会反复重选文件。
     *      只能靠 Content-Length 与 post_max_size 比对反推。
     *
     * 返回 null 表示不是体积问题，交给正常校验流程。
     */
    public static function exceededUploadLimit($request, string $field): ?string
    {
        $uploaded = $request->file($field);

        if ($uploaded !== null && !$uploaded->isValid()
            && in_array($uploaded->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return (string) ini_get('upload_max_filesize');
        }

        // 请求体被整个丢弃的特征：没有文件、没有任何 POST 字段，但 Content-Length
        // 明显超过 post_max_size。只在确实超限时才认，避免把普通的「没选文件」误判。
        $postMax = static::iniSizeToBytes((string) ini_get('post_max_size'));
        $length  = (int) $request->server('CONTENT_LENGTH', 0);

        if ($uploaded === null && $postMax > 0 && $length > $postMax && count($request->post()) === 0) {
            return (string) ini_get('post_max_size');
        }

        return null;
    }

    /**
     * php.ini 的体积写法（"8M" / "512K" / "1G"）转字节。0 或空表示不限制。
     */
    public static function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit   = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g'     => $number * 1024 * 1024 * 1024,
            'm'     => $number * 1024 * 1024,
            'k'     => $number * 1024,
            default => (int) $value,
        };
    }

    public static function navigation()
    {
        return 'layouts.app';
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
