<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetHostReservations extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'status' => [Rule::in([Reservation::STATUS_ACTIVE, Reservation::STATUS_CANCELLED])],
            'from_date' => ['date', 'requirede_with:to_date'],
            'to_date' => ['date', 'required_with:from_date', 'after:from_date'],
            'office_id' => ['integer'],
            'user_id' => ['integer']
        ];
    }
}
