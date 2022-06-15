<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfficeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->tokenCan('office.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'title' => ['required', 'string'],
            'description' => ['required', 'string'],
            'address_line1' => ['required', 'string'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'hidden' => ['bool'],
            'price_per_day' => ['required', 'integer', 'min:100'],
            'monthly_discount' => ['integer', 'min:0', 'max:99'],

            'tags' => ['array'],
            //query for each tag. Change later!
            'tags.*' => ['integer', Rule::exists('tags', 'id')],
        ];
    }
}
