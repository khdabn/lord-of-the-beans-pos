<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SaleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array
    {
        return ['product_id'=>['required','integer','exists:products,id'],
            'customer_id'=>['nullable','integer','exists:customers,id'],
            'quantity'=>['required','integer','min:1','max:1000000'],
            'request_token'=>['required','uuid']];
    }
}
