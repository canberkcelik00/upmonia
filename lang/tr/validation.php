<?php

return [

    'accepted' => ':attribute onaylanmalıdır.',
    'active_url' => ':attribute geçerli bir URL olmalıdır.',
    'after' => ':attribute, :date tarihinden sonraki bir tarih olmalıdır.',
    'after_or_equal' => ':attribute, :date tarihiyle aynı veya sonraki bir tarih olmalıdır.',
    'alpha' => ':attribute sadece harflerden oluşmalıdır.',
    'alpha_dash' => ':attribute sadece harf, rakam, tire ve alt çizgiden oluşmalıdır.',
    'alpha_num' => ':attribute sadece harf ve rakamlardan oluşmalıdır.',
    'array' => ':attribute bir dizi olmalıdır.',
    'before' => ':attribute, :date tarihinden önceki bir tarih olmalıdır.',
    'before_or_equal' => ':attribute, :date tarihiyle aynı veya önceki bir tarih olmalıdır.',
    'between' => [
        'numeric' => ':attribute :min ile :max arasında olmalıdır.',
        'string' => ':attribute :min ile :max karakter arasında olmalıdır.',
        'array' => ':attribute :min ile :max öğe arasında olmalıdır.',
    ],
    'boolean' => ':attribute doğru veya yanlış olmalıdır.',
    'confirmed' => ':attribute onayı eşleşmiyor.',
    'date' => ':attribute geçerli bir tarih olmalıdır.',
    'different' => ':attribute ve :other birbirinden farklı olmalıdır.',
    'digits' => ':attribute :digits haneli olmalıdır.',
    'email' => ':attribute geçerli bir e-posta adresi olmalıdır.',
    'exists' => 'Seçili :attribute geçerli değil.',
    'gt' => [
        'numeric' => ':attribute :value değerinden büyük olmalıdır.',
        'string' => ':attribute :value karakterden uzun olmalıdır.',
    ],
    'image' => ':attribute bir resim olmalıdır.',
    'in' => 'Seçili :attribute geçersiz.',
    'integer' => ':attribute bir tam sayı olmalıdır.',
    'lt' => [
        'numeric' => ':attribute :value değerinden küçük olmalıdır.',
        'string' => ':attribute :value karakterden kısa olmalıdır.',
    ],
    'max' => [
        'numeric' => ':attribute :max değerinden büyük olamaz.',
        'string' => ':attribute :max karakterden uzun olamaz.',
        'array' => ':attribute :max öğeden fazla olamaz.',
    ],
    'min' => [
        'numeric' => ':attribute en az :min olmalıdır.',
        'string' => ':attribute en az :min karakter olmalıdır.',
        'array' => ':attribute en az :min öğe içermelidir.',
    ],
    'not_in' => 'Seçili :attribute geçersiz.',
    'numeric' => ':attribute bir sayı olmalıdır.',
    'required' => ':attribute alanı zorunludur.',
    'required_if' => ':other, :value olduğunda :attribute alanı zorunludur.',
    'same' => ':attribute ve :other eşleşmelidir.',
    'size' => [
        'numeric' => ':attribute :size olmalıdır.',
        'string' => ':attribute :size karakter olmalıdır.',
        'array' => ':attribute :size öğe içermelidir.',
    ],
    'string' => ':attribute bir metin olmalıdır.',
    'unique' => 'Bu :attribute zaten kullanılıyor.',
    'url' => ':attribute geçerli bir URL olmalıdır.',

    // Laravel's password composition rule (Password::min()->letters()->numbers()...).
    'password' => [
        'letters' => ':attribute en az bir harf içermelidir.',
        'mixed' => ':attribute en az bir büyük ve bir küçük harf içermelidir.',
        'numbers' => ':attribute en az bir rakam içermelidir.',
        'symbols' => ':attribute en az bir sembol içermelidir.',
        'uncompromised' => 'Girilen :attribute bir veri sızıntısında ortaya çıkmış. Lütfen farklı bir :attribute seçin.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'ad soyad',
        'email' => 'e-posta',
        'password' => 'parola',
        'password_confirmation' => 'parola tekrarı',
        'organization_name' => 'şirket adı',
    ],

];
