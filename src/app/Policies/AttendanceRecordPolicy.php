<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;


class AttendanceRecordPolicy
{
    use HandlesAuthorization;

    public function before(User $user) : bool | null
    {
        if($user->is_admin){
            return true;
        }else{
            return null;
        }
    }
    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user) : bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Attendance $attendance) : bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user) : bool
    {
        return true;
    }

    /**
     * 勤怠情報の更新の権限の判別
     *
     * 指定した勤怠に紐付いているuser_idとログイン中のユーザーIDが一致した時、
     * またはログイン中のユーザーが管理者だった場合のみ更新が可能となる
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Attendance $attendance) : Response
    {
        return $user->id === $attendance->user_id || $user->is_admin
            ? Response::allow()
            : Response::deny('この操作を実行する権限がありません。');
    }

    /**
     * 勤怠情報の削除の権限の判別
     *
     * 指定した勤怠に紐付いているuser_idとログイン中のユーザーIDが一致した時、
     * またはログイン中のユーザーが管理者だった場合のみ削除が可能となる
     *
     * @param  \App\Models\User  $user ログイン中のユーザーモデル
     * @param  \App\Models\Attendance  $attendance 指定した勤怠のモデル
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Attendance $attendanceRecord) : Response
    {
        return $user->id === $attendanceRecord->user_id || $user->is_admin
            ? Response::allow()
            : Response::deny('この操作を実行する権限がありません。');
    }
    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user ログイン中のユーザーモデル
     * @param  \App\Models\Attendance  $attendance 指定した勤怠のモデル
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Attendance $attendance)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Attendance $attendance)
    {
        //
    }
}
