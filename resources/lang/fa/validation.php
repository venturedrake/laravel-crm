<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'فیلد :attribute باید تایید شود',
    'accepted_if' => 'زمانی که فیلد :other برابر :value است :attribute باید تایید شود',
    'active_url' => 'فیلد :attribute یک آدرس سایت معتبر نیست',
    'after' => 'فیلد :attribute باید تاریخی بعد از :date باشد',
    'after_or_equal' => 'فیلد :attribute باید تاریخی مساوی یا بعد از :date باشد',
    'alpha' => 'فیلد :attribute باید تنها شامل حروف باشد',
    'alpha_dash' => 'فیلد :attribute باید تنها شامل حروف، اعداد، خط تیره و زیر خط باشد',
    'alpha_num' => 'فیلد :attribute باید تنها شامل حروف و اعداد باشد',
    'array' => 'فیلد :attribute باید آرایه باشد',
    'ascii' => 'فیلد :attribute تنها میتواند شامل تک حرف، عدد یا نماد ها باشد. .',
    'before' => 'فیلد :attribute باید تاریخی قبل از :date باشد',
    'before_or_equal' => 'فیلد :attribute باید تاریخی مساوی یا قبل از :date باشد',
    'between' => [
        'array' => 'فیلد :attribute باید بین :min و :max آیتم باشد',
        'file' => 'فیلد :attribute باید بین :min و :max کیلوبایت باشد',
        'numeric' => 'فیلد :attribute باید بین :min و :max باشد',
        'string' => 'فیلد :attribute باید بین :min و :max کاراکتر باشد',
    ],
    'boolean' => 'فیلد :attribute تنها می تواند صحیح یا غلط باشد',
    'confirmed' => 'تایید مجدد فیلد :attribute صحیح نمی باشد',
    'current_password' => 'رمزعبور صحیح نمی باشد',
    'date' => 'فیلد :attribute یک تاریخ صحیح نمی باشد',
    'date_equals' => 'فیلد :attribute باید تاریخی مساوی با :date باشد',
    'date_format' => 'فیلد :attribute با فرمت :format همخوانی ندارد',
    'decimal' => 'فیلد :attribute باید :decimal رقم اعشار داشته باشد.',
    'declined' => 'فیلد :attribute باید رد شود',
    'declined_if' => 'فیلد :attribute زمانی که :other برابر :value است باید رد شود',
    'different' => 'فیلد :attribute و :other باید متفاوت باشند',
    'digits' => 'فیلد :attribute باید :digits عدد باشد',
    'digits_between' => 'فیلد :attribute باید بین :min و :max عدد باشد',
    'dimensions' => 'ابعاد تصویر فیلد :attribute مجاز نمی باشد',
    'distinct' => 'فیلد :attribute دارای افزونگی داده می باشد',
    'doesnt_end_with' => 'فیلد :attribute نباید با این مقادیر به پایان برسد: :values.',
    'doesnt_start_with' => 'فیلد :attribute نباید با این مقادیر شروع شود: :values.',
    'email' => 'فیلد :attribute باید یک آدرس ایمیل صحیح باشد',
    'ends_with' => 'فیلد :attribute باید با یکی از این مقادیر پایان یابد، :values',
    'enum' => 'فیلد :attribute صحیح نمی باشد',
    'exists' => 'فیلد انتخاب شده :attribute صحیح نمی باشد',
    'file' => 'فیلد :attribute باید یک فایل باشد',
    'filled' => 'فیلد :attribute نمی تواند خالی باشد',
    'gt' => [
        'array' => 'فیلد :attribute باید بیشتر از :value آیتم باشد',
        'file' => 'فیلد :attribute باید بزرگتر از :value کیلوبایت باشد',
        'numeric' => 'فیلد :attribute باید بزرگتر از :value باشد',
        'string' => 'فیلد :attribute باید بزرگتر از :value کاراکتر باشد',
    ],
    'gte' => [
        'array' => 'فیلد :attribute باید :value آیتم یا بیشتر داشته باشد',
        'file' => 'فیلد :attribute باید بزرگتر یا مساوی :value کیلوبایت باشد',
        'numeric' => 'فیلد :attribute باید بزرگتر یا مساوی :value باشد',
        'string' => 'فیلد :attribute باید بزرگتر یا مساوی :value کاراکتر باشد',
    ],
    'image' => 'فیلد :attribute باید از نوع تصویر باشد',
    'in' => 'فیلد :attribute صحیح نمی باشد',
    'in_array' => 'فیلد :attribute در :other وجود ندارد',
    'integer' => 'فیلد :attribute باید از نوع عددی باشد',
    'ip' => 'فیلد :attribute باید آی پی آدرس باشد',
    'ipv4' => 'فیلد :attribute باید آی پی آدرس ورژن 4 باشد',
    'ipv6' => 'فیلد :attribute باید آی پی آدرس ورژن 6 باشد',
    'json' => 'فیلد :attribute باید از نوع رشته جیسون باشد',
    'lowercase' => 'فیلد :attribute باید با حروف کوچک باشد.',
    'hex_color' => 'فیلد :attribute باید یک کد رنگ معتبر باشد.',
    'lt' => [
        'array' => 'فیلد :attribute باید کمتر از :value آیتم داشته باشد',
        'file' => 'فیلد :attribute باید کمتر از :value کیلوبایت باشد',
        'numeric' => 'فیلد :attribute باید کمتر از :value باشد',
        'string' => 'فیلد :attribute باید کمتر از :value کاراکتر باشد',
    ],
    'lte' => [
        'array' => 'فیلد :attribute نباید کمتر از :value آیتم داشته باشد',
        'file' => 'فیلد :attribute باید مساوی یا کمتر از :value کیلوبایت باشد',
        'numeric' => 'فیلد :attribute باید مساوی یا کمتر از :value باشد',
        'string' => 'فیلد :attribute باید مساوی یا کمتر از :value کاراکتر باشد',
    ],
    'mac_address' => 'فیلد :attribute باید یک مک آدرس معتبر باشد',
    'max' => [
        'array' => 'فیلد :attribute نباید بیشتر از :max آیتم داشته باشد',
        'file' => 'فیلد :attribute نباید بزرگتر از :max کیلوبایت باشد',
        'numeric' => 'فیلد :attribute نباید بزرگتر از :max باشد',
        'string' => 'فیلد :attribute نباید بزرگتر از :max کاراکتر باشد',
    ],
    'max_digits' => 'فیلد :attribute نباید بیشتر از :max رقم باشد',
    'mimes' => 'فیلد :attribute باید دارای یکی از این فرمت ها باشد: :values',
    'mimetypes' => 'فیلد :attribute باید دارای یکی از این فرمت ها باشد: :values',
    'min' => [
        'array' => 'فیلد :attribute باید حداقل :min آیتم داشته باشد',
        'file' => 'فیلد :attribute باید حداقل :min کیلوبایت باشد',
        'numeric' => 'فیلد :attribute باید حداقل :min باشد',
        'string' => 'فیلد :attribute باید حداقل :min کاراکتر باشد',
    ],
    'min_digits' => 'فیلد :attribute باید حداقل :min رقم باشد',
    'missing' => 'فیلد :attribute نباید تعریف شود.',
    'missing_if' => 'فیلد :attribute زمانی که مقدار :other برابر :value می باشد، نباید تعریف شود',
    'missing_unless' => 'فیلد :attribute نباید تعریف شود مگر اینکه فیلد :other برابر :value باشد',
    'missing_with' => 'فیلد :attribute زمانی که مقدار :values تعریف شده است دیگر نباید تعریف شود.',
    'missing_with_all' => 'فیلد :attribute زمانی که :values مقدار دارد دیگر نباید تعریف شود.',
    'multiple_of' => 'فیلد :attribute باید حاصل ضرب :value باشد',
    'not_in' => 'فیلد :attribute صحیح نمی باشد',
    'not_regex' => 'فرمت فیلد :attribute صحیح نمی باشد',
    'numeric' => 'فیلد :attribute باید از نوع عددی باشد',
    'password' => [
        'letters' => 'فیلد :attribute باید حداقل شامل یک حرف باشد',
        'mixed' => 'فیلد :attribute باید شامل حداقل یک حرف بزرگ و یک حرف کوچک باشد',
        'numbers' => 'فیلد :attribute باید شامل حداقل یک عدد باشد',
        'symbols' => 'فیلد :attribute باید شامل حداقل یک کارکتر خاص باشد',
        'uncompromised' => 'محتوای وارده شده در :attribute ایمن نمی باشد. لطفا فیلد :attribute را اصلاح فرمایید',
    ],
    'present' => 'فیلد :attribute باید از نوع درصد باشد',
    'prohibited' => 'فیلد :attribute مجاز نمی باشد',
    'prohibited_if' => 'فیلد :attribute زمانی که :other برابر :value باشد مجاز نمی باشد',
    'prohibited_unless' => 'فیلد :attribute مجاز نیست مگر :other برابر :values باشد',
    'prohibits' => 'فیلد :attribute باعث ممنوعیت :other می باشد',
    'regex' => 'فرمت فیلد :attribute صحیح نمی باشد',
    'required' => 'تکمیل فیلد :attribute الزامی است',
    'required_array_keys' => 'فیلد :attribute باید شامل مقادیر: :values باشد',
    'required_if' => 'تکمیل فیلد :attribute زمانی که :other دارای مقدار :value است الزامی می باشد',
    'required_if_accepted' => 'تکمیل فیلد :attribute زمانی که :other انتخاب شده الزامی است',
    'required_unless' => 'تکمیل فیلد :attribute الزامی می باشد مگر :other دارای مقدار :values باشد',
    'required_with' => 'تکمیل فیلد :attribute زمانی که مقدار :values درصد است الزامی است',
    'required_with_all' => 'تکمیل فیلد :attribute زمانی که مقادیر :values درصد است الزامی می باشد',
    'required_without' => 'تکمیل فیلد :attribute زمانی که مقدار :values درصد نیست الزامی است',
    'required_without_all' => 'تکمیل فیلد :attribute زمانی که هیچ کدام از مقادیر :values درصد نیست الزامی است',
    'same' => 'فیلد های :attribute و :other باید یکی باشند',
    'size' => [
        'array' => 'فیلد :attribute باید شامل :size آیتم باشد',
        'file' => 'فیلد :attribute باید :size کیلوبایت باشد',
        'numeric' => 'فیلد :attribute باید :size باشد',
        'string' => 'فیلد :attribute باید :size  کاراکتر باشد',
    ],
    'starts_with' => 'فیلد :attribute باید با یکی از این مقادیر شروع شود، :values',
    'string' => 'فیلد :attribute باید تنها شامل حروف باشد',
    'timezone' => 'فیلد :attribute باید از نوع منطقه زمانی صحیح باشد',
    'unique' => 'این :attribute از قبل ثبت شده است',
    'uploaded' => 'بارگذاری فیلد :attribute شکست خورد',
    'uppercase' => 'فیلد :attribute باید با حروف بزرگ باشد',
    'url' => 'فرمت :attribute اشتباه است',
    'ulid' => 'فیلد :attribute باید یک ULID صحیح باشد.',
    'uuid' => 'فیلد :attribute باید یک UUID صحیح باشد',
    'phone' => 'فیلد :attribute باید یک شماره همراه صحیح باشد.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
    ],

];
