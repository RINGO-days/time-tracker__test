<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRecordRequest extends FormRequest
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
            'date' => ['required','date_format:Y-m-d',
                Rule::unique('attendances','attendance_date')->where(function($query){
                    return $query->where('user_id',$this->user()->id);
                })],
            'clock_in' => ['required','date_format:H:i:s'],
            'clock_out' => ['nullable','date_format:H:i:s'],
            'comment' => ['nullable','max:225']
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
            'date.required' => '勤怠日は必須です。',
            'date.date_format' => '勤怠日はYYYY-MM-DD形式で指定してください。',
            'date.unique' => 'この日付の勤怠はすでに登録されています。',
            'clock_in.required' => '出勤時刻は必須です。',
            'clock_in.date_format' => '出勤時刻はHH:MM:SS形式で指定してください。',
            'clock_out.date_format' => '退勤時刻はHH:MM:SS形式で入力してください。',
            'clock_out.after' => '退勤時刻は出勤時刻より後の時刻を指定してください。'
        ];
    }
}
