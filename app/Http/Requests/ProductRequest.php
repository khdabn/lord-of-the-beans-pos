<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ProductRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array
    {
        return [
            'name'=>['required','string','max:100'],
            'price'=>['required','numeric','decimal:0,2','regex:/^\d+(\.\d{1,2})?$/D','min:0.01','max:99999999.99'],
            'stock_quantity'=>['required','integer','min:0','max:1000000'],
            'image'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
            'remove_image'=>['sometimes','boolean'],
            'stock_version'=>[$this->route('product') ? 'required' : 'nullable','integer','min:0'],
        ];
    }
}
