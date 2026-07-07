<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceRecordRequest extends FormRequest
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
            'user_id' => ['nullable','integer'],
            'date' => ['nullable','date_format:Y-m-d',],
            'month' => ['nullable','string','regex:/^\d{4}-\d{2}$/'],
            'page' => ['nullable','integer'],
            'per_page' => ['nullable','integer','max:100']
        ];
    }

    public function messages()
    {
        return [
            'user_id.integer' => 'ユーザーIDは整数を入力してください。',
            'date.date_format' => 'YYYY-MM-DD形式を指定してください。',
            'month.regex' => 'YYYY-MM形式を指定してください。',
            'page.integer' => 'ページ番号は整数を入力してください。',
            'per_page.integer' => '件数の表示数は整数を入力してください。',
            'per_page.max' => '件数の表示数の最大は100件です。'
        ];
    }
}
