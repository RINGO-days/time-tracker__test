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
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'date' => [
                'nullable',
                Rule::unique('attendances','attendance_date')->ignore($this->route('attendanceRecord')->id)->where('user_id', $this->user()->id)
            ],
            'clock_in' => ['nullable','date_format:H:i:s'],
            'clock_out' => ['required_with:clock_out','nullable','date_format:H:i:s'],
            'comment' => ['nullable','string','max:255']
        ];

        // 出勤時刻が適切な形式ではない場合に、clock_outのafterのバリデーションが実行されるのを防ぐ
        $clockIn = $this->input('clock_in');
        if ($clockIn && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $clockIn)) {
            $rules['clock_out'][] = 'after:clock_in';
        }
    }

    public function messages()
    {
        return [
            'date.unique' => 'この日付の勤怠はすでに登録されています。',
            'clock_in.before' => '出勤時刻は退勤時間より前の時刻を指定してください。',
            'clock_in.date_format' => '出勤時刻はHH:MM:SS形式で指定してください。',
            'clock_out.after' => '退勤時刻は出勤時刻より後の時刻を選択してください。',
            'clock_out.date_format' => '退勤時刻はHH:MM:SS形式で指定してください。',
            'comment.max' => 'コメントの最大文字数は255文字です。'
        ];
    }

    public function prepareForValidation()
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
