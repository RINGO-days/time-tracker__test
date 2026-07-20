<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\AttendanceService;
use Symfony\Component\HttpFoundation\StreamedResponse;


class CsvExportController extends Controller
{
        /**
     * 管理者用のスタッフの月次勤怠リストからCSVファイルとして表示されている月の勤怠のCSVファイルの出力
     * - リクエストボディをサービスクラスgetMonthPeriodに渡し、月の指定をする変数を取得($preMonth,$nextMonth,$targetMonth)
     * - StreamedResponseを用いてデータを順次ブラウザへ出力する
     * - UTF-8のBOMを指定することでExcelなどで文字化けを防ぐ
     * - リクエストボディをサービスクラスgetMonthlyRecordsに渡し、取得した１ヶ月の勤怠情報をCSVのデータとして取得する
     * @param Request $request クエリパラメータからCSV出力したい月を取得
     * @param AttendanceService $attendanceService CSV出力のための変数の取得と月毎の勤怠情報の取得
     * @return StreamedResponse CSVダウンロード用のストリームレスポンス
     */
    public function export(Request $request,AttendanceService $attendanceService) : StreamedResponse
    {
        extract($attendanceService->getMonthPeriod($request));
        $user = User::findOrFail($request->id);
        $fileName = $user->name . 'さんの勤怠＿' . $targetMonth . '.csv';
        $response = new StreamedResponse(function() use($startOfMonth,$endOfMonth,$attendanceService,$request){
            $stream = fopen('php://output','w');
            fwrite($stream,pack('C*', 0xEF, 0xBB, 0xBF));
            $header = ['日付','出勤','退勤','休憩','合計'];
            fputcsv($stream,$header);

            $records = $attendanceService->getMonthlyRecords($request);
            foreach($records as $row)
                fputcsv($stream,[
                    $row['date']."[".$row['week']."]",
                    $row['attendance'],
                    $row['leave'],
                    $row['rest'],
                    $row['actualTime'],
                ]);
            fclose($stream);
            });
        $response->headers->set('Content-Type','text/csv');
        $response->headers->set('Content-Disposition','attachment; filename="' . rawurlencode($fileName) . '"');
        return $response;
    }

}
