<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProposalRequest extends FormRequest
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
            'attendance' => ['required','date_format:H:i'],
            'leave' => ['required','date_format:H:i','after:attendance'],
            'remarks' => ['required','string','max:225'],
            'rest' => ['nullable','array'],

            'rest.*.rest_id' => ['nullable','integer'],
            'rest.*.rest_start' => ['nullable','date_format:H:i','required_with:rest.*.rest_end','after:attendance','before:leave'],
            'rest.*.rest_end' => ['nullable','date_format:H:i','required_with:rest.*.rest_start','after:rest.*.rest_start','before:leave'],

            'rest.new.rest_start' => ['nullable','date_format:H:i','required_with:rest.new.rest_end','before:rest.new.rest_end'],
            'rest.new.rest_end' => ['nullable','date_format:H:i','required_with:rest.new.rest_start','after:rest.new.rest_start'],

        ];
    }
    
    public function messages()
    {
        return [
            'attendance.required' => '出勤時間を入力してください。',
            'attendance.date_format' => '時:分の形式で入力してください。',

            'leave.required' => '退勤時間を入力してください。',
            'leave.date_format' => '時:分の形式で入力してください。',
            'leave.after' => '出勤時間もしくは退勤時間が不適切な値です',

            'remarks.required' => '備考を記入してください。',
            'remarks.max' => '備考は225文字以内で入力してください。',

            'rest.*.rest_start.date_format' => '時:分の形式で入力してください。',
            'rest.*.rest_start.required_with' => '休憩開始時間を入力してください。',
            'rest.*.rest_start.after' => '休憩時間が不適切な値です。',
            'rest.*.rest_start.before' => '休憩時間が不適切な値です。',

            'rest.*.rest_end.date_format' => '時:分の形式で入力してください。',
            'rest.*.rest_end.required_with' => '休憩終了時刻を入力してください。',
            'rest.*.rest_end.after' => '休憩時間もしくは退勤時間が不適切な値です。',
            'rest.*.rest_end.before' => '休憩時間もしくは退勤時間が不適切な値です。',

            'rest.new.rest_start.date_format' => '時:分の形式で入力してください。',
            'rest.new.rest_start.required_with' => '休憩開始時間を入力してください。',
            'rest.new.rest_start.after' => '休憩時間もしくは退勤時間が不適切な値です。',

            'rest.new.rest_end.date_format' => '時:分の形式で入力してください。',
            'rest.new.rest_end.required_with' => '休憩開始時間を入力してください。',
            'rest.new.rest_end.before' => '休憩時間もしくは退勤時間が不適切な値です。',

        ];
    }
}
