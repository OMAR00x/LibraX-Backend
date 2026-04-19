<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseRequest extends FormRequest
{
    public function messages(): array
    {
        return array_merge($this->commonMessages(), $this->customMessages());
    }

    protected function commonMessages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب',
            'string' => 'حقل :attribute يجب أن يكون نص',
            'integer' => 'حقل :attribute يجب أن يكون رقم صحيح',
            'numeric' => 'حقل :attribute يجب أن يكون رقم',
            'exists' => 'القيمة المحددة في :attribute غير موجودة',
            'unique' => 'القيمة المحددة في :attribute مستخدمة مسبقاً',
            'min.string' => 'حقل :attribute يجب أن يحتوي على :min حرف على الأقل',
            'min.numeric' => 'حقل :attribute يجب أن يكون :min على الأقل',
            'max.string' => 'حقل :attribute يجب ألا يتجاوز :max حرف',
            'max.numeric' => 'حقل :attribute يجب ألا يتجاوز :max',
            'confirmed' => 'حقل التأكيد لا يطابق :attribute',
            'in' => 'القيمة المحددة في :attribute غير صحيحة',
            'array' => 'حقل :attribute يجب أن يكون مصفوفة',
            'date' => 'حقل :attribute يجب أن يكون تاريخ صحيح',
            'boolean' => 'حقل :attribute يجب أن يكون صحيح أو خطأ',
            'image' => 'حقل :attribute يجب أن يكون صورة',
            'mimes' => 'حقل :attribute يجب أن يكون من نوع: :values',
            'max.file' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت',
            'size' => 'حقل :attribute يجب أن يكون :size',
        ];
    }

    protected function customMessages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }

    protected function failedValidation(Validator $validator)
    {
        // Log validation errors for debugging
        \Log::error('Validation failed', [
            'url' => request()->url(),
            'method' => request()->method(),
            'errors' => $validator->errors()->toArray(),
            'input' => request()->except(['password', 'password_confirmation'])
        ]);
        
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'فشل التحقق من البيانات',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
