<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\Attendance;

class HeaderComposer
{
    /**
     * スタッフ画面のヘッダーを出勤時のステータスによって変える
     * 管理者だった場合、管理者用のヘッダーを表示する
     *
     * 未ログイン状態の場合はヘッダーを表示しない
     */
    public function compose(View $view): void
    {
        if (!auth()->check()) {
            $view->with([
                'status' => null,
                'is_admin' => false,
            ]);
            return;
        }

        $status = Attendance::where('attendance_date', today()->format('Y-m-d'))
            ->where('user_id', auth()->id())
            ->latest()
            ->value('status');

        $user = auth()->user();

        $view->with([
            'status' => (int)$status,
            'is_admin' => (bool)$user->is_admin
        ]);
    }
}
