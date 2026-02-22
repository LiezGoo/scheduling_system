<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user() && Auth::user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'room_code' => 'required|string|max:50|unique:rooms,room_code',
            'room_name' => 'required|string|max:255',
            'room_type_id' => 'required|integer|exists:room_types,id',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'room_code.required' => 'The room code is required.',
            'room_code.unique' => 'This room code already exists.',
            'room_name.required' => 'The room name is required.',
            'room_type_id.required' => 'Please select a room type.',
            'room_type_id.exists' => 'The selected room type is invalid.',
        ];
    }
}
