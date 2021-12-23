<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppealRequest extends FormRequest
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
            'esia_login' => 'required',
            'esia_password' => 'required',
            'selenium_url' => 'required',

            'birthplace' => 'required',
            'appeal_type' => 'required',
            'court_region' => 'required',
            'court_judiciary' => 'required',

            'applicants.*.name' => 'required',
            'applicants.*.inn' => 'required',
            'applicants.*.procedural_status',
            'applicants.*.ogrn',
            'applicants.*.kpp',
            'applicants.*.legal_address.zipcode' => 'required',
            'applicants.*.legal_address.address' => 'required',
            'applicants.*.location_address.zipcode' => 'required',
            'applicants.*.location_address.address' => 'required',
            'applicants.*.email',
            'applicants.*.phone',

            'participants.*.procedural_status',
            'participants.*.first_name' => 'required',
            'participants.*.middle_name' => 'required',
            'participants.*.last_name',
            'participants.*.gender',
            'participants.*.birthplace' => 'required',

            'participants.*.registration.zipcode' => 'required',
            'participants.*.registration.address' => 'required',

            'participants.*.residence.zipcode' => 'required',
            'participants.*.residence.address' => 'required',

            'participants.*.snils',

            'participants.*.identity_type',
            'participants.*.identity.series',
            'participants.*.identity.issue_date',

            'participants.*.drivers_license.series',
            'participants.*.drivers_license.number',
            'participants.*.vehicle.series',
            'participants.*.vehicle.number',
            'participants.*.email',
            'participants.*.phone',

            'proxy_files.*' => 'required|array|min:1|max:1',
            'essence_files.*' => 'required|array|min:1|max:1',
            'attachment_files.*' => 'required|max:30720',
            'payment_files.*' => 'required|array|min:1|max:1',

        ];
    }
}
