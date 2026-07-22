<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() : bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() : array
    {
        return [
            'date' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::unique('attendances','attendance_date')->ignore($this->route('attendanceRecord')->id)->where('user_id', $this->user()->id)
            ],
            'clock_in' => ['nullable','date_format:H:i:s','before:clock_out'],
            'clock_out' => ['nullable','date_format:H:i:s', 'after:clock_in'],
            'comment' => ['nullable','max:255']
        ];

        // 出勤時刻が適切な形式ではない場合に、clock_outのafterのバリデーションが実行されるのを防ぐ
        $clockIn = $this->input('clock_in');
        if ($clockIn && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $clockIn)) {
            $rules['clock_out'][] = 'after:clock_in';
        }
    }

    public function messages() : array
    {
        return [
            'date.unique' => 'この日付の勤怠はすでに登録されています。',
            'date.date_format' => '勤怠日はYYYY-MM-DD形式で指定してください。',
            'clock_in.before' => '出勤時刻は退勤時間より前の時刻を指定してください。',
            'clock_in.date_format' => '出勤時刻はHH:MM:SS形式で指定してください。',
            'clock_out.after' => '退勤時刻は出勤時刻より後の時刻を選択してください。',
            'clock_out.date_format' => '退勤時刻はHH:MM:SS形式で指定してください。',
            'comment.max' => 'コメントの最大文字数は255文字です。'
        ];
    }

    /**
     * 退勤時間を修正する場合に、出勤時間の修正の値がなかった時、データベースからすでに登録されている出勤時間を反映し、バリデーションチェック(after)を行う
     * 出勤時間の修正も同様
     */
    public function prepareForValidation() : void
    {
        $attendance = $this->route('attendanceRecord');

        if(!$this->has('clock_in') && $attendance->attendance_time){
            $this->merge(['clock_in' => $attendance->attendance_time->format('H:i:s')]);
        }

        if(!$this->has('clock_out') && $attendance->leave_time){
            $this->merge(['clock_out' => $attendance->leave_time->format('H:i:s')]);
        }
    }
}
