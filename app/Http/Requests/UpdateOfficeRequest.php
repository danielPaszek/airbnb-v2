<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfficeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->tokenCan('office.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
        'title' => ['filled', 'string'],
        'description' => ['filled', 'string'],
        'address_line1' => ['filled', 'string'],
        'lat' => ['filled', 'numeric'],
        'lng' => ['filled', 'numeric'],
        'hidden' => ['bool'],
        'price_per_day' => ['filled', 'integer', 'min:100'],
        'monthly_discount' => ['integer', 'min:0', 'max:99'],

        'tags' => ['array'],
        //query for each tag. Change later!
        'tags.*' => ['integer', Rule::exists('tags', 'id')],
    ];
    }
}
