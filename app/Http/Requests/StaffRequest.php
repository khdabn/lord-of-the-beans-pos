<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
class StaffRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array
    {
        $staff = $this->route('staff');
        return [
            'username'=>['required','string','max:50','regex:/^[A-Za-z0-9_.-]+$/',Rule::unique('users','username')->ignore($staff?->id)],
            'full_name'=>['required','string','max:100'],
            'password'=>[$staff ? 'nullable' : 'required','confirmed',Password::min(12)],
            'avatar'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
            'remove_avatar'=>['sometimes','boolean'],
        ];
    }
}
