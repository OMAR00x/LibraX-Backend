<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidImageFile implements ValidationRule
{
    /**
     * التحقق من أن الملف صورة حقيقية (Magic Bytes)
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof \Illuminate\Http\UploadedFile) {
            $fail('الملف غير صالح');
            return;
        }

        // قراءة أول 12 bytes من الملف
        $handle = fopen($value->getRealPath(), 'rb');
        if (!$handle) {
            $fail('فشل في قراءة الملف');
            return;
        }

        $bytes = fread($handle, 12);
        fclose($handle);

        if ($bytes === false) {
            $fail('فشل في قراءة الملف');
            return;
        }

        // تحويل إلى hex
        $hex = bin2hex($bytes);

        // ✅ التحقق من Magic Bytes للصور
        $validSignatures = [
            'ffd8ff',           // JPEG
            '89504e47',         // PNG
            '47494638',         // GIF
            '424d',             // BMP
            '49492a00',         // TIFF (little-endian)
            '4d4d002a',         // TIFF (big-endian)
            '52494646',         // WEBP (starts with RIFF)
        ];

        foreach ($validSignatures as $signature) {
            if (str_starts_with($hex, $signature)) {
                return; // ✅ صورة صالحة
            }
        }

        // ❌ ليس صورة
        $fail('الملف المرفوع ليس صورة صالحة');
    }
}
