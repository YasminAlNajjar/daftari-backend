<?php

namespace App\Http\Requests\Report;

use App\Models\ReportExport;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reportType =
            $this->input('report_type');

        return [

            /*
            |--------------------------------------------------------------------------
            | Report Type
            |--------------------------------------------------------------------------
            */

            'report_type' => [
                'required',

                Rule::in(
                    ReportExport::reportTypes()
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Daily Inventory
            |--------------------------------------------------------------------------
            */

            'date' => [
                Rule::requiredIf(
                    $reportType ===
                    ReportExport::TYPE_DAILY_INVENTORY
                ),

                Rule::prohibitedIf(
                    $reportType !==
                    ReportExport::TYPE_DAILY_INVENTORY
                ),

                'nullable',
                'date_format:Y-m-d',
            ],

            /*
            |--------------------------------------------------------------------------
            | Customer Statement
            |--------------------------------------------------------------------------
            */

            'customer_id' => [
                Rule::requiredIf(
                    $reportType ===
                    ReportExport::TYPE_CUSTOMER_STATEMENT
                ),

                Rule::prohibitedIf(
                    $reportType !==
                    ReportExport::TYPE_CUSTOMER_STATEMENT
                ),

                'nullable',
                'integer',
                'min:1',
            ],

            /*
            |--------------------------------------------------------------------------
            | Customer Statement + General Transactions
            |--------------------------------------------------------------------------
            */

            'from_date' => [
                Rule::requiredIf(
                    in_array(
                        $reportType,
                        [
                            ReportExport::TYPE_CUSTOMER_STATEMENT,
                            ReportExport::TYPE_GENERAL_TRANSACTIONS,
                        ],
                        true
                    )
                ),

                Rule::prohibitedIf(
                    !in_array(
                        $reportType,
                        [
                            ReportExport::TYPE_CUSTOMER_STATEMENT,
                            ReportExport::TYPE_GENERAL_TRANSACTIONS,
                        ],
                        true
                    )
                ),

                'nullable',
                'date_format:Y-m-d',
            ],

            'to_date' => [
                Rule::requiredIf(
                    in_array(
                        $reportType,
                        [
                            ReportExport::TYPE_CUSTOMER_STATEMENT,
                            ReportExport::TYPE_GENERAL_TRANSACTIONS,
                        ],
                        true
                    )
                ),

                Rule::prohibitedIf(
                    !in_array(
                        $reportType,
                        [
                            ReportExport::TYPE_CUSTOMER_STATEMENT,
                            ReportExport::TYPE_GENERAL_TRANSACTIONS,
                        ],
                        true
                    )
                ),

                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:from_date',
            ],

            'type' => [
                Rule::prohibitedIf(
                    !in_array(
                        $reportType,
                        [
                            ReportExport::TYPE_CUSTOMER_STATEMENT,
                            ReportExport::TYPE_GENERAL_TRANSACTIONS,
                        ],
                        true
                    )
                ),

                'nullable',

                Rule::in([
                    'all',
                    Transaction::TYPE_DEBT,
                    Transaction::TYPE_PAYMENT,
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Customer Balances
            |--------------------------------------------------------------------------
            */

            'filter' => [
                Rule::prohibitedIf(
                    $reportType !==
                    ReportExport::TYPE_CUSTOMER_BALANCES
                ),

                'nullable',

                Rule::in([
                    'all',
                    'debtors',
                    'credit',
                    'settled',
                ]),
            ],

            'sort' => [
                Rule::prohibitedIf(
                    $reportType !==
                    ReportExport::TYPE_CUSTOMER_BALANCES
                ),

                'nullable',

                Rule::in([
                    'most_indebted',
                    'least_indebted',
                    'name',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'report_type.required' =>
                'نوع التقرير مطلوب.',

            'report_type.in' =>
                'نوع التقرير غير صالح أو غير قابل للتصدير.',

            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */

            'date.required' =>
                'تاريخ التقرير مطلوب.',

            'date.date_format' =>
                'صيغة التاريخ غير صالحة. يجب أن تكون Y-m-d.',

            'date.prohibited' =>
                'حقل التاريخ غير مسموح لهذا النوع من التقارير.',

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            'customer_id.required' =>
                'الزبون مطلوب.',

            'customer_id.integer' =>
                'معرّف الزبون غير صالح.',

            'customer_id.min' =>
                'معرّف الزبون غير صالح.',

            'customer_id.prohibited' =>
                'حقل الزبون غير مسموح لهذا النوع من التقارير.',

            /*
            |--------------------------------------------------------------------------
            | Date Range
            |--------------------------------------------------------------------------
            */

            'from_date.required' =>
                'تاريخ البداية مطلوب.',

            'from_date.date_format' =>
                'صيغة تاريخ البداية غير صالحة. يجب أن تكون Y-m-d.',

            'from_date.prohibited' =>
                'تاريخ البداية غير مسموح لهذا النوع من التقارير.',

            'to_date.required' =>
                'تاريخ النهاية مطلوب.',

            'to_date.date_format' =>
                'صيغة تاريخ النهاية غير صالحة. يجب أن تكون Y-m-d.',

            'to_date.after_or_equal' =>
                'تاريخ النهاية يجب أن يكون مساويًا لتاريخ البداية أو بعده.',

            'to_date.prohibited' =>
                'تاريخ النهاية غير مسموح لهذا النوع من التقارير.',

            /*
            |--------------------------------------------------------------------------
            | Transaction Type
            |--------------------------------------------------------------------------
            */

            'type.in' =>
                'نوع المعاملة غير صالح.',

            'type.prohibited' =>
                'فلتر نوع المعاملة غير مسموح لهذا التقرير.',

            /*
            |--------------------------------------------------------------------------
            | Customer Balances
            |--------------------------------------------------------------------------
            */

            'filter.in' =>
                'فلتر الأرصدة غير صالح.',

            'filter.prohibited' =>
                'فلتر الأرصدة غير مسموح لهذا التقرير.',

            'sort.in' =>
                'طريقة ترتيب الأرصدة غير صالحة.',

            'sort.prohibited' =>
                'طريقة ترتيب الأرصدة غير مسموحة لهذا التقرير.',
        ];
    }
}