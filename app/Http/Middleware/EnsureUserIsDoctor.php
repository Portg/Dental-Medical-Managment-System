<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 医生自助模块（/doctor-appointments、/claims）的模块门禁。
 *
 * 这些控制器原先只挂 can:view-appointments。那条权限在正式权限种子里
 * （DefaultRolePermissionsSeeder）同时发给了医生、护士、前台和管理员 ——
 * 换句话说，「医生模块」对全院开着。数据层按 Auth::User()->id 过滤，所以
 * 列表是空的，看起来没事；但写入路径不看这个：护士或前台照样能 POST /claims
 * 提交提成、POST /doctor-appointments 把自己排成接诊医生。
 *
 * 判据用 users.is_doctor，与 DoctorReportController、DentalChartService
 * 判断「当前用户是不是医生」用的是同一个字段，不再新造一套权限。
 * 权限中间件仍然保留：这一条只回答「是不是医生」，不回答「能不能看预约」。
 */
class EnsureUserIsDoctor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_doctor) {
            abort(403, __('messages.unauthorized_access'));
        }

        return $next($request);
    }
}
